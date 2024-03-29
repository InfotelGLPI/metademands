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

Session::checkRight("plugin_metademands", UPDATE);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

if (isset($_POST["purge_emptyoptions"])) {
    $itil = $_POST["id"];
    $field = new PluginMetademandsFieldOption();
    $field->check(-1, DELETE, $_POST);
    $field->delete($_POST, 1);
    Session::addMessageAfterRedirect(__('Empty option has been deleted', 'metademands'));
    Html::back();
} else if (isset($_POST["fix_emptycustomvalues"])) {
    $itil = $_POST["id"];
    $field = new PluginMetademandsFieldParameter();
    $field->getfromDB($itil);
    $test = json_decode($field->fields['custom_values'], true);
    $start_one = array_combine(range(1, count($test)), array_values($test));
    $input['custom_values'] = json_encode($start_one);
    $input['id'] = $itil;
    $field->update($input, 1);
    Session::addMessageAfterRedirect(__('Empty custom value has been cleaned', 'metademands'));
    Html::back();
} else {
    Html::displayRightError();
}
