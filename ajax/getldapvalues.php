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
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Metademands\Fields\Ldapdropdown;


if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

// Check if plugin is activated...
if (!Plugin::isPluginActive('metademands')) {
    throw new NotFoundHttpException();
}

Session::checkLoginUser();

echo Ldapdropdown::getDropdownValue($_POST);
