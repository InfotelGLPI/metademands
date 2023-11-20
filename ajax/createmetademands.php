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

if (isset($_POST['see_basket_summary'])) {
    header("Content-Type: text/html; charset=UTF-8");
} else {
    header("Content-Type: application/json; charset=UTF-8");
}


Html::header_nocache();

Session::checkLoginUser();

$KO = false;
$step = $_POST['step'] + 1;
$metademands = new PluginMetademandsMetademand();
$wizard = new PluginMetademandsWizard();
$fields = new PluginMetademandsField();

if (isset($_POST['see_basket_summary'])) {

    if (isset($_GET['current_ticket_id']) && $_GET['current_ticket_id'] > 0) {
        $_POST['current_ticket_id'] = $_GET['current_ticket_id'];
    }
    if (isset($_GET['meta_validated'])) {
        if ($_GET['meta_validated'] > 0) {
            $_POST['meta_validated'] = true;
        } else {
            $_POST['meta_validated'] = false;
        }
    }

    unset($_POST['see_basket_summary']);
    $post = $_POST;

    //why i don't know
    if (isset($post['quantity'])) {
        foreach ($post['quantity'] as $k => $v) {
            foreach ($v as $key => $q) {
                if ($q > 0 && !isset($post['field'][$k])) {
                    $post['field'][$k] = [$key => $key];
                }
                if ($q == 0) {
                    unset($post['quantity'][$k][$key]);
                    unset($post['field'][$k][$key]);
                }
            }
        }
    }
    $metademands->getFromDB($_POST['form_metademands_id']);

    if (Plugin::isPluginActive('ordermaterial')) {
        $ordermaterial = new PluginOrdermaterialMetademand();
        if ($ordermaterial->getFromDBByCrit(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']])) {
            echo PluginOrdermaterialMetademand::displayBasketSummary($post);
        }
    } else {
        if ($metademands->fields['is_basket'] == 1) {
            echo PluginMetademandsBasket::displayBasketSummary($post);
        }
    }


    if ($metademands->fields['is_order'] == 1) {

        $metademands_data = $metademands->constructMetademands($_POST['form_metademands_id']);
        //Reorder array
        $metademands_data = array_values($metademands_data);
        array_unshift($metademands_data, "", "");
        unset($metademands_data[0]);
        unset($metademands_data[1]);

        if (count($metademands_data)) {
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    echo PluginMetademandsBasketline::displayBasketSummary($_POST['form_metademands_id'], $line['form'], $post);
                }
            }
        }
    }

} else {

    if ($metademands->canCreate()
        || PluginMetademandsGroup::isUserHaveRight($_POST['form_metademands_id'])) {
        $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
        $metademands->getFromDB($_POST['form_metademands_id']);

        $meta = [];
        if (Plugin::isPluginActive('orderprojects')
            && $metademands->fields['is_order'] == 1) {
            $orderprojects = new PluginOrderprojectsMetademand();
            $meta = $orderprojects->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
        }

        if (count($meta) == 1) {
            $orderprojects->createFromMetademands($_POST);
            Html::back();
        } else {
            $nblines = 0;
            //Create ticket
            if ($metademands->fields['is_order'] == 1) {
                $basketline = new PluginMetademandsBasketline();
                $basketToSend = $basketline->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                    'users_id' => Session::getLoginUserID()]);

                $basketLines = [];
                foreach ($basketToSend as $basketLine) {
                    $basketLines[$basketLine['line']][] = $basketLine;
                }

                $basket = [];
                if (count($basketLines) > 0) {
                    foreach ($basketLines as $idline => $field) {
                        foreach ($field as $k => $v) {
                            $basket[$v['plugin_metademands_fields_id']] = $v['value'];
                        }

                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['basket'][$nblines] = $basket;
                        $nblines++;
                    }
                    $_POST['field'] = $basket;
                } else {
                    $KO = true;
                    Session::addMessageAfterRedirect(__("There is no line on the basket", "metademands"), false, ERROR);
                }
            }
            if ($nblines == 0) {
                $post = $_POST['field'];

//                if (isset($_POST['_filename'])) {
//                    foreach ($_POST['_filename'] as $key => $filename) {
//                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']['_prefix_filename'][] = $_POST['_prefix_filename'][$key];
//                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']['_tag_filename'][] = $_POST['_tag_filename'][$key];
//                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']['_filename'][] = $_POST['_filename'][$key];
//                    }
//                }

                if (isset($_POST['field_plugin_servicecatalog_itilcategories_id_key'])
                    && isset($_POST['field_plugin_servicecatalog_itilcategories_id'])) {
                    $post[$_POST['field_plugin_servicecatalog_itilcategories_id_key']] = $_POST['field_plugin_servicecatalog_itilcategories_id'];
                }
                $nblines = 1;
            }
            if ($KO === false) {
                $checks = [];
                $content = [];

                for ($i = 0; $i < $nblines; $i++) {
                    if ($metademands->fields['is_order'] == 1) {
                        $post = $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['basket'][$i];
                    } else {

                        if (isset($_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'])) {
//                            unset($_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']);
                            $post = $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'];
                        }

                    }


                    foreach ($data as $idf => $form_data_fields) {

                        $fieldopt = new PluginMetademandsFieldOption();
                        if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $idf])) {

                            foreach ($opts as $opt) {
                                $check_value = $opt["check_value"];
                                if ($fieldopt->getFromDBByCrit(["plugin_metademands_fields_id" => $idf,
                                    "check_value" => $check_value])) {
                                    $data[$idf]["options"][$check_value]['plugin_metademands_tasks_id'] = $fieldopt->fields['plugin_metademands_tasks_id'] ?? 0;
                                    $data[$idf]["options"][$check_value]['fields_link'] = $fieldopt->fields['fields_link'] ?? 0;
                                    $data[$idf]["options"][$check_value]['hidden_link'] = $fieldopt->fields['hidden_link'] ?? 0;
                                    $data[$idf]["options"][$check_value]['hidden_block'] = $fieldopt->fields['hidden_block'] ?? 0;
                                    $data[$idf]["options"][$check_value]['users_id_validate'] = $fieldopt->fields['users_id_validate'] ?? 0;
                                    $data[$idf]["options"][$check_value]['childs_blocks'] = $fieldopt->fields['childs_blocks'];
                                    $data[$idf]["options"][$check_value]['checkbox_value'] = $fieldopt->fields['checkbox_value'] ?? 0;
                                    $data[$idf]["options"][$check_value]['checkbox_id'] = $fieldopt->fields['checkbox_id'] ?? 0;
                                    $data[$idf]["options"][$check_value]['parent_field_id'] = $fieldopt->fields['parent_field_id'] ?? 0;
                                }
                            }
                        }
                    }

                    //Clean $post & $data & $_POST
                    $dataOld = $data;

                    // Double appel for prevent order fields
                    PluginMetademandsFieldOption::unsetHidden($data, $post);
                    PluginMetademandsFieldOption::unsetHidden($dataOld, $post);
                    $_POST['field'] = $post;


                    //check fields_link to be mandatory
                    $fields_links = [];
                    foreach ($data as $id => $value) {
                        if (isset($value['options'])) {
                            $unserialisedCheck = $value['options'];
                            foreach ($unserialisedCheck as $key => $check) {
                                $fields_links[] = $check['fields_link'];
                            }
                        }
                    }

                    $fields_links = array_unique($fields_links);
                    $fields_links = array_filter($fields_links);

//                    $toBeMandatory = [];
//                    foreach ($fields_links as $fields_link) {
//                        if (isset($_POST['field'][$fields_link])
//                            && empty($_POST['field'][$fields_link])) {
//                            $toBeMandatory[] = $fields_link;
//                        }
//                    }
//
//                    $toBeMandatory = array_unique($toBeMandatory);
//                    $toBeMandatory = array_filter($toBeMandatory);

//                    if (is_array($toBeMandatory) && count($toBeMandatory) > 0) {
//                        foreach ($toBeMandatory as $keyMandatory => $valueMandatory) {
//                            if (isset($data[$valueMandatory]['type'])) {
//                                $data[$valueMandatory]['is_mandatory'] = true;
//                            }
//                        }
//                    }

                    //end fields_link to be mandatory
                    foreach ($data as $id => $value) {
                        if (!isset($post[$id])) {
                            $post[$id] = [];
                        }

                        if (isset($value['options'])) {
                            $check_values = $value['options'];
                            foreach ($check_values as $key => $check) {
                                //Permit to launch child metademand on check value
//                                $checkchild = $key;
//                                if (is_array($checkchild)) {
//                             Check if no form values block the creation of meta
                                $metademandtasks_tasks_id = PluginMetademandsMetademandTask::getSonMetademandTaskId($_POST['form_metademands_id']);

                                if (!is_null($metademandtasks_tasks_id)) {
                                    $_SESSION['son_meta'] = $metademandtasks_tasks_id;
                                    if (!isset($post)) {
                                        $post[$id] = 0;
                                    }
                                    $wizard->checkValueOk($key, $check['plugin_metademands_tasks_id'], $metademandtasks_tasks_id, $id, $value, $post);
                                }

//                                    foreach ($checkchild as $keyId => $check_value) {
                                $value['check_value'] = $key;
                                if (isset($check['hidden_link'])) {
                                    $value['plugin_metademands_tasks_id'] = $check['hidden_link'];
                                }
                                $value['fields_link'] = $check['fields_link'] ?? 0;
//                                    }
//                                }
                            }
                        }

                        if ($value['type'] == 'radio') {
                            if (!isset($_POST['field'][$id])) {
                                $_POST['field'][$id] = null;
                            }
                        }
                        if ($value['type'] == 'checkbox') {
                            if (!isset($_POST['field'][$id])) {
                                $_POST['field'][$id] = "";
                            }
                        }
                        if ($value['type'] == 'informations'
                            || $value['type'] == 'title') {
                            if (!isset($_POST['field'][$id])) {
                                $_POST['field'][$id] = 0;
                            }
                        }
                        if ($value['item'] == 'ITILCategory_Metademands') {
                            $_POST['field'][$id] = isset($_POST['field_plugin_servicecatalog_itilcategories_id']) ? $_POST['field_plugin_servicecatalog_itilcategories_id'] : 0;
                        }
                        $checks[] = PluginMetademandsWizard::checkvalues($value, $id, $_POST, 'field');
                    }

                    foreach ($checks as $check) {
                        if ($check['result'] == true) {
                            $KO = true;
                        }
                        $content = array_merge($content, $check['content']);
                    }

                    if ($KO === false) {
                        // Save requester user
                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
                        // Case of simple ticket convertion
                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['tickets_id'] = $_POST['tickets_id'];
                        // Resources id
                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
                        // Resources step
                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];

                        //Category id if have category field
                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
                            (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
                                && $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'];
//                        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_type'] = $metademands->fields['type'];
                    }

                    if ($KO) {
                        if (isset($_SESSION['metademands_hide'])) {
                            unset($_SESSION['metademands_hide']);
                        }
                        $step = $_POST['step'];
                    } elseif (isset($_POST['create_metademands'])) {

                        if (isset($_POST['quantity'])) {//Plugin::isPluginActive('ordermaterial') &&
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['quantities'] = $_POST['quantity'];
                        }
                        $step = PluginMetademandsMetademand::STEP_CREATE;
                    }
                }
            }
        }
    }
//}
    if ($KO === false) {
        echo 0;
    } else {
        echo $KO;
    }

}
