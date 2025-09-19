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
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;

Session::checkLoginUser();

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$field = new Field();
$fieldparameter = new FieldParameter();
$fieldcustomvalues = new FieldCustomvalue();

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

if (isset($_POST['type'])
    && ($_POST['type'] == "text"
        || $_POST['type'] == "title"
        || $_POST['type'] == "title-block"
        || $_POST['type'] == "tel"
        || $_POST['type'] == "email"
        || $_POST['type'] == "url"
        || $_POST['type'] == "textarea"
        || $_POST['type'] == "signature")) {
    $_POST['item'] = null;
}

if (isset($_POST["add"])) {
    if (isset($_POST["plugin_metademands_metademands_id"])) {
        $meta = new Metademand();
        $meta->getFromDB($_POST["plugin_metademands_metademands_id"]);
        $_POST["entities_id"] = $meta->getEntityID();
    }
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
        Metademand::addLog($_POST, Metademand::LOG_ADD);
        unset($_SESSION['glpi_plugin_metademands_fields']);
    }

    Html::back();
} elseif (isset($_POST["update"])) {

    if ($_POST["type"] == 'checkbox') {
        $_POST["item"] = 'checkbox';
    }

    if ($_POST["type"] == 'radio') {
        $_POST["item"] = 'radio';
    }

    if (($_POST["type"] == 'dropdown_meta' || $_POST["type"] == 'dropdown_multiple')
    && ($_POST["item"] == 'radio' || $_POST["item"] == 'checkbox')) {
        $_POST["item"] = 'other';
    }

    if (!isset($_POST['item'])) {
        $_POST['item'] = "";
    }

    //convert radio | checkbox to dropdown_meta - other
    if (isset($_POST["type"]) && isset($_POST['item'])
        && $_POST["type"] == $_POST["item"]
        && ($_POST["item"] == "dropdown_meta" || $_POST["item"] == "dropdown_multiple")) {
        $_POST['item'] = "other";
    }


    //    Check update rights for fields
    $field->check(-1, UPDATE, $_POST);

    if ($field->update($_POST)) {
        $field->recalculateOrder($_POST);
        Metademand::addLog($_POST, Metademand::LOG_UPDATE);

        //Hook to add and update values add from plugins
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p = $_POST;
                $new_res = Field::getPluginSaveOptions($plug, $p);
            }
        }
    }

    Html::back();
}  else if (isset($_POST["fixorders"])) {

    $field = new Field();
    if ($field_values = $field->find(["plugin_metademands_metademands_id" =>$_POST["plugin_metademands_metademands_id"],
        'rank' => $_POST["rank"]])) {
        if (count($field_values) > 0) {

            foreach ($field_values as $k => $field_value) {
                $orders[$k]['order'] = $field_value['order'];
            }

            $neworders = Field::fixOrders($orders);
            foreach ($neworders as $id => $neworder) {
                $input['id'] = $id;
                $input['order'] = $neworder['order'];
                $field->update($input);
            }
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    // Check update rights for fields
    $field->check(-1, UPDATE, $_POST);
    $field->delete($_POST, 1);
    Metademand::addLog($_POST, Metademand::LOG_DELETE);
    Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/metademand.form.php?id=" . $_POST['plugin_metademands_metademands_id']);
} else {
    $field->checkGlobal(READ);
    Html::header(Field::getTypeName(2), '', "helpdesk", Menu::class);
    Html::requireJs('tinymce');
    $field->display(['id' => $_GET["id"]]);
    Html::footer();
}
