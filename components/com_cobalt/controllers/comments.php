<?php
/**
 * Cobalt by MintJoomla
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: http://www.mintjoomla.com/
 * @copyright Copyright (C) 2012 MintJoomla (http://www.mintjoomla.com). All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

class CobaltControllerComments extends JControllerAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		if(!$this->input)
		{
			$this->input = JFactory::getApplication()->input;
		}
	}
	public function getModel($type = 'Comment', $prefix = 'CobaltModel', $config = array())
	{
		return JModelLegacy::getInstance($type, $prefix, $config);
	}
	
	public function delete()
	{
		
		$cid	= $this->input->get('cid', array(), '', 'array');
		JArrayHelper::toInteger($cid);

		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_cobalt/tables');
		$comment = JTable::getInstance('Cobcomments', 'CobaltTable');
		$comment->load($cid[0]);
		
		parent::delete();
		
		$model_record = JModelLegacy::getInstance('Record', 'CobaltModel');
		$model_record->onComment($this->input->get('record_id'), get_class_vars($comment));
		
		$record = JTable::getInstance('Record', 'CobaltTable');
		$record->load($this->input->getInt('record_id'));
		
		$url = 'index.php?option=com_cobalt&view=record';
		$url .= $this->getRedirectToListAppend();
		$this->setRedirect($url);
		if($comment->user_id)
		{
			
			$data = $comment->getProperties();
			$data['record'] = $record->getProperties();
			
			CEventsHelper::notify('record', CEventsHelper::_COMMENT_DELETED, $this->input->get('record_id'), $this->input->get('section_id'), 0, 0, 0, $data, 2, $comment->user_id);
		}
		ATlog::log($record, ATlog::COM_DELET, $comment->id);
	}
	
	public function publish()
	{
	    parent::publish();

	    $url = 'index.php?option=com_cobalt&view=record';
		$url .= $this->getRedirectToListAppend();
		$this->setRedirect(JRoute::_($url, FALSE));
		
		$task 	= $this->getTask();
		
		$cid	= $this->input->get('cid', array(), '', 'array');
		JArrayHelper::toInteger($cid);
		
		$comment = JTable::getInstance('Cobcomments', 'CobaltTable');
		$comment->load($cid[0]);
		
		$record = JTable::getInstance('Record', 'CobaltTable');
		$record->load($comment->record_id);
			
		if($comment->user_id)
		{
			$data = $comment->getProperties();
			$data['record'] = $record->getProperties();
			
			CEventsHelper::notify('record', ($task == 'publish' ? CEventsHelper::_COMMENT_APPROVED : CEventsHelper::_COMMENT_UNPUBLISHED), $this->input->get('record_id'), $this->input->get('section_id'), 0, ($task == 'publish' ? $comment->id  : 0), 0, $data, 2, $comment->user_id);
		}
		
		ATlog::log($record, ($task == 'publish' ? ATlog::COM_PUBLISHED : ATlog::COM_UNPUBLISHED), $comment->id);

		$record->index();
		
	}
	
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		return $this->getRedirectToListAppend($recordId = null, $urlVar = 'id');
	}
	
	protected function getRedirectToListAppend($recordId = null, $urlVar = 'id')
	{
		
		
		$tmpl = $this->input->getCmd('tmpl');
		$record_id	= $this->input->get('record_id');
		$section_id	= $this->input->get('section_id');
		$cat_id	= $this->input->get('cat_id');
		$ucat_id	= $this->input->get('ucat_id');
		
		$append		= '';

		if ($tmpl) {
			$append .= '&tmpl='.$tmpl;
		}
		
		if ($record_id) {
			$append .= '&id='.$record_id;
		}
		if ($section_id) {
			$append .= '&section_id='.$section_id;
		}
		if ($cat_id) {
			$append .= '&cat_id='.$cat_id;
		}
		if ($ucat_id) {
			$append .= '&ucat_id='.$ucat_id;
		}
		
		return $append;
	}
	
}