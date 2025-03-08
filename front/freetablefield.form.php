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

$fieldcustom = new PluginMetademandsFreetablefield();
$fieldparam = new PluginMetademandsFieldParameter();

if (isset($_POST["add"])) {
    $params = [];
    if (isset($_POST["internal_name_values"])) {
        $internal_name_values = $_POST["internal_name_values"];
        foreach ($internal_name_values as $rank => $internal_name_value) {
            $params[$rank]['rank'] = $rank;
            $params[$rank]['internal_name'] = $internal_name_value;
        }
    }
    if (isset($_POST["custom_values"])) {
        $custom_values = $_POST["custom_values"];
        foreach ($custom_values as $rank => $custom_value) {
            $params[$rank]['name'] = $custom_value;
        }
    }

    if (isset($_POST["type_values"])) {
        $type_values = $_POST["type_values"];
        foreach ($type_values as $rank => $type_value) {
            $params[$rank]['type'] = $type_value;
        }
    }

    if (isset($_POST["comment_values"])) {
        $comment_values = $_POST["comment_values"];
        foreach ($comment_values as $rank => $comment_value) {
            $params[$rank]['comment'] = $comment_value;
        }
    }

    if (isset($_POST["dropdown_values"])) {
        $dropdown_values = $_POST["dropdown_values"];
        foreach ($dropdown_values as $rank => $dropdown_value) {
            $params[$rank]['dropdown_values'] = $dropdown_value;
        }
    }

    if (isset($_POST["is_mandatory_values"])) {
        $is_mandatory_values = $_POST["is_mandatory_values"];
        foreach ($is_mandatory_values as $rank => $is_mandatory_value) {
            $params[$rank]['is_mandatory'] = $is_mandatory_value;
        }
    }

    foreach ($params as $rank => $value) {
        $input['rank'] = $rank;

        $name = preg_replace('/[^\da-z]/i', '', $value['internal_name']);
        $input['internal_name'] = $name;
        $input['name'] = $value['name'];
        $input['type'] = $value['type'];
        if (!isset($value['comment'])) {
            $value['comment'] = "";
        }
        $input['comment'] = $value['comment'];
        $input['is_mandatory'] = $value['is_mandatory'];
        $input['dropdown_values'] = $value['dropdown_values'];
        $input['plugin_metademands_fields_id'] = $_POST['fields_id'];
        // Check update rights for fields
        $fieldcustom->check(-1, CREATE, $input);
        $fieldcustom->add($input);
    }
    Html::back();
} elseif (isset($_POST["update"])) {

    $internal_names = $_POST['internal_name'];
    $names = $_POST['name'];
    $is_mandatorys = $_POST['is_mandatory'];
    $dropdown_values = $_POST['dropdown_values'];
    $comments = $_POST['comment'] ?? "";
    $types = $_POST['type'] ?? "";
    $ids = $_POST['id'];
    $inputs = [];

    if (is_array($ids) && count($ids) > 0) {
        foreach ($ids as $k => $id) {
            $inputs[$id]['id'] = $id;

            if (isset($internal_names[$id])) {
                $internal_name = preg_replace('/[^\da-z]/i', '', $internal_names[$id]);
                $inputs[$id]['internal_name'] = $internal_name;
            }
            if (isset($names[$id])) {
                $inputs[$id]['name'] = $names[$id];
            }
            if (isset($types[$id])) {
                $inputs[$id]['type'] = $types[$id];
            }
            if (isset($comments[$id])) {
                $inputs[$id]['comment'] = $comments[$id];
            }
            if (isset($is_mandatorys[$id])) {
                $inputs[$id]['is_mandatory'] = $is_mandatorys[$id];
            }
            if (isset($dropdown_values[$id])) {
                $inputs[$id]['dropdown_values'] = $dropdown_values[$id];
            }
        }
    }

    //    Check update rights for fields
    foreach ($inputs as $key => $input) {
        $fieldcustom->check(-1, UPDATE, $_POST);
        $fieldcustom->update($input);
    }

    Html::back();
} elseif (isset($_POST["delete"])) {
    $input['id'] = $_POST['freetablefield_id'];

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
