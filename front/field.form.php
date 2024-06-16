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

$field = new PluginMetademandsField();
$fieldparameter = new PluginMetademandsFieldParameter();
$fieldcustomvalues = new PluginMetademandsFieldCustomvalue();

if (isset($_POST['existing_field_id'])) {
    if ($field->getFromDB($_POST['existing_field_id'])) {
        $input = $field->fields;
        unset($input['id']);
        unset($input['entities_id']);
        unset($input['is_recursive']);
        unset($input['rank']);
        unset($input['order']);
        unset($input['plugin_metademands_fields_id']);
        unset($input['plugin_metademands_metademands_id']);
        $_POST = array_merge($_POST, $input);
    }
}

if (isset($_POST['item']) && isset($_POST['type'])
    && (empty($_POST['item']) || $_POST['item'] === 0)) {
    $_POST['item'] = $_POST['type'];
}

if (isset($_POST["add"])) {
    $_POST["name"] = Toolbox::addslashes_deep($_POST["name"]);
    $_POST["comment"] = Toolbox::addslashes_deep($_POST["comment"]);
    $_POST["label2"] = Toolbox::addslashes_deep($_POST["label2"]);
    // Check update rights for fields
    $field->check(-1, UPDATE, $_POST);

    if ($_POST['id'] = $field->add($_POST)) {

        if (isset($_POST['existing_field_id'])
            && $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $_POST['existing_field_id']])) {
            $inputp = $fieldparameter->fields;
            unset($inputp['id']);
            $inputp['plugin_metademands_fields_id'] = $_POST['id'];
            $fieldparameter->add($inputp);
        } else {
            $fieldparameter->add(["plugin_metademands_fields_id" => $_POST['id']]);
        }

        if (isset($_POST['existing_field_id'])
            && $customs = $fieldcustomvalues->find(['plugin_metademands_fields_id' => $_POST['existing_field_id']])) {

            if (count($customs) > 0) {
                foreach ($customs as $key => $val) {
                    $inputc['name'] = $val['name'];
                    $inputc['is_default'] = $val['is_default'];
                    $inputc['comment'] = $val['comment'];
                    $inputc['rank'] = $val['rank'];
                    $inputc['plugin_metademands_fields_id'] = $_POST['id'];
                    $fieldcustomvalues->add($inputc);
                }
            }
        }

        $field->recalculateOrder($_POST);
        PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_ADD);
        unset($_SESSION['glpi_plugin_metademands_fields']);
    }

    Html::back();
} elseif (isset($_POST["update"])) {


    if ($_POST["type"] == 'checkbox'
        || $_POST["type"] == 'radio') {
        $_POST["item"] = 0;
    }

    if (!isset($_POST['item'])) {
        $_POST['item'] = "";
    }

    //convert radio | checkbox to dropdown_meta - other
    if (isset($_POST["type"]) && isset($_POST['item'])
    && $_POST["type"] == $_POST["item"] && ($_POST["item"] == "dropdown_meta" || $_POST["item"] == "dropdown_multiple")) {
        $_POST['item'] = "other";
    }

    //    Check update rights for fields
    $field->check(-1, UPDATE, $_POST);

    if ($field->update($_POST)) {
        $field->recalculateOrder($_POST);
        PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_UPDATE);

        //Hook to add and update values add from plugins
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p = $_POST;
                $new_res = PluginMetademandsField::getPluginSaveOptions($plug, $p);
            }
        }
    }

    Html::back();
} elseif (isset($_POST["purge"])) {
    // Check update rights for fields
    $field->check(-1, UPDATE, $_POST);
    $field->delete($_POST, 1);
    PluginMetademandsMetademand::addLog($_POST, PluginMetademandsMetademand::LOG_DELETE);
    $field->redirectToList();
} else {
    $field->checkGlobal(READ);
    Html::header(PluginMetademandsField::getTypeName(2), '', "helpdesk", "pluginmetademandsmenu");
    Html::requireJs('tinymce');
    $field->display(['id' => $_GET["id"]]);
    Html::footer();
}
