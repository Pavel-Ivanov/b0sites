<?php
defined('_JEXEC') or die();
require_once JPATH_COMPONENT . '/library/php/helpers/helper.php';

$params              = JComponentHelper::getParams('com_cobalt');
$meta                = [];
$meta['description'] = $params->get('metadesc');
$meta['keywords']    = $params->get('metakey');
$meta['author']      = $params->get('author');
$meta['robots']      = $params->get('robots');
$meta['copyright']   = $params->get('rights');

MetaHelper::setMeta($meta);

JFactory::getApplication()->setUserState('skipers.all', []);
$controller = JControllerLegacy::getInstance('Cobalt');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
