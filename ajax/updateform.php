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

$KO = true;

$form = new PluginMetademandsForm();

use Glpi\Toolbox\Sanitizer;

if (isset($_POST['save_model'])) {

    $form->getFromDB($_POST['plugin_metademands_forms_id']);
    if ($form->fields['is_model'] == 0) {
        $input = ['name' => $_POST['form_name'],
            'plugin_metademands_metademands_id' => $form->fields['plugin_metademands_metademands_id'],
            'users_id' => $form->fields['users_id'],
            'items_id' => 0,
            'itemtype' => '',
            'date' => date('Y-m-d H:i:s'),
            'is_model' => $_POST['is_model']];

        if ($newid = $form->add($input)) {
            $KO = false;
            $_SESSION['plugin_metademands'][$form->fields['plugin_metademands_metademands_id']]['plugin_metademands_forms_name'] = $_POST['form_name'];
            $_SESSION['plugin_metademands'][$form->fields['plugin_metademands_metademands_id']]['plugin_metademands_forms_id'] = $newid;
            $form_values = new PluginMetademandsForm_Value();
            $values = $form_values->find(['plugin_metademands_forms_id' => $_POST['plugin_metademands_forms_id']]);

            $input = [];
//            if (isset($_SESSION['plugin_metademands'][$form->fields['plugin_metademands_metademands_id']]['fields']['files']['_filename'])) {
//                $input['_filename'] = $_SESSION['plugin_metademands'][$form->fields['plugin_metademands_metademands_id']]['fields']['files']['_filename'];
//            }
//            if (isset($_SESSION['plugin_metademands'][$form->fields['plugin_metademands_metademands_id']]['fields']['files']['_prefix_filename'])) {
//                $input['_prefix_filename'] = $_SESSION['plugin_metademands'][$form->fields['plugin_metademands_metademands_id']]['fields']['files']['_prefix_filename'];
//            }

            foreach ($values as $value) {

                $field = new PluginMetademandsField();
                $field->getFromDB($value['plugin_metademands_fields_id']);

                $fieldparameter            = new PluginMetademandsFieldParameter();
                if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $value['plugin_metademands_fields_id']])) {

                    if (isset($field->fields['type']) && $field->fields['type'] == 'textarea' && $fieldparameter->fields['use_richtext'] == 1) {
                        $form_value = new PluginMetademandsForm_Value();
                        $form_value->getFromDB($value['plugin_metademands_forms_id']);
                        $inputv = Toolbox::convertTagToImage($value['value'], $form_value, $input, false);
                        $inputv = Sanitizer::unsanitize($inputv);
                        $inputv = Toolbox::addslashes_deep($inputv);

                        $form_values->add(['plugin_metademands_forms_id' => $newid,
                            'plugin_metademands_fields_id' => $value['plugin_metademands_fields_id'],
                            'value' => $inputv,
                            'value2' => $value['value2']]);

                    } else {
                        $form_values->add(['plugin_metademands_forms_id' => $newid,
                            'plugin_metademands_fields_id' => $value['plugin_metademands_fields_id'],
                            'value' => $value['value'],
                            'value2' => $value['value2']]);
                    }
                }
            }
        }
    } else {

        $input = ['name' => $_POST['form_name'],
            'plugin_metademands_metademands_id' => $form->fields['plugin_metademands_metademands_id'],
            'users_id' => $form->fields['users_id'],
            'items_id' => 0,
            'itemtype' => '',
            'date' => date('Y-m-d H:i:s'),
            'is_model' => 1,
            'id' => $_POST['plugin_metademands_forms_id']];

        $form->update($input);
        $metademands = new PluginMetademandsMetademand();
        $forms_values = new PluginMetademandsForm_Value();
        $forms_values->deleteByCriteria(['plugin_metademands_forms_id' => $_POST['plugin_metademands_forms_id']]);
        $metademands_data = PluginMetademandsMetademand::constructMetademands($_POST['metademands_id']);

        $nblines = 0;
        $KO = false;

        if ($nblines == 0) {
            if(isset($_POST['field'])){
                $post    =  $_POST['field'];
            }

            $nblines = 1;
        }

        $checks = [];
        $content = [];

        for ($i = 0; $i < $nblines; $i++) {

//            if (Plugin::isPluginActive('orderfollowup')) {
//                if (isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
//                    $freeinputs = $_SESSION['plugin_orderfollowup']['freeinputs'];
//                    foreach ($freeinputs as $freeinput) {
//                        $_POST['freeinputs'][] = $freeinput;
//                    }
//                }
//            }

            if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'])) {
                $freetables = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'];

                foreach ($freetables as $field_id => $freetable) {
                    $_POST['freetables'][$_POST['metademands_id']][$field_id] = $freetable;
                }
            }

            $metademands_data = PluginMetademandsMetademand::constructMetademands($_POST['metademands_id']);

            if(!isset($post) || !is_array($post)){
                $_POST['field'] = [];
            }

            if (count($metademands_data)) {
                foreach ($metademands_data as $form_step => $data) {
                    $docitem = null;
                    foreach ($data as $form_metademands_id => $line) {
                        foreach ($line['form'] as $id => $value) {
                            if (!isset($post[$id])) {
                                if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id])
                                    && $value['plugin_metademands_metademands_id'] != $_POST['form_metademands_id']) {
                                    $_POST['field'][$id] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id];
                                }
                            } else {
                                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id] = $post[$id];
                            }

                            if ($value['type'] == 'radio') {
                                if (!isset($_POST['field'][$id])) {
                                    $_POST['field'][$id] = NULL;
                                }
                            }
                            if ($value['type'] == 'checkbox') {
                                if (!isset($_POST['field'][$id])) {
                                    $_POST['field'][$id] = 0;
                                }
                            }
                            if ($value['type'] == 'informations'
                                || $value['type'] == 'title') {
                                if (!isset($_POST['field'][$id])) {
                                    $_POST['field'][$id] = 0;
                                }
                            }
                            if ($value['item'] == 'ITILCategory_Metademands') {
                                $_POST['field'][$id] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                            }

                            if ($value['type'] == 'basket' && isset($_POST['quantity'])) {
                                $_POST['field'][$id] = $_POST['quantity'][$id];
                            }

                            if ($value['type'] == 'freetable'
                                && isset($_POST['freetables'])
                                && !empty($_POST['freetables'])) {

                                if(!isset($_POST['field']) || !is_array($_POST['field'])){
                                    $_POST['field'] = [];
                                }
                                if (isset($_POST['freetables'][$_POST['metademands_id']][$id])) {
                                    $_POST['field'][$id] = $_POST['freetables'][$_POST['metademands_id']][$id];
                                }

                            }
                        }

                    }
                }
            }

            if (count($metademands_data)) {
                foreach ($metademands_data as $form_step => $data) {
                    $docitem = null;
                    foreach ($data as $form_metademands_id => $line) {
                        PluginMetademandsForm_Value::setFormValues($_POST['metademands_id'], $line['form'], $_POST['field'], $_POST['plugin_metademands_forms_id']);
                    }
                }
            }
            PluginMetademandsForm_Value::loadFormValues($_POST['metademands_id'], $_POST['plugin_metademands_forms_id']);

            $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_forms_name'] = $_POST['form_name'];
            $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_forms_id'] = $_POST['plugin_metademands_forms_id'];
        }
    }
}

if ($KO === false) {
    echo 0;
} else {
    echo $KO;
}
