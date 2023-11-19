<?php
/**
 * Cobalt by MintJoomla
 * a component for Joomla! 1.7 - 2.5 CMS (http://www.joomla.org)
 * Author Website: http://www.mintjoomla.com/
 * @copyright Copyright (C) 2012 MintJoomla (http://www.mintjoomla.com). All rights reserved.
 * @license GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die();

jimport('joomla.application.component.controllerform');

class CobaltControllerModerator extends JControllerForm
{

	public function __construct($config = array())
	{
		parent::__construct($config);
		
		if(!$this->input)
		{
			$this->input = JFactory::getApplication()->input;
		}
	}
	protected function allowSave($data = array())
	{
		return TRUE;
	}

	protected function allowAdd($data = array())
	{
		return true;
		$user = JFactory::getUser();
		$allow = $user->authorise('core.create', 'com_cobalt.moderator');

		if($allow === null)
		{
			return parent::allowAdd($data);
		}
		else
		{
			return $allow;
		}
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
		return true;
		$recordId = (int)isset($data[$key]) ? $data[$key] : 0;
		$asset = 'com_cobalt.moderator.' . $recordId;

		// Check general edit permission first.
		if(JFactory::getUser()->authorise('core.edit', $asset))
		{
			return true;
		}
	}

	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{

		$tmpl = $this->input->getCmd('tmpl');
		$secton_id = $this->input->getCmd('section_id');
		$itemId = $this->input->getInt('Itemid');
		$return = $this->getReturnPage();

		$append = '';

		if($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		if($secton_id)
		{
			$append .= '&section_id=' . $secton_id;
		}

		if($recordId)
		{
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		if($itemId)
		{
			$append .= '&Itemid=' . $itemId;
		}

		if($return)
		{
			$append .= '&return=' . base64_encode($return);
		}

		return $append;
	}

	protected function getRedirectToListAppend($recordId = null, $urlVar = 'id')
	{


		$tmpl = $this->input->getCmd('tmpl');
		$secton_id = $this->input->getCmd('section_id');
		$itemId = $this->input->getInt('Itemid');

		$append = '';

		if($tmpl)
		{
			$append .= '&tmpl=' . $tmpl;
		}

		if($secton_id)
		{
			$append .= '&section_id=' . $secton_id;
		}

		if($itemId)
		{
			$append .= '&Itemid=' . $itemId;
		}

		return $append;
	}

	protected function getReturnPage()
	{


		$return = $this->input->getBase64('return');

		if(! empty($return) || JUri::isInternal(CobaltFilter::base64($return)))
		{
			return CobaltFilter::base64($return);
		}

		return false;
	}

	protected function postSaveHook(JModelLegacy $model, $validData = array())
	{


		if($this->input->getCmd('task') == 'save')
		{
			$return = Url::get_back('return');
			if(! JURI::isInternal($return))
			{
				$return = '';
			}

			if($return)
			{
				$this->setRedirect($return);
				return TRUE;
			}
		}
	}

}