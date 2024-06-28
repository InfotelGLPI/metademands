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
//header("Content-Type: text/html; charset=UTF-8");
header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

use Glpi\RichText\RichText;

$fields = $_POST['field'];
$tab = PluginMetademandsCondition::conditionsTab($_POST['metademands_id']);

foreach ($tab as $key => $value) {
    if (array_key_exists($value['fields_id'], $fields)) {
        if (!is_array($fields[$value['fields_id']])) {
            $tab[$key]['value'] = RichText::getTextFromHtml($fields[$value['fields_id']]);
        } else {
            $tab[$key]['value'] = $fields[$value['fields_id']];
        }
    } else {
        $tab[$key]['value'] = '';
    }
}

$checked_tab = [];
$result = '';
$predicate = '';
$actual_group = 0;

foreach ($tab as $key => $condition) {
    $result = (int)PluginMetademandsCondition::verifyCondition($condition);
    if (!empty($predicate) && $actual_group == $condition['order']) {
        $predicate .= ' ' . PluginMetademandsCondition::showPhpLogic($condition['show_logic']);
    } elseif (empty($predicate)) {
        $predicate = '(';
    } elseif ($actual_group != $condition['order']) {
        $predicate .= ") " . PluginMetademandsCondition::showPhpLogic($condition['show_logic']) . "( ";
    }
    $actual_group = $condition['order'];
    $predicate .= " $result ";
}
$predicate .= ")";

echo json_encode($predicate);
