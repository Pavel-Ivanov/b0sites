<?php
defined('_JEXEC') or die();
jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

$params = JComponentHelper::getParams('com_cobalt');
$router = $params->get('sef_router', 'main_router.php');
 
$lang = JFactory::getLanguage();
$lang->load('com_cobalt');
$lang->load();

include_once JPATH_ROOT. DIRECTORY_SEPARATOR .'components'. DIRECTORY_SEPARATOR .'com_cobalt'. DIRECTORY_SEPARATOR .'routers'. DIRECTORY_SEPARATOR .$router;
