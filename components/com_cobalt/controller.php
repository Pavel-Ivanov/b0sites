<?php
defined('_JEXEC') or die();

class CobaltController extends JControllerLegacy
{
	public function display($cachable = false, $urlparams = [])
	{
		if(!JComponentHelper::getParams('com_cobalt')->get('general_upload')) {
			JFactory::getApplication()->enqueueMessage(JText::_('CUPLOADREQ'), 'error');
			return;
		}
		$display = parent::display();
		if($this->input->get('no_html')) {
			jexit();
		}
		return $display;
	}
}
