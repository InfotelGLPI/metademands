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

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

$KO = false;
$step = $_POST['step'] + 1;
$metademands = new PluginMetademandsMetademand();
$wizard = new PluginMetademandsWizard();
$fields = new PluginMetademandsField();
$nofreeinputs = false;

if (isset($_POST['is_freeinput'])
    && $_POST['is_freeinput'] == 1
    && isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
    if (count($_SESSION['plugin_orderfollowup']['freeinputs']) == 0) {
        $nofreeinputs = true;
        $step = $_POST['step'];
        unset($_POST['create_metademands']);
    }
}

//why i don't know
if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
    foreach ($_POST['quantity'] as $k => $v) {
        foreach ($v as $key => $q) {
            if ($q > 0) {
                $_POST['field'][$k][$key] = $key;
            }
            if ($q == 0) {
                unset($_POST['quantity'][$k][$key]);
                unset($_POST['field'][$k][$key]);
            }
        }
    }
}


if (isset($_POST['save_form']) && isset($_POST['metademands_id'])) {
    $nblines = 0;
    $KO = false;

    if ($nblines == 0) {
        if (isset($_POST['field'])) {
            $post = $_POST['field'];

            if (isset($_POST['field_plugin_servicecatalog_itilcategories_id_key'])
                && isset($_POST['field_plugin_servicecatalog_itilcategories_id'])) {
                $post[$_POST['field_plugin_servicecatalog_itilcategories_id_key']] = $_POST['field_plugin_servicecatalog_itilcategories_id'];
            }

        } else {
            $post['field'] = [];
        }


        $nblines = 1;
    }

    if ($KO === false) {
        $checks = [];
        $content = [];

        for ($i = 0; $i < $nblines; $i++) {
            if (isset($_POST['_filename'])) {
                foreach ($_POST['_filename'] as $key => $filename) {
                    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['uploaded_files']['_prefix_filename'][] = $_POST['_prefix_filename'][$key];
                    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['uploaded_files']['_tag_filename'][] = $_POST['_tag_filename'][$key];
                    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['uploaded_files']['_filename'][] = $_POST['_filename'][$key];
                }
            }

            if (Plugin::isPluginActive('orderfollowup')) {
                if (isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
                    $freeinputs = $_SESSION['plugin_orderfollowup']['freeinputs'];
                    foreach ($freeinputs as $freeinput) {
                        $_POST['freeinputs'][] = $freeinput;
                    }
                }
            }

            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);

            if (count($metademands_data)) {
                foreach ($metademands_data as $form_step => $data) {
                    $docitem = null;
                    foreach ($data as $form_metademands_id => $line) {
                        foreach ($line['form'] as $id => $value) {
                            if (!isset($post[$id])) {
                                if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id])
                                    && $value['plugin_metademands_metademands_id'] != $_POST['form_metademands_id']) {
                                    $post[$id] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id];
                                }
                            } else {
                                if ($post[$id] != 0) {
                                    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id] = $post[$id];
                                }
                            }

                            if ($value['type'] == 'radio') {
                                if (!isset($post[$id])) {
                                    $post[$id] = null;
                                }
                            }
                            if ($value['type'] == 'checkbox') {
                                if (!isset($post[$id])) {
                                    $post[$id] = 0;
                                }
                            }
                            if ($value['type'] == 'informations'
                                || $value['type'] == 'title'
                                || $value['type'] == 'title-block') {
                                $post[$id] = "";
                            }
                            if ($value['item'] == 'ITILCategory_Metademands') {
                                $post[$id] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                            }

                            if ($value['type'] == 'basket' && isset($_POST['quantity'])) {
                                $post[$id] = isset($_POST['quantity'][$id]) ? $_POST['quantity'][$id] : 0;
                            }

                            if ($value['type'] == 'free_input' && isset($_POST['freeinputs']) && !empty($_POST['freeinputs'])) {
                                $post[$id] = $_POST['freeinputs'];
                            }
                        }
                    }
                }
            }

            $metademands->getFromDB($_POST['metademands_id']);
            if ($KO === false) {
                // Save requester user
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
                // Case of simple ticket convertion
                if (isset($_POST['items_id']) && $_POST['itemtype'] == 'Ticket') {
                    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['tickets_id'] = $_POST['items_id'];
                }
                // Resources id
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
                // Resources step
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];

                //Category id if have category field
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;


            }

            $forms = new PluginMetademandsForm();

            if (!isset($_POST['form_name']) || (isset($_POST['form_name']) && empty($_POST['form_name']))) {
                Session::addMessageAfterRedirect(__('Form name is required', 'metademands'), false, ERROR);
                break;
            }
            $inputs = [];
            $inputs['name'] = Toolbox::addslashes_deep($_POST['form_name']);
            $inputs['users_id'] = Session::getLoginUserID();
            $inputs['plugin_metademands_metademands_id'] = $_POST['metademands_id'];
            $inputs['date'] = date('Y-m-d H:i:s');
            if (isset($_POST['is_model'])) {
                $inputs['is_model'] = $_POST['is_model'];
            }

            if (isset($_POST['resources_id']) && $_POST['resources_id'] > 0) {
                $resForm = $forms->find([
                    'plugin_metademands_metademands_id' => $_POST['metademands_id'],
                    'resources_id' => $_POST['resources_id']
                ]);
                if (count($resForm)) {
                    foreach ($resForm as $res) {
                        $last = $res['id'];
                    }
                } else {
                    $last = 0;
                }
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['form_to_compare'] = $last;
            }

            if ($form_new_id = $forms->add($inputs)) {
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_forms_id'] = $form_new_id;
                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_forms_name'] = $_POST['form_name'];

                $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);

                if ($metademands->fields['is_basket'] == 1
                    && isset($_POST['quantity'])) {
                    $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['quantities'] = $_POST['quantity'];
                }

                if (count($metademands_data) && $form_new_id > 0) {
                    foreach ($metademands_data as $form_step => $data) {
                        $docitem = null;
                        foreach ($data as $form_metademands_id => $line) {
                            PluginMetademandsForm_Value::setFormValues(
                                $_POST['metademands_id'],
                                $line['form'],
                                $post,
                                $form_new_id
                            );
                        }
                    }
                }
            } else {
                $KO = true;
            }
        }
    }
}
if ($KO === false) {
    echo 0;
} else {
    echo $KO;
}
