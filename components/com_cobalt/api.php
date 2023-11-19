<?php
defined('_JEXEC') or die();

jimport('joomla.database.table');
jimport('joomla.form.form');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.table.table');
jimport('mint.mvc.model.base');
jimport('mint.mvc.controller.base');
jimport('mint.mvc.view.base');
jimport('mint.forms.helper');
jimport('mint.helper');

JHtml::_('bootstrap.framework');

JLoader::discover('MModel', JPATH_LIBRARIES.'/mint/mvc/model');
JLoader::discover('MView', JPATH_LIBRARIES.'/mint/mvc/view');
JLoader::discover('MController', JPATH_LIBRARIES.'/mint/mvc/controller');

JLoader::registerPrefix('Cobalt', JPATH_ROOT . '/components/com_cobalt');

JTable::addIncludePath(JPATH_ROOT . '/components/com_cobalt/tables');
MModelBase::addIncludePath(JPATH_ROOT . '/components/com_cobalt/models', 'CobaltModel');

JForm::addFieldPath(JPATH_ROOT . '/libraries/mint/forms/fields');
JHtml::addIncludePath(JPATH_ROOT . '/components/com_cobalt/library/php/html');
JHtml::addIncludePath(JPATH_ROOT . '/components/com_cobalt/library/php');

foreach (glob('components/com_cobalt/library/php/helpers/*.php') as $filename) {
	require_once JPATH_ROOT.DIRECTORY_SEPARATOR.$filename;
}

JFactory::getLanguage()->load('com_cobalt', JPATH_ROOT);

if(JComponentHelper::getParams('com_cobalt')->get('compatibility')) {
	JHtml::_('bootstrap.loadCss');
}

JHTML::_('behavior.tooltip');
JHTML::_('behavior.modal');
JHTML::_('bootstrap.popover', '*[rel="popover"]',
	[
		'placement' => 'bottom',
		'trigger'   => 'click'
	]
);
JHTML::_('bootstrap.tooltip', '*[rel^="tooltip"]');
JHTML::_('bootstrap.tooltip', '*[rel="tooltipright"]',
	[
		'placement' => 'right'
	]
);
JHTML::_('bootstrap.tooltip', '*[rel="tooltipbottom"]',
	[
		'placement' => 'bottom'
	]
);

$em_api = JPATH_ROOT . '/components/com_emerald/api.php';
if(JFile::exists($em_api)) {
	require_once $em_api;
}

use Joomla\Registry\Registry;

class CobaltApi
{
	public const FIELD_FULL = 'full';
	public const FIELD_LIST = 'list';

	public static function getArticleLink($record_id, $attribs = null): string
	{
		$record = ItemsStore::getRecord($record_id);
		$url = JRoute::_(Url::record($record));

		return JHtml::link($url, $record->title, $attribs);
	}

	/**
	 *
	 * @param string $condition Somethign like 'r.id = 12' or 'r.id IN (SELECT...)'
	 * @return array
	 */
	public static function renderRating($type_id, $section_id, $condition): array
	{
		$type    = ItemsStore::getType($type_id);
		$section = ItemsStore::getSection($section_id);

		$db    = JFactory::getDbo();
		$query = $db->getQuery(TRUE);

		$query->select('r.votes, r.votes_result, r.multirating');
		$query->from('#__js_res_record AS r');
		$query->where('r.type_id = ' . $type_id);
		$query->where('r.section_id = ' . $section_id);
		if(CStatistics::hasUnPublished($section_id)) {
			$query->where('r.published = 1');
		}
		if($condition) {
			$query->where($condition);
		}

		$db->setQuery($query);
		$list = $db->loadObjectList();

		$record               = new stdClass();
		$record->user_id      = NULL;
		$record->id           = random_int(1000, time());
		$record->votes        = 0;
		$record->votes_result = 0;
		$record->multirating  = [];

		$ratings = [];
		foreach($list as $article) {
			$record->votes += $article->votes;
			$record->votes_result += $article->votes_result;

			if($article->multirating) {
				$mr = json_decode($article->multirating, true, 512, JSON_THROW_ON_ERROR);
				foreach($mr AS $key => $rating) {
					@$ratings[$key]['sum'] += $rating['sum'];
					@$ratings[$key]['num'] += $rating['num'];
					@$ratings[$key]['avg']++;
				}
			}
		}

		if($ratings) {
			$total = 0;
			foreach($ratings AS $key => $rating) {
				$ratings[$key]['sum'] = round($ratings[$key]['sum'] / $ratings[$key]['avg']);
				$total += $ratings[$key]['sum'];
				unset($ratings[$key]['avg']);
			}

			$record->votes_result = round($total / count($ratings), 0);
			$record->multirating  = $ratings;
		}
		else {
			$record->votes_result = $record->votes ? round($record->votes_result / $record->votes, 0) : 0;
		}
		$record->multirating = json_encode($record->multirating, JSON_THROW_ON_ERROR);

		$rating = RatingHelp::loadMultiratings($record, $type, $section, TRUE);

		return [
			'html'  => $rating,
			'total' => $record->votes_result,
			'multi' => json_decode($record->multirating, TRUE),
			'num'   => $record->votes
		];
	}

	public static function getField($field_id, $record, $default = null, $bykey = FALSE)
	{
		JTable::addIncludePath(JPATH_ROOT . '/components/com_cobalt/tables/');
		$field_table = JTable::getInstance('Field', 'CobaltTable');
		if($bykey) {
			$field_table->load(['key' => $field_id]);
		}
		else {
			$field_table->load($field_id);
		}

		if(!$field_table->id) {
			JError::raiseError(500, JText::_('CERRNOFILED'));
			return;
		}

		$field_path = JPATH_ROOT . "/components/com_cobalt/fields/{$field_table->field_type}/{$field_table->field_type}.php";
		if(!JFile::exists($field_path)) {
			JError::raiseError(500, JText::_('CERRNOFILEHDD'));
			return;
		}
		require_once $field_path;

		if(!is_object($record)) {
			$record = ItemsStore::getRecord($record);
		}

		if($default === NULL) {
			$values  = json_decode($record->fields, TRUE, 512, JSON_THROW_ON_ERROR);
			$default = @$values[$field_id];
		}

		$classname = 'JFormFieldC' . ucfirst($field_table->field_type);
		if(!class_exists($classname)) {
			JError::raiseError(500, JText::_('CCLASSNOTFOUND'));
			return;
		}

		return new $classname($field_table, $default);
	}

	public static function renderField($record, $field_id, $view, $default = null, $bykey = false)
	{
		if(!$record) {
			return;
		}

		if(!is_object($record) && $record > 0) {
			$record = ItemsStore::getRecord($record);
		}

		if(!$record->id) {
			return;
		}

		$fieldclass = self::getField($field_id, $record, $default, $bykey);

		$func = ($view === 'full') ? 'onRenderFull' : 'onRenderList';

		if(!method_exists($fieldclass, $func)) {
			throw new Exception(JText::_('AJAX_METHODNOTFOUND'), 500);
			return;
		}

		$type    = ItemsStore::getType($record->type_id);
		$section = ItemsStore::getSection($record->section_id);

		return $fieldclass->$func($record, $type, $section);
	}

	public static function touchRecord($record_id, $section_id = null, $type_id = null, $data = [], $fields = [], $categories = [], $tags = [])
	{
		if($record_id) {
			return self::updateRecord($record_id, $data, $fields, $categories, $tags);
		}
		return self::createRecord($data, $section_id, $type_id, $fields,$categories, $tags);
	}

	public static function updateRecord($record_id, $data, $fields = [], $categories = [], $tags = [])
	{
		$record = JTable::getInstance('Record', 'CobaltTable');

		if(is_int($record_id)) {
			$record->load($record_id);
			$record->bind($data);
		}

		if(!$record->id) {
			throw new Exception("Cobalt API: update Record: Record not found", 500);
		}

		return self::_touchRecord($record, $fields, $categories, $tags);
	}

	public static function createRecord($data, $section_id, $type_id, $fields = [], $categories = [], $tags = [])
	{
		$obj = new Registry($data);

		$obj->def('ctime', JDate::getInstance()->toSql());
		$obj->def('mtime', JDate::getInstance()->toSql());
		$obj->def('title', 'NO: ' . time());
		$obj->def('user_id', JFactory::getUser()->id);
		$obj->def('section_id', $section_id);
		$obj->def('type_id', $type_id);

		$record = JTable::getInstance('Record', 'CobaltTable');
		$record->save($obj->toArray());

		return self::_touchRecord($record, $fields, $categories, $tags);
	}

	private static function _touchRecord($record, $fields = [], $categories = [], $tags = [])
	{
		try {
			/**
			 * @return CobaltTableRecord_values
			 */
			$table  = JTable::getInstance('Record_values', 'CobaltTable');
			$type   = ItemsStore::getType($record->type_id);
			$db     = JFactory::getDbo();

			if($fields) {
				$field_ids = array_keys($fields);

				$_POST['jform']['fields'] = $fields;

				JFactory::getApplication()->setUserState('com_cobalt.edit.form.data', ['fields' => $fields]);
				$table->clean($record->id, $field_ids);

				$fileds_model = JModelLegacy::getInstance('Fields', 'CobaltModel');
				$form_fields  = $fileds_model->getFormFields($record->type_id, $record->id, false, $fields);

				$validData['id'] = $record->id;
				foreach($form_fields as $key => $field) {
					if(!in_array($field->id, $field_ids)) {
						continue;
					}
					$values = $field->onStoreValues($validData, $record);
					$values = (array) $values;

					foreach($values as $key1 => $value) {
						$table->store_value($value, $key1, $record, $field);
						$table->reset();
						$table->id = NULL;
					}
				}

				$fields_data = json_decode($record->fields, true, 512, JSON_THROW_ON_ERROR);
				$fields += $fields_data;
				$record->fields = json_encode($fields, JSON_THROW_ON_ERROR);
			}

			if($categories) {
				$table_cat      = JTable::getInstance('CobCategory', 'CobaltTable');
				$table_category = JTable::getInstance('Record_category', 'CobaltTable');

				$cids = [];
				foreach($categories as $key) {
					$table_cat->load($key);

					$array = [
						'catid'      => $key,
						'section_id' => $record->section_id,
						'record_id'  => $record->id
					];
					$table_category->load($array);

					if(!$table_category->id) {
						$array['published'] = $table_cat->published;
						$array['access']    = $table_cat->access;
						$array['id']        = NULL;

						$table_category->save($array);
					}
					else {
						$table_category->published = $table_cat->published;
						$table_category->access    = $table_cat->access;
						$table_category->store();
					}

					$cids[] = $key;
					$cat_save[$key] = $table_cat->title;

					$table_category->reset();
					$table_category->id = NULL;
				}

				if($cids) {
					$sql = 'DELETE FROM #__js_res_record_category WHERE record_id = ' . $record->id . ' AND catid NOT IN (' . implode(',', $cids) . ')';
					$db->setQuery($sql);
					$db->execute();
				}

				$record->categories = json_encode($cat_save);
			}

			if($tags) {
				$tag_table     = JTable::getInstance('Tags', 'CobaltTable');
				$taghist_table = JTable::getInstance('Taghistory', 'CobaltTable');

				$tag_ids = $tdata = $rtags = [];

				$tdata['record_id']  = $record->id;
				$tdata['section_id'] = $record->section_id;
				$tdata['user_id']    = $record->user_id;


				foreach($tags as $i => $tag) {
					if($type->params->get('general.item_tags_max', 25) && $i > $type->params->get('general.item_tags_max', 25)) {
						break;
					}

					$tag_table->reset();
					$tag_table->id = NULL;
					$tag_table->load(['tag' => $tag]);
					if(!$tag_table->id) {
						$tag_table->save(['tag' => $tag]);
					}

					$tdata['tag_id'] = $tag_ids[] = $tag_table->id;
					$taghist_table->reset();
					$taghist_table->id = NULL;
					$taghist_table->load($tdata);
					if(!$taghist_table->id) {
						$taghist_table->save($tdata);
					}
					$rtags[$tag_table->id] = $tag_table->tag;
				}

				$record->tags = count($rtags) ? json_encode($rtags) : '';

				if(!empty($tag_ids)) {
					$sql = 'DELETE FROM #__js_res_tags_history WHERE record_id = ' . $record->id . ' AND tag_id NOT IN (' . implode(',', $tag_ids) . ')';
					$db->setQuery($sql);
					$db->execute();
				}
			}
			$record->store();
			return $record->id;
		}
		catch(Exception $e) {
			return false;
		}
	}

	public static function deleteRecord($record): bool
	{
		if(!$record) {
			return false;
		}

		if(!is_object($record) && $record > 0) {
			$record = ItemsStore::getRecord($record);
		}

		if(!$record->id) {
			return false;
		}

		$record_id = $record->id;
		$record_type = $record->type_id;

		$type = ItemsStore::getType($record_type);

		if($type->params->get('audit.versioning'))
		{
			$versions = JTable::getInstance('Audit_versions', 'CobaltTable');
			$version  = $versions->snapshot($record_id, $type);
		}

		$db = JFactory::getDbo();

		$db->setQuery("DELETE FROM #__js_res_record_category WHERE record_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_record_values WHERE record_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_tags_history WHERE record_id = " . $record_id);
		$db->execute();

		$db->setQuery("SELECT * FROM #__js_res_files WHERE record_id = " . $record_id);
		$files = $db->loadObjectList('id');

		if(!empty($files) && !$type->params->get('audit.versioning'))
		{
			$field_table   = JTable::getInstance('Field', 'CobaltTable');
			$cobalt_params = JComponentHelper::getParams('com_cobalt');

			foreach($files AS $file)
			{
				$field_table->load($file->field_id);
				$field_params = new JRegistry($field_table->params);
				$subfolder    = $field_params->get('params.subfolder', $field_table->field_type);
				if(JFile::exists(JPATH_ROOT . DIRECTORY_SEPARATOR . $cobalt_params->get('general_upload') . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . $file->fullpath))
				{
					unlink(JPATH_ROOT . DIRECTORY_SEPARATOR . $cobalt_params->get('general_upload') . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR . $file->fullpath);
				}
				// deleting image field files
				elseif(JFile::exists(JPATH_ROOT . DIRECTORY_SEPARATOR . $file->fullpath))
				{
					unlink(JPATH_ROOT . DIRECTORY_SEPARATOR . $file->fullpath);
				}
			}
			$db->setQuery("DELETE FROM #__js_res_files WHERE id IN (" . implode(',', array_keys($files)) . ")");
			$db->execute();
		}

		if($files)
		{
			$db->setQuery("UPDATE #__js_res_files SET `saved` = 2 WHERE id IN (" . implode(',', array_keys($files)) . ")");
			$db->execute();
		}

		if($type->params->get('audit.versioning'))
		{
			$restore['files']     = json_encode($files);
			$restore['record_id'] = $record_id;
			$restore['dtime']     = JFactory::getDate()->toSql();

			$db->setQuery("SELECT * FROM #__js_res_comments WHERE record_id = " . $record_id);
			$restore['comments'] = json_encode($db->loadAssocList(), JSON_THROW_ON_ERROR);

			$db->setQuery("SELECT * FROM #__js_res_favorite WHERE record_id = " . $record_id);
			$restore['favorites'] = json_encode($db->loadAssocList(), JSON_THROW_ON_ERROR);

			$db->setQuery("SELECT * FROM #__js_res_hits WHERE record_id = " . $record_id);
			$restore['hits'] = json_encode($db->loadAssocList(), JSON_THROW_ON_ERROR);

			$db->setQuery("SELECT * FROM #__js_res_subscribe WHERE type = 'record' AND ref_id = " . $record_id);
			$restore['subscriptions'] = json_encode($db->loadAssocList(), JSON_THROW_ON_ERROR);

			$db->setQuery("SELECT * FROM #__js_res_vote WHERE (ref_id = " . $record_id .
				" AND ref_type = 'record') OR (ref_id IN(SELECT id FROM #__js_res_comments WHERE record_id = " . $record_id . ") AND ref_type = 'comment')");
			$restore['votes'] = json_encode($db->loadAssocList(), JSON_THROW_ON_ERROR);

			$db->setQuery("SELECT * FROM #__js_res_notifications WHERE ref_1 = " . $record_id);
			$restore['notifications'] = json_encode($db->loadAssocList(), JSON_THROW_ON_ERROR);

			$restore['type_id'] = $type->id;

			$table = JTable::getInstance('Audit_restore', 'CobaltTable');
			$table->save($restore);
		}

		$db->setQuery("DELETE FROM #__js_res_vote WHERE (ref_id = " . $record_id .
			" AND ref_type = 'record') OR (ref_id IN(SELECT id FROM #__js_res_comments WHERE record_id = " . $record_id . ") AND ref_type = 'comment')");
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_comments WHERE record_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_favorite WHERE record_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_hits WHERE record_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_subscribe WHERE type = 'record' AND ref_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_notifications WHERE ref_1 = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_record WHERE parent = 'com_cobalt' AND parent_id = " . $record_id);
		$db->execute();

		$db->setQuery("DELETE FROM #__js_res_record WHERE parent = 'com_cobalt' AND id = " . $record_id);
		$db->execute();

		ATlog::log($record, ATlog::REC_DELETE);

		JPluginHelper::importPlugin('mint');
		$dispatcher = JEventDispatcher::getInstance();
		$dispatcher->trigger('onRecordDelete', [$record]);

		return true;
	}

	/**
	 * @param int    $section_id
	 * @param string $view_what
	 * @param string $order
	 * @param array  $type_ids
	 * @param null   $user_id   No user must be NULL, otherwise 0 would be Guest
	 * @param int    $cat_id
	 * @param int    $limit
	 * @param null   $tpl
	 * @param int    $client    name of the extension that use cobalt records
	 * @param string $client_id ID of the parent cobalt record
	 * @param bool   $lang      true or false. Selects only current language records or records on any language.
	 * @param array  $ids       Ids array of the records.
	 *
	 * @return array
	 */
	public function records(int $section_id, string $view_what, string $order, $type_ids = [], $user_id = null,
	                        int $cat_id = 0, int $limit = 5, $tpl = null, int $client = 0, string $client_id = '', bool $lang = false, array $ids = [])
	{
		require_once JPATH_ROOT . '/components/com_cobalt/models/record.php';
		$content = [
			'total' => 0,
			'html'  => NULL,
			'ids'   => []
		];
		$this->section = ItemsStore::getSection($section_id);

		if(!$this->section->id) {
			JError::raiseNotice(404, 'Section not found');
			return;
		}

		$app = JFactory::getApplication();
		$this->appParams = new JRegistry([]);
		if(method_exists($app, 'getParams')) {
			$this->appParams = $app->getParams();
		}

		//$this->section->params->set('general.section_home_items', 2);
		$this->section->params->set('general.featured_first', 0);
		$this->section->params->set('general.records_mode', 0);
		if($lang) {
			$this->section->params->set('general.lang_mode', 1);
		}

		$order = explode(' ', $order);

		$back_sid   = $app->input->get('section_id');
		$back_vw    = $app->input->get('view_what');
		$back_cat   = $app->input->get('force_cat_id');
		$back_type  = $app->input->get('filter_type');
		$back_user  = $app->input->get('user_id');
		$back_uc    = $app->input->get('ucat_id');
		$back_limit = $app->input->get('limit', NULL);

		$state_limit = $app->getUserState('global.list.limit', 20);
		$state_ord   = $app->getUserState('com_cobalt.records' . $section_id . '.ordercol');
		$state_ordd  = $app->getUserState('com_cobalt.records' . $section_id . '.orderdirn');
		$app->input->set('section_id', $section_id);
		$app->input->set('view_what', $view_what);
		$app->input->set('force_cat_id', $cat_id);
		$app->input->set('user_id', $user_id);
		$app->input->set('ucat_id', 0);
		$app->input->set('limit', $limit);
		$app->input->set('api', 1);
		$app->setUserState('global.list.limit', $limit);
		$sortable = CobaltModelRecord::$sortable;

		$records                = MModelBase::getInstance('Records', 'CobaltModel');
		$records->section       = $this->section;
		$records->_filtersWhere = FALSE;
		$records->_navigation   = FALSE;
		$records->getState(NULL);

		$records->setState('records.section_id', $this->section->id);
		$records->setState('records.type', $type_ids);
		$records->_ids = $ids;
		$records->setState('records.ordering', $order[0]);
		$records->setState('records.direction', $order[1]);
		//var_dump($records);
		$items = $records->getItems();

		$ids = [];
		foreach($items as $key => $item) {
			$items[$key] = MModelBase::getInstance('Record', 'CobaltModel')->_prepareItem($item, ($client ?: 'list'));
			$ids[] = $item->id;
		}

		$this->input = $app->input;

		require_once JPATH_ROOT . '/components/com_cobalt/views/records/view.html.php';
		$view                    = new CobaltViewRecords();
		$this->total_fields_keys = $view->_fieldsSummary($items);
		$this->items             = $items;
		$this->user              = JFactory::getUser();
		$this->input             = $app->input;

		require_once JPATH_ROOT . '/components/com_cobalt/models/category.php';
		$catmodel = new CobaltModelCategory();
		$this->category = $catmodel->getEmpty();
		if($app->input->getInt('force_cat_id')) {
			$this->category = $catmodel->getItem($app->input->getInt('force_cat_id'));
		}

		$this->submission_types      = $records->getAllTypes();
		$this->total_types           = $records->getFilterTypes();
		$this->fields_keys_by_id     = $records->getKeys($this->section);
		CobaltModelRecord::$sortable = $sortable;

		$tpl = $this->_setuptemplate($tpl);

		if($items) {
			ob_start();
			include JPATH_ROOT . '/components/com_cobalt/views/records/tmpl/default_list_' . $tpl . '.php';
			$content['html'] = ob_get_contents();
			ob_end_clean();
			$content['total'] = count($items);
			$content['list']  = $items;
			$content['ids']   = $ids;
		}

		$app->input->set('section_id', $back_sid);
		$app->input->set('view_what', $back_vw);
		$app->input->set('force_cat_id', $back_cat);
		$app->input->set('user_id', $back_user);
		$app->input->set('ucat_id', $back_uc);
		$app->input->set('limit', $back_limit);
		$app->input->set('api', 0);

		$app->setUserState('global.list.limit', $state_limit);
		$app->setUserState('com_cobalt.records' . $section_id . '.ordercol', $state_ord);
		$app->setUserState('com_cobalt.records' . $section_id . '.orderdirn', $state_ordd);

		return $content;
	}

	private function _setuptemplate($tpl = null)
	{
		$dir       = JPATH_ROOT . '/components/com_cobalt/views/records/tmpl' . DIRECTORY_SEPARATOR;
		$templates = (array)$this->section->params->get('general.tmpl_list');

		$cleaned_tmpl = [];
		foreach($templates as $template) {
			$tmp            = explode('.', $template);
			$cleaned_tmpl[] = $tmp[0];
		}

		if(!$tpl && in_array($cleaned_tmpl, $templates, true)) {
			$tpl = $this->section->params->get('general.tmpl_list_default');
		}

		if(!$tpl) {
			$tpl = @$templates[0];
		}

		if(!$tpl) {
			$tpl = 'default';
		}

		$tmpl = explode('.', $tpl);
		$tmpl = $tmpl[0];

		if(!JFile::exists("{$dir}default_list_{$tmpl}.php")) {
			JError::raiseError(100, 'TMPL not found');

			return;
		}

		$this->section->params->set('general.tmpl_list', $tpl);

		$this->list_template       = $tmpl;
		$this->tmpl_params['list'] = CTmpl::prepareTemplate('default_list_', 'general.tmpl_list', $this->section->params);

		$this->section->params->set('general.tmpl_list', $templates);

		return $tmpl;
	}
}