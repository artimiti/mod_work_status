<?php
defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

use Joomla\CMS\Helper\ModuleHelper;

require ModuleHelper::getLayoutPath('mod_work_status', $params->get('layout', 'default'));