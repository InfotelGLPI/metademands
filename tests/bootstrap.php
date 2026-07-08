<?php

define('GLPI_ROOT', dirname(__DIR__, 3));
define('GLPI_LOG_DIR', GLPI_ROOT . '/files/_logs');

define('TU_USER', 'glpi');
define('TU_PASS', 'glpi');

require GLPI_ROOT . '/inc/includes.php';

require_once __DIR__ . '/MetademandsTestCase.php';
