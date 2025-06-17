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

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "ugroupUpdate.php")) {
    include('../../../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

$fieldGroup = new PluginMetademandsField();
$fieldparameter = new PluginMetademandsFieldParameter();
$cond       = [];

if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    if (!isset($_POST['field'])) {

        if ($fields = $fieldGroup->find(['type'                              => "dropdown_object",
                                          'plugin_metademands_metademands_id' => $_POST['metademands_id'],
                                          'item'                              => Group::getType()])) {
            foreach ($fields as $field) {
                if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field['id'],
                    'link_to_user' => $_POST['id_fielduser']])) {
                    $id             = $field['id'];
                    $_POST["field"] = "field[$id]";
                    $fieldGroup->getFromDB($fieldparameter->fields['plugin_metademands_fields_id']);
                }
            }

        }
    } else {
        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']])) {
            $_POST['value'] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']];
        }

        $fieldGroup->getFromDB($_POST['fields_id']);
        $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' =>$_POST['fields_id']]);

    }

    if (!empty($fieldparameter->fields['custom']) && isset($_POST["value"])) {
        $condition       = getEntitiesRestrictCriteria(Group::getTable(), '', '', true);
        $group_user_data = Group_User::getUserGroups($_POST["value"], $condition);

        $requester_groups = [];
        foreach ($group_user_data as $groups) {
            $requester_groups[] = $groups['id'];
        }

        $options = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['custom']);

        foreach ($options as $type_group => $values) {
            if ($type_group != 'user_group') {
                $cond[$type_group] = $values;
            } else {
                if (count($requester_groups) > 0) {
                    $cond["`glpi_groups`.`id`"] = $requester_groups;
                }
            }
        }
    }
}
unset($cond['user_group']);
//chercher les champs de la meta avec param : updatefromthisfield
$groups_id = 0;
if (isset($_POST['value']) && !is_array($_POST["value"]) && $_POST["value"] > 0
    && isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    $user = new User();
    if ($user->getFromDB($_POST["value"])) {
        $groups_id = PluginMetademandsField::getUserGroup(
            $_SESSION['glpiactiveentities'],
            $_POST["value"],
            $cond,
            true
        );
    }
}

if (isset($_POST['fields_id'])
    && isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']])) {
    $groups_id = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']];
}


if (isset($_POST['groups_id']) && $_POST['groups_id'] > 0) {
    $groups_id = $_POST['groups_id'];
}

if (is_array($groups_id)) {
    $groups_id = 0;
}

$rand = mt_rand();
$opt  = ['name'      => $_POST["field"],
         'entity'    => $_SESSION['glpiactiveentities'],
         'value'     => $groups_id,
         'condition' => $cond,
         'rand'      => $rand,
    'width' => '200px'
];

$fieldparameter            = new PluginMetademandsFieldParameter();

if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $fieldGroup->fields['id']])) {
    if ($fieldparameter->fields["is_mandatory"] && $fieldparameter->fields['is_mandatory'] == 1) {
        $opt['specific_tags'] = ['required' => 'required'];
    }
}


Group::dropdown($opt);

$_POST['name'] = "group_user";
$_POST['rand'] = $rand;
Ajax::commonDropdownUpdateItem($_POST);
