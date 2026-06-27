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

use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;

$meta = new Metademand();

if ($meta->canView() || Session::haveRight("config", UPDATE)) {

    Html::header(Metademand::getTypeName(2), '', "helpdesk", Menu::class);

    $meta->listOfTemplates(PLUGIN_METADEMANDS_WEBDIR . "/front/metademand.form.php", $_GET["add"]);

    Html::footer();
}
