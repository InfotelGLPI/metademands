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

Session::checkLoginUser();

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$fieldcustom = new PluginMetademandsFieldCustomvalue();
$fieldparam = new PluginMetademandsFieldParameter();

if (isset($_POST["add"])) {
    $params = [];
    if (isset($_POST["custom_values"])) {
        $custom_values = $_POST["custom_values"];
        foreach ($custom_values as $rank => $custom_value) {
            $params[$rank]['rank'] = $rank;
            $params[$rank]['name'] = $custom_value;
        }
    }
    if (isset($_POST["comment_values"])) {
        $comment_values = $_POST["comment_values"];
        foreach ($comment_values as $rank => $comment_value) {
            $params[$rank]['comment'] = $comment_value;
        }
    }
    if (isset($_POST["default_values"])) {
        $default_values = $_POST["default_values"];
        foreach ($default_values as $rank => $default_value) {
            $params[$rank]['is_default'] = $default_value;
        }
    }

    foreach ($params as $rank => $value) {
        $input['rank'] = $rank;
        $input['name'] = $value['name'];
        if (!isset($value['comment'])) {
            $value['comment'] = "";
        }
        $input['comment'] = $value['comment'];
        $input['is_default'] = $value['is_default'];
        $input['plugin_metademands_fields_id'] = $_POST['fields_id'];
        // Check update rights for fields
        $fieldcustom->check(-1, CREATE, $input);
        $fieldcustom->add($input);
    }
    Html::back();
} elseif (isset($_POST["update"])) {
    if ($_POST['type'] == "link"
        || $_POST['type'] == "number"
        || $_POST['type'] == "range"
        || $_POST['type'] == "basket"
        || ($_POST['type'] == "dropdown_multiple" && ($_POST['item'] == "Appliance" || $_POST['item'] == "Group"))) {
        $input["custom"] = PluginMetademandsFieldParameter::_serialize($_POST['custom']);
        $input["default"] = PluginMetademandsFieldParameter::_serialize($_POST['default']);
        $input["plugin_metademands_fields_id"] = $_POST['plugin_metademands_fields_id'];
        if ($fieldparam->getFromDBByCrit(["plugin_metademands_fields_id" => $_POST['plugin_metademands_fields_id']])) {
            $input["id"] = $fieldparam->getID();
            $fieldparam->check(-1, UPDATE, $input);
            $fieldparam->update($input);
        }
    } elseif ($_POST['type'] == "yesno") {
        $input["custom"] = $_POST['custom'];
        $input["default"] = $_POST['default'];
        $input["plugin_metademands_fields_id"] = $_POST['plugin_metademands_fields_id'];
        if ($fieldparam->getFromDBByCrit(["plugin_metademands_fields_id" => $_POST['plugin_metademands_fields_id']])) {
            $input["id"] = $fieldparam->getID();
            $fieldparam->check(-1, UPDATE, $input);
            $fieldparam->update($input);
        }
    } elseif ($_POST['item'] == "urgency"
        || $_POST['item'] == "priority"
        || $_POST['item'] == "impact"
        || $_POST['item'] == "mydevices") {
        $input["default"] = PluginMetademandsFieldParameter::_serialize($_POST['default']);
        $input["plugin_metademands_fields_id"] = $_POST['plugin_metademands_fields_id'];
        if ($fieldparam->getFromDBByCrit(["plugin_metademands_fields_id" => $_POST['plugin_metademands_fields_id']])) {
            $input["id"] = $fieldparam->getID();
            $fieldparam->check(-1, UPDATE, $input);
            $fieldparam->update($input);
        }
    } else {
        $names = $_POST['name'];
        $is_defaults = $_POST['is_default'];
        $comments = $_POST['comment'] ?? "";
        $ids = $_POST['id'];
        $inputs = [];
        if (is_array($ids) && count($ids) > 0) {
            foreach ($ids as $k => $id) {
                $inputs[$id]['id'] = $id;
                if (isset($names[$id])) {
                    $inputs[$id]['name'] = $names[$id];
                }
                if (isset($is_defaults[$id])) {
                    $inputs[$id]['is_default'] = $is_defaults[$id];
                }
                if (isset($comments[$id])) {
                    $inputs[$id]['comment'] = $comments[$id];
                }
            }
        }

        //    Check update rights for fields
        foreach ($inputs as $key => $input) {
            $fieldcustom->check(-1, UPDATE, $_POST);
            $fieldcustom->update($input);
        }
    }

    Html::back();
} elseif (isset($_POST["delete"])) {
    $input['id'] = $_POST['customvalues_id'];

    //TODO update ranks
    $condition_del = ["plugin_metademands_fields_id" => $_POST["plugin_metademands_fields_id"]];
    $condition_del['rank'] = ['>', $_POST['rank']];
    $updateRank = $fieldcustom->find($condition_del, "rank");
    if (count($updateRank) > 0) {
        foreach ($updateRank as $update) {
            $fieldcustom->update([
                'id' => $update['id'],
                'rank' => $update['rank'] - 1
            ]);
        }
    }
    $fieldcustom->check(-1, DELETE, $input);
    $fieldcustom->delete($input, 1);

    Html::back();
} else {
    $fieldcustom->checkGlobal(READ);
    Html::header(PluginMetademandsField::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");
    Html::requireJs('tinymce');
    $fieldcustom->display(['id' => $_GET["id"]]);
    Html::footer();
}
