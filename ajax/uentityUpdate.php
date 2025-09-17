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

use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "uentityUpdate.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();

$fieldEntity = new Field();
$cond       = [];

if (isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    if (!isset($_POST['field'])) {
        if ($fields = $fieldEntity->find(['type'                              => "dropdown_object",
                                          'plugin_metademands_metademands_id' => $_POST['metademands_id'],
                                          'item'                              => Entity::getType()])) {
            foreach ($fields as $f) {
                $fieldparameter = new FieldParameter();
                if ($fieldparameter->getFromDBByCrit(
                    [
                        'plugin_metademands_fields_id' => $f['id'],
                        'link_to_user' => $_POST['id_fielduser']
                    ]
                )) {
                    $id = $f['id'];
                    $_POST["field"] = "field[$id]";
                }
            }
        }
    } else {
        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']])) {
            $_POST['value'] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']];
        }

        $fieldEntity->getFromDB($_POST['fields_id']);
    }
}

////chercher les champs de la meta avec param : updatefromthisfield
$entities_id = 0;
if (isset($_POST['value']) && $_POST["value"] > 0
    && isset($_POST['id_fielduser']) && $_POST["id_fielduser"] > 0) {
    $user = new User();
    if ($user->getFromDB($_POST["value"])) {

        $entities_id = Profile_User::getUserEntitiesForRight(
            $user->getID(),
            Ticket::$rightname,
            CREATE
        );

    }
}

if (is_array($entities_id) && count($entities_id) > 0) {
    $entities_id = $entities_id[0];
}

if (is_array($entities_id) && count($entities_id) == 0) {
    $entities_id = 0;
}

if (isset($_POST['fields_id'])
    && isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']])) {
    $entities_id = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['fields_id']];
}


if (isset($_POST['entities_id']) && $_POST['entities_id'] > 0) {
    $entities_id = $_POST['entities_id'];
}

$rand = mt_rand();
$opt  = ['name'      => $_POST["field"],
         'entity'    => $_SESSION['glpiactiveentities'],
         'value'     => $entities_id,
         'condition' => $cond,
         'rand'      => $rand
];
if (isset($_POST["is_mandatory"]) && $_POST['is_mandatory'] == 1) {
    $opt['specific_tags'] = ['required' => 'required'];
}

if ($fieldEntity->fields['readonly'] == 1) {
    $opt['readonly'] = true;
    echo \Dropdown::getDropdownName("glpi_entities", $entities_id);
    echo Html::hidden($_POST["field"], ['value' => $entities_id]);
} else {
    Entity::dropdown($opt);
}

$_POST['name'] = "entity_user";
$_POST['rand'] = $rand;
Ajax::commonDropdownUpdateItem($_POST);
