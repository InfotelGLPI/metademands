<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Bootstrap GLPI's test environment (DB connection, session, etc.)
require_once dirname(__DIR__, 3) . '/tests/bootstrap.php';

// Register metademands classes into the already-loaded Composer autoloader
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
$loader->addPsr4('GlpiPlugin\\Metademands\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Metademands\\Tests\\', dirname(__DIR__) . '/tests/');

// Install plugin tables in the test DB if they do not yet exist
if (!defined('PLUGIN_METADEMANDS_VERSION')) {
    require_once dirname(__DIR__) . '/setup.php';
}
global $DB;
if (!$DB->tableExists('glpi_plugin_metademands_metademands')) {
    require_once dirname(__DIR__) . '/hook.php';
    plugin_metademands_install();
}
