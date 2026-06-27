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

use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;

Session::checkLoginUser();

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$field = new FieldOption();

if (isset($_POST["add"]) || isset($_POST["update"]) || isset($_POST["purge"])) {
    if (isset($_POST['check_type_value'])
        && $_POST['check_type_value'] == 2) {
        $_POST['check_value_regex'] = $_POST['check_value'];
        $_POST['check_value'] = 0;
    }
    if (isset($_POST["add"])) {

        if (isset($_POST["childs_blocks"])) {
            $_POST["childs_blocks"] = json_encode($_POST["childs_blocks"]);
        } else {
            $_POST["childs_blocks"] = json_encode([]);
        }

        if (isset($_POST["assign_tech_group"]) && $_POST['check_type_value'] != 2) {
            $_POST["assign_tech_group"] = json_encode($_POST["assign_tech_group"]);
        } else {
            $_POST["assign_tech_group"] = json_encode([]);
        }
//   // Check update rights for fields
        $field->check(-1, CREATE, $_POST);
        $field->add($_POST);
        Html::back();

    } else if (isset($_POST["update"])) {

        if (isset($_POST["childs_blocks"])) {
            $_POST["childs_blocks"] = json_encode($_POST["childs_blocks"]);
        } else {
            $_POST["childs_blocks"] = json_encode([]);
        }

        if (isset($_POST["assign_tech_group"]) && $_POST['check_type_value'] != 2) {
            $_POST["assign_tech_group"] = json_encode($_POST["assign_tech_group"]);
        } else {
            $_POST["assign_tech_group"] = json_encode([]);
        }

        //    Check update rights for fields
        $field->check(-1, UPDATE, $_POST);

        if ($field->update($_POST)) {

            //Hook to add and update values add from plugins
            if (isset($PLUGIN_HOOKS['metademands'])) {
                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                    $p = $_POST;
                    $new_res = Field::getPluginSaveOptions($plug, $p);
                }
            }
        }

        Html::back();

    } else if (isset($_POST["purge"])) {

        // Check update rights for fields
        $field->check(-1, DELETE, $_POST);
        $field->delete($_POST, 1);
        Html::back();

    }
}

