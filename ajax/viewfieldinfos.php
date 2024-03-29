<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

//var_dump($_POST);

$field = new PluginMetademandsField();
if ($field->getFromDB($_POST["fields_id"])) {

    echo "<br><table class='tab_cadre' width='100%'>";
    echo "<tr class='tab_bg_1'>";
    echo "<th colspan='2'>" . __('Field informations', 'metademands') . "</th>";
    echo "</tr>";

    echo "<tr class='tab_bg_1'>";
    echo "<td>" . __('Type') . "</td>";
    echo "<td>";
    echo PluginMetademandsField::getFieldTypesName($field->fields["type"]);
    echo "</td>";
    echo "</tr>";

    echo "<tr class='tab_bg_1'>";
    echo "<td>" . __('Example', 'metademands') . "</td>";
    echo "<td>";
    echo PluginMetademandsField::getFieldInput([], $field->fields, false, 0, 0, false, "");
    echo "</td>";
    echo "</tr>";

    echo "</table>";
}

Html::ajaxFooter();
