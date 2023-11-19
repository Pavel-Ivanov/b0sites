<?php
/**
 * Cobalt by MintJoomla
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: http://www.mintjoomla.com/
 * @copyright Copyright (C) 2012 MintJoomla (http://www.mintjoomla.com). All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

class CobaltControllerAuditlog extends JControllerForm
{
	protected $view_item = 'auditlog';
	protected $view_list = 'auditlog';

	public function __construct($config = array())
	{
		parent::__construct($config);
		
		if(!$this->input)
		{
			$this->input = JFactory::getApplication()->input;
		}
	}
	public function reset()
	{
		$app = JFactory::getApplication();

		$app->setUserState('com_cobalt.auditlog.filter.search', '');
		$app->setUserState('com_cobalt.auditlog.section_id', '');
		$app->setUserState('com_cobalt.auditlog.type_id', '');
		$app->setUserState('com_cobalt.auditlog.user_id', '');
		$app->setUserState('com_cobalt.auditlog.event_id', '');
		$app->setUserState('com_cobalt.auditlog.fcs', '');
		$app->setUserState('com_cobalt.auditlog.fce', '');

		$this->setRedirect(JRoute::_('index.php?option=com_cobalt&view=auditlog', false));
	}
}
