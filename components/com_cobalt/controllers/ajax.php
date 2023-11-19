<?php
defined('_JEXEC') or die();

jimport('joomla.application.component.controlleradmin');

class CobaltControllerAjax extends JControllerAdmin
{

	public function __construct($config = array())
	{
		parent::__construct($config);

		if(!$this->input)
		{
			$this->input = JFactory::getApplication()->input;
		}
	}

	public function usermention()
	{
		$db    = JFactory::getDbo();
		$input = JFactory::getApplication()->input;

		if($input->get('id'))
		{
			$db->setQuery('SELECT params FROM `#__js_res_fields` WHERE id = ' . $input->getInt('id'));
			$params = new JRegistry($db->loadResult());
			if(!$params->get('params.mention'))
			{
				return;
			}
		}

		$query = "SELECT name, username, id FROM #__users WHERE username LIKE '%" . $db->escape($input->get('q')) . "%' OR name LIKE '%" . $db->escape(JFactory::getApplication()->input->get('q')) . "%'";
		$db->setQuery($query);
		$list = $db->loadObjectList();

		$out = array();
		foreach($list AS $user)
		{

			$out[] = $user;
		}

		AjaxHelper::send($out);
	}

	public function icons()
	{
		$dir = JPATH_ROOT . '/' . $this->input->getPath('dir');

		$out = array();
		if(is_dir($dir))
		{
			if($dh = opendir($dir))
			{
				while(($file = readdir($dh)) !== FALSE)
				{
					$ext = strtolower(substr($file, strrpos($file, '.') + 1));
					if($ext == 'png' || $ext == 'gif')
					{
						$out[] = $file;
					}
				}
				closedir($dh);
			}
		}

		AjaxHelper::send($out);
	}

	public function mainJS()
	{
		//$this->input->set('view', 'assets');
		//$this->input->set('layout', 'mainjs');

		header('content-type: application/javascript');

		include JPATH_ROOT . '/components/com_cobalt/library/js/main.js';

		JFactory::getApplication()->close();
	}

	public function trackcomment()
	{
		$record_id = $this->input->getInt('record_id');

		if(!$record_id)
		{
			return;
		}

		$record = ItemsStore::getRecord($record_id);

		CEventsHelper::notify('record', CEventsHelper::_COMMENT_NEW, $record->id, $record->section_id, 0, 0, 0, array());

		$user = JFactory::getUser();
		if($user->get('id'))
		{
			CSubscriptionsHelper::subscribe_record($record);
		}
		JFactory::getApplication()->close();
	}

	public function checkuser()
	{
		$user = $this->input->get('user');

		if(!$user)
		{
			AjaxHelper::error(JText::_('AJAX_ENTERUSERNAMEOREMAILORID'));
		}

		$user_id = 0;
		if(preg_match("/^[0-9]*$/iU", $user))
		{
			$user_id = JFactory::getUser($user)->get('id');
		}

		$db = JFactory::getDbo();

		if(JMailHelper::isEmailAddress($user))
		{
			$db->setQuery("SELECT id FROM #__users WHERE email = " . $db->quote($user));
			$user_id = $db->loadResult();
		}

		if(!$user_id)
		{
			$db->setQuery("SELECT id FROM #__users WHERE username = " . $db->quote($user));
			$user_id = $db->loadResult();
		}

		if(!$user_id)
		{
			$db->setQuery("SELECT id FROM #__users WHERE name = " . $db->quote($user));
			$user_id = $db->loadResult();
		}

		if(!$user_id)
		{
			AjaxHelper::error(JText::_('AJAX_USERNOTFOUND'));
		}

		AjaxHelper::send($user_id);
	}

	public function status()
	{
		$order = JTable::getInstance('Sales', 'CobaltTable');
		$order->load($this->input->getInt('order_id'));

		if(!$order->id)
		{
			AjaxHelper::error(JText::_('AJAX_CANNOTLOADORDER'));
		}

		$record = ItemsStore::getRecord($order->record_id);

		$user         = JFactory::getUser();
		$orders_model = JModelLegacy::getInstance('Orders', 'CobaltModel');

		if(!in_array($order->section_id, MECAccess::allowChangeSaleStatus($user)) && !$orders_model->isSuperUser($user->get('id')) && !($user->get('id') == $record->user_id))
		{
			AjaxHelper::error(JText::_('AJAX_CANNOTCHANGESTATUS'));
		}

		$order->status = $this->input->getInt('status');
		$order->store();

		JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . 'tables' . DIRECTORY_SEPARATOR . 'field.php');
		$field = JTable::getInstance('Field', 'CobaltTable');
		$field->load($order->field_id);
		$field->params = new JRegistry($field->params);

		if($field->params->get('params.order_updated'))
		{
			CEventsHelper::notify('record', 'order_updated', $order->record_id, $order->section_id, 0, 0, $order->field_id, $order, 2, $order->user_id);
		}

		AjaxHelper::send(NULL);
	}

	public function removeucicon()
	{
		$file     = $this->input->get('file');
		$id       = $this->input->get('id');
		$user     = JFactory::getUser();
		$fullpath = JPATH_ROOT . '/images/usercategories/' . $user->get('id') . DIRECTORY_SEPARATOR . $file;
		if(JFile::exists($fullpath))
		{
			if(JFile::delete($fullpath))
			{
				$db = JFactory::getDbo();
				$db->setQuery('UPDATE #__js_res_category_user SET icon = "" WHERE id = ' . $id);
				$db->query();
				AjaxHelper::send(1);
			}
		}
		else
		{
			AjaxHelper::error(JText::_('CFILENOTEXISTS'));
		}
	}

	public function category_children()
	{
		$db      = JFactory::getDbo();
		$parent  = $this->input->get('parent');
		$section = $this->input->get('section');

		$db->setQuery(
			"SELECT
				c.id,
				c.title,
				CONCAT(s.name, '/', c.path) AS path,
				c.params,
				c.section_id,
				s.name as section_name,
				(SELECT count(id)
					FROM `#__js_res_categories`
					WHERE parent_id = c.id
				)  as children
			FROM `#__js_res_categories` AS c
			LEFT JOIN `#__js_res_sections` AS s ON s.id = c.section_id
			WHERE c.published = 1
			AND c.section_id = {$section}
			AND c.parent_id = {$parent}
			ORDER BY c.lft ASC
		");

		$categories = $db->loadObjectList();

		foreach($categories as &$cat)
		{
			$cat->params = json_decode($cat->params);
			$cat->title  = htmlentities($cat->title, ENT_QUOTES, 'UTF-8');
			$cat->path   = htmlentities($cat->path, ENT_QUOTES, 'UTF-8');
		}

		echo json_encode($categories);
		JFactory::getApplication()->close();
	}

	public function category_childs()
	{
		require_once JPATH_ROOT . '/components/com_cobalt/models/category.php';
		$cat_model  = new CobaltModelCategory();
		$cats_model = JModelLegacy::getInstance('Categories', 'CobaltModel');

		$db                    = JFactory::getDbo();
		$level                 = $this->input->getInt('level');
		$category              = $cat_model->getItem($this->input->getInt('cat_id'));
		$cats_model->section   = $category->section_id;
		$cats_model->parent_id = $category->id;
		$cats_model->order     = $this->input->get('order', 'c.lft ASC');
		$cats_model->levels    = 1;
		$cats_model->all       = 0;
		$cats_model->nums      = 1;
		$categories            = $cats_model->getItems();

		AjaxHelper::send($categories);
	}

	public function category_filter()
	{
		$section_id = $this->input->getInt('section_id');
		$empty_cats = $this->input->getInt('empty_cats');

		$db = JFactory::getDbo();

		$sql = $db->getQuery(TRUE);
		$sql->select('id, title, path, image, description, params');
		$sql->from('#__js_res_categories');
		$sql->where('published = 1');
		$sql->where('section_id = ' . $section_id);
		$db->setQuery($sql);

		$categories = $db->loadObjectList();

		if(!$empty_cats)
		{
			$section = ItemsStore::getSection($section_id);
			$user    = JFactory::getUser();

			$sql = $db->getQuery(TRUE);
			$sql->select('count(rc.catid) as num, rc.catid');
			$sql->from('#__js_res_record_category AS rc');
			$sql->where("rc.section_id = {$section->id}");
			$sql->group('rc.catid');

			if($section->params->get('general.cat_mode'))
			{
				$sql2 = $db->getQuery(TRUE);
				$sql2->select('r.id');
				$sql2->from('#__js_res_record AS r');
				if(CStatistics::hasUnPublished($section->id))
				{
					$sql2->where('r.published = 1');
				}
				$sql2->where('r.hidden = 0');
				$sql2->where('r.section_id = ' . $section->id);

				if(!in_array($section->params->get('general.show_restrict'), $user->getAuthorisedViewLevels()) && !MECAccess::allowRestricted($user, $section))
				{
					$sql2->where("(r.access IN(" . implode(',', $user->getAuthorisedViewLevels()) . ") OR r.user_id = " . $user->get('id') . ")");
				}

				if(!in_array($section->params->get('general.show_future_records'), $user->getAuthorisedViewLevels()))
				{
					$sql2->where("r.ctime < " . $db->quote(JFactory::getDate()->toSql()));
				}

				if(!in_array($section->params->get('general.show_past_records'), $user->getAuthorisedViewLevels()))
				{
					$sql2->where("(r.extime = '0000-00-00 00:00:00' OR r.extime > '" . JFactory::getDate()->toSql() . "')");
				}

				if(!in_array($section->params->get('general.show_children'), $user->getAuthorisedViewLevels()))
				{
					$sql2->where("r.parent_id = 0");
				}

				if($section->params->get('general.lang_mode'))
				{
					$sql2->where('r.langs = ' . $db->quote(JFactory::getLanguage()->getTag()));
				}

				$sql->where('rc.record_id IN (' . $sql2 . ')');
			}
			$db->setQuery($sql);

			$nums = $db->loadAssocList("catid", "num");
		}

		foreach($categories as $i => $cat)
		{
			if(!$empty_cats && !isset($nums[$cat->id]))
			{
				unset($categories[$i]);
				continue;
			}
			$params = new JRegistry($cat->params);
			if(!$params->get('submission'))
			{
				continue;
			}

			$html = '';

			if($cat->image)
			{
				$html .= '<img class="pull-left img-polaroid" src="' . JURI::root(TRUE) . '/' . $cat->image . '" style="max-width:50px;" hspace="5" align="absmiddle">';
			}
			$html .= $cat->title;
			$html .= '<br><small>' . $cat->path . '</small>';

			if($cat->description)
			{
				$html .= '<br><small>' . substr(trim(strip_tags($cat->description)), 0, 200) . '</small>';
			}
			$html .= '<div class="clearfix"></div>';
			$out[] = array(
				$cat->id,
				$html,
				$cat->title
			);
		}

		AjaxHelper::send($out);
	}

	public function users_filter()
	{
		$section_id = $this->input->getInt('section_id');
		$section    = ItemsStore::getSection($section_id);

		$db = JFactory::getDbo();

		$sql = $db->getQuery(TRUE);
		$sql->select('id, ' . $section->params->get('personalize.author_mode', 'username') . ' AS plain');
		$sql->from('#__users');
		$sql->where("id IN(SELECT user_id FROM #__js_res_record WHERE section_id = {$section->id})");

		$db->setQuery($sql);

		$users = $db->loadObjectList();

		foreach($users as $i => $user)
		{
			$out[] = array(
				$user->id,
				'<img src="' . CCommunityHelper::getAvatar($user->id, 24, 24) . '" /> ' . $user->plain,
				$user->plain
			);
		}

		AjaxHelper::send($out);
	}

	public function category_records()
	{
		$catid = $this->input->getInt('cat_id');
		$limit = $this->input->getInt('rec_limit');

		$db = JFactory::getDbo();

		$sql = $db->getQuery(TRUE);
		$sql->select('*');
		$sql->from('#__js_res_record');
		$sql->where('published = 1');
		$sql->where('hidden = 0');
		$sql->where("ctime < " . $db->quote(JFactory::getDate()->toSql()));
		$sql->where("(extime = '0000-00-00 00:00:00' OR extime > '" . JFactory::getDate()->toSql() . "')");
		$sql->where("id IN (SELECT record_id FROM #__js_res_record_category WHERE catid = '{$catid}')");
		$db->setQuery($sql, 0, ($limit ? $limit + 1 : 0));
		$items = array();
		if($recs = $db->loadObjectList())
		{
			foreach($recs as $rec)
			{
				$rec->url = Url::record($rec);
				$items[]  = $rec;
			}
		}
		AjaxHelper::send($items);
	}

	public function field_call()
	{
		$id = $this->input->getInt('field_id');
		if(!$id)
		{
			AjaxHelper::error(JText::_('AJAX_NOFIELDID'));
		}

		JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . 'tables' . DIRECTORY_SEPARATOR . 'field.php');
		$field_table = JTable::getInstance('Field', 'CobaltTable');
		$field_table->load($id);

		$field     = $field_table->field_type;
		$func      = $this->input->get('func');
		$record_id = $this->input->getInt('record_id');
		$params    = $_REQUEST;

		if(!$field)
		{
			AjaxHelper::error(JText::_('AJAX_NOFIELDNAME'));
		}

		if(!$func)
		{
			AjaxHelper::error(JText::_('AJAX_NOFUNCNAME'));
		}

		$field_path = JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_cobalt' . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR . $field . DIRECTORY_SEPARATOR . $field . '.php';
		$classname  = 'JFormFieldC' . ucfirst($field);

		if($field == 'upload')
		{
			$field_path = JPATH_ROOT . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_cobalt' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR . 'cobaltupload.php';
			$classname  = 'CFormFieldUpload';
		}

		if(!JFile::exists($field_path))
		{
			AjaxHelper::error(JText::_('AJAX_FIELDNOTFOUND' . $id));
		}

		require_once $field_path;

		$default = array();
		$record  = NULL;
		if($record_id)
		{
			$record_model = JModelLegacy::getInstance('Record', 'CobaltModel');
			$record       = $record_model->getItem($record_id);
			$values       = json_decode($record->fields, TRUE);
			$default      = @$values[$id];
		}

		if(!class_exists($classname))
		{
			AjaxHelper::error(JText::_('CCLASSNOTFOUND'));
		}

		$fieldclass = new $classname($field_table, $default);

		if(!method_exists($fieldclass, $func))
		{
			AjaxHelper::error(JText::_('AJAX_METHODNOTFOUND'));
		}

		$result = $fieldclass->$func($params, $record);

		if($fieldclass->getErrors())
		{
			AjaxHelper::error(JText::_($fieldclass->getError()));
		}

		AjaxHelper::send($result);

		JFactory::getApplication()->close();
	}

	public function mark_notification()
	{
		$id         = $this->input->get('id');
		$section_id = $this->input->getInt('section_id');
		$client     = $this->input->getInt('client', 'module');

		if($id == 'all')
		{
			$user  = JFactory::getUser();
			$db    = JFactory::getDbo();
			$query = $db->getQuery(TRUE);
			$query->update('#__js_res_notifications');
			if($client == 'component')
			{
				$query->set('state_new = 0');
			}

			$query->set('notified = 1');
			$query->where('user_id = ' . $user->id);
			if($section_id)
			{
				$query->where('ref_2 = ' . $section_id);
			}
			$db->setQuery($query);
			$db->execute();
			if($db->getErrors())
			{
				AjaxHelper::error(JText::_($db->getError()));
			}
		}
		else
		{

			$table = JTable::getInstance('Notificat', 'CobaltTable');
			$table->load($id);

			if($client == 'component')
			{
				$table->state_new = 0;
			}

			$table->notified = 1;
			$table->store();
			if($table->getErrors())
			{
				AjaxHelper::error(JText::_($table->getError()));
			}
		}

		AjaxHelper::send(0);
	}

	public function remove_notification()
	{
		$id         = $this->input->getVar('id');
		$section_id = $this->input->getInt('section_id');

		$user  = JFactory::getUser();
		$db    = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->delete();
		$query->from('#__js_res_notifications');
		$query->where('user_id = ' . $user->id);
		if($section_id)
		{
			$query->where('ref_2 = ' . $section_id);
		}
		if($id != 'all')
		{
			if(!is_array($id))
			{
				settype($id, 'array');
			}
			$query->where('id IN (' . implode(', ', $id) . ')');
		}
		$db->setQuery($query);
		$db->query();
		if($db->getErrors())
		{
			AjaxHelper::error(JText::_($db->getError()));
		}

		AjaxHelper::send(0);
	}

	public function remove_notification_by()
	{
		$type = $this->input->getVar('type');
		$list = $this->input->getVar('list');
		if(!count($list) && $type != 'read')
		{
			echo json_encode(
				array(
					'success' => '0'
				)
			);

			return;
		}
		$section_id = $this->input->getInt('section_id');
		$db         = JFactory::getDbo();
		if($type == 'selected')
		{
			$ids = $list;
		}
		else
		{
			$user  = JFactory::getUser();
			$query = $db->getQuery(TRUE);
			$query->select(' id ');
			$query->from('#__js_res_notifications');
			$query->where(' user_id = ' . $user->id);
			if($section_id)
			{
				$query->where('ref_2 = ' . $section_id);
			}

			switch($type)
			{
				case 'event':
					$query->where("type IN ('" . implode("', '", $list) . "')");
					break;
				case 'record':
					$query->where("ref_1 IN (" . implode(",", $list) . ")");
					break;
				case 'section':
					$query->where("ref_2 IN (" . implode(",", $list) . ")");
					break;
				case 'eventer':
					$query->where("eventer IN (" . implode(",", $list) . ")");
					break;
				case 'read':
					$query->where("state_new = 0");
					break;
			}

			$db->setQuery($query);
			$ids = $db->loadColumn();
		}

		if(!count($ids))
		{
			return;
		}

		$query = $db->getQuery(TRUE);
		$query->delete();
		$query->from('#__js_res_notifications');
		$query->where("id IN (" . implode(", ", $ids) . ")");
		$db->setQuery($query);
		$db->query();
		if($db->getErrors())
		{
			AjaxHelper::error(JText::_($db->getError()));

			return;
		}

		AjaxHelper::send($ids);
	}

	public function get_notifications()
	{
		$ids        = $this->input->get('exist', array(0), 'array');
		$section_id = $this->input->getVar('section_id');
		$user       = JFactory::getUser();
		$db         = JFactory::getDbo();
		$query      = $db->getQuery(TRUE);

		$query->select('*');
		$query->from('#__js_res_notifications');
		$query->where('user_id = ' . $user->id);
		$query->where('notified = 0');
		if($section_id)
		{
			if(!is_array($section_id))
			{
				settype($section_id, 'array');
			}
			$query->where('ref_2 IN (' . implode(', ', $section_id) . ')');
		}
		if(count($ids))
		{
			$query->where('id NOT IN (' . implode(', ', $ids) . ')');
		}
		$query->order('ctime DESC');

		$query .= " LIMIT 0, " . $this->input->get('notiflimit', 5);

		$db->setQuery($query);
		$result = $db->loadObjectList();

		if($db->getError())
		{
			AjaxHelper::error(JText::_($db->getError()));

			return " ";
		}
		$list = array();
		$k    = 0;
		foreach($result as $i => $item)
		{
			$list[$i]       = new stdClass();
			$list[$i]->id   = $item->id;
			$list[$i]->html = CEventsHelper::get_notification($item, FALSE);
		}

		AjaxHelper::send($list);
	}

	public function remove_log()
	{
		$id = $this->input->getVar('id');

		$db    = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->delete();
		$query->from('#__js_res_audit_log');
		if(!is_array($id))
		{
			settype($id, 'array');
		}
		$query->where('id IN (' . implode(', ', $id) . ')');

		$db->setQuery($query);
		$db->query();
		if($db->getErrors())
		{
			echo AjaxHelper::error(JText::_($db->getError()));
		}

		AjaxHelper::send(0);
	}
}
