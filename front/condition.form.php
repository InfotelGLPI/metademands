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

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}
global $DB;

$condition = new PluginMetademandsCondition();
$field = new PluginMetademandsField();
$dbu = new DbUtils();

$criteria = [
    'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']
];
if (isset($_POST['plugin_metademands_fields_id'])) {
    if ($_POST['plugin_metademands_fields_id'] == 0) {
        Session::addMessageAfterRedirect(__('You have to select a field', 'metademands'), false, ERROR);
        Html::back();
    }
    $field->getFromDB($_POST['plugin_metademands_fields_id']);
    $type = $field->fields['type'];
    $item = $field->fields['item'];
}
if (isset($_POST['add'])) {
    if (isset($_POST['check_item'])) {
        $input = [
            '_no_message_link' => '',
            'add'=> '',
            'plugin_metademands_fields_id' => $_POST['plugin_metademands_fields_id'],
            'show_logic' => $_POST['show_logic'],
            'show_condition' => $_POST['show_condition'],
            'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'],
            'items_id' => $_POST['check_value'],
            'item' => $item,
            'type' => $type,
            'order' => $_POST['order']
        ];
        if(empty($_POST['check_value'])){
            Session::addMessageAfterRedirect(__('You have to select an item', 'metademands'), false, ERROR);
            Html::back();
        }
    } else {
        $input = [
            '_no_message_link' => '',
            'add' => '',
            'plugin_metademands_fields_id' => $_POST['plugin_metademands_fields_id'],
            'show_logic' => $_POST['show_logic'],
            'show_condition' => $_POST['show_condition'],
            'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'],
            'check_value' => $_POST['check_value'],
            'item' => $item,
            'type' => $type,
            'order' => $_POST['order']
        ];
    }

    $res = $condition->add($input);
    if (!$res) {
        Session::addMessageAfterRedirect(__('Condition not added', 'metademands'), false, ERROR);
    }

    Html::back();
} else if(isset($_POST['update'])){
    if (isset($_POST['check_item'])) {
        $input = [
            '_no_message_link' => '',
            'update' => '',
            'id' => $_POST['id'],
            'plugin_metademands_fields_id' => $_POST['plugin_metademands_fields_id'],
            'show_logic' => $_POST['show_logic'],
            'show_condition' => $_POST['show_condition'],
            'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'],
            'items_id' => $_POST['check_value'],
            'item' => $item,
            'type' => $type,
            'order' => $_POST['order']
        ];
        if(empty($_POST['check_value'])){
            Session::addMessageAfterRedirect(__('You have to select an item', 'metademands'), false, ERROR);
            Html::back();
        }
    } else {
        $input = [
            '_no_message_link' => '',
            'update' => '',
            'id' => $_POST['id'],
            'plugin_metademands_fields_id' => $_POST['plugin_metademands_fields_id'],
            'show_logic' => $_POST['show_logic'],
            'show_condition' => $_POST['show_condition'],
            'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'],
            'check_value' => $_POST['check_value'],
            'item' => $item,
            'type' => $type,
            'order' => $_POST['order']
        ];
    }
    $res = $condition->update($input);
    Html::back();
    } else {
    Html::header(__('Condition', 'metademands'), '', "helpdesk", "pluginmetademandscondition");
    $condition->display(['id' => $_GET["id"]]);
    Html::footer();
}