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
$nofreeinputs = false;

if (isset($_POST['is_freeinput'])
    && $_POST['is_freeinput'] == 1
    && isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
    if (count($_SESSION['plugin_orderfollowup']['freeinputs']) == 0) {
        $nofreeinputs = true;
        $step = $_POST['step'];
        unset($_POST['create_metademands']);
        Session::addMessageAfterRedirect(__("There is no line on the basket", "metademands"), false, ERROR);

        echo $nofreeinputs;
    }
}

$current_ticket_id = 0;
$meta_validated = false;
if (isset($_GET['current_ticket_id']) && $_GET['current_ticket_id'] > 0) {
    $_POST['current_ticket_id'] = $_GET['current_ticket_id'];
    $current_ticket_id = $_GET['current_ticket_id'];
}
if (isset($_POST['tickets_id']) && $_POST['tickets_id'] > 0) {
    $_POST['current_ticket_id'] = $_POST['tickets_id'];
    $current_ticket_id = $_POST['tickets_id'];
}

if (empty($_POST['ancestor_tickets_id'])) {
    $_POST['ancestor_tickets_id'] = 0;
}

if (isset($_POST['ancestor_tickets_id'])) {
    $ancestor_tickets_id = $_POST['ancestor_tickets_id'];
}

if (isset($_GET['meta_validated'])) {
    if ($_GET['meta_validated'] > 0) {
        $_POST['meta_validated'] = true;
    } else {
        $_POST['meta_validated'] = false;
    }
    $meta_validated = $_POST['meta_validated'];
}

if ($nofreeinputs === false) {
    if (isset($_POST['see_basket_summary'])) {
        unset($_POST['see_basket_summary']);
        $post = $_POST;

        //why i don't know
        if (isset($post['quantity']) && is_array($post['quantity'])) {
            foreach ($post['quantity'] as $k => $v) {
                foreach ($v as $key => $q) {
                    if ($q > 0) {
                        $post['field'][$k][$key] = $key;
                    }
                    if ($q == 0) {
                        unset($post['quantity'][$k][$key]);
                        unset($post['field'][$k][$key]);
                    }
                }
            }
        }

        $metademands->getFromDB($_POST['form_metademands_id']);

        if ($metademands->fields['is_basket'] == 1) {
            if (isset($_POST['is_freeinput']) && $_POST['is_freeinput'] == 1) {
                echo PluginOrderfollowupFreeinput::displayBasketSummary($post);
            } else {
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
                        echo PluginMetademandsBasketline::displayBasketSummary(
                            $_POST['form_metademands_id'],
                            $line['form'],
                            $post
                        );
                    }
                }
            }
        }
    } elseif (isset($_POST['form_metademands_id'])) {
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

            if (Plugin::isPluginActive('orderfollowup') && (!isset($_POST['field']) || empty($_POST['field']))) {
                if (isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
                    $freeinputs = $_SESSION['plugin_orderfollowup']['freeinputs'];
                    foreach ($freeinputs as $freeinput) {
                        $_POST['field'][] = $freeinput;
                    }
                }
            }

            if (count($meta) == 1) {
                $orderprojects->createFromMetademands($_POST);
                Html::back();
            } else {
                $nblines = 0;
                //Create ticket
                if ($metademands->fields['is_order'] == 1) {
                    $basketline = new PluginMetademandsBasketline();
                    $basketToSend = $basketline->find([
                        'plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                        'users_id' => Session::getLoginUserID()
                    ]);

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
                        Session::addMessageAfterRedirect(
                            __("There is no line on the basket", "metademands"),
                            false,
                            ERROR
                        );
                    }
                }
                if ($nblines == 0) {
                    $post = $_POST['field'];
                    if (isset($_POST['field_plugin_servicecatalog_itilcategories_id_key'])
                        && isset($_POST['field_plugin_servicecatalog_itilcategories_id'])) {
                        $post[$_POST['field_plugin_servicecatalog_itilcategories_id_key']] = $_POST['field_plugin_servicecatalog_itilcategories_id'];
                    }

                    if (isset($_POST['field_plugin_requestevolutions_itilcategories_id_key'])
                        && isset($_POST['field_plugin_requestevolutions_itilcategories_id'])) {
                        $post[$_POST['field_plugin_requestevolutions_itilcategories_id_key']] = $_POST['field_plugin_requestevolutions_itilcategories_id'];
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
                                $post = $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'];
                            }
                        }


                        foreach ($data as $idf => $form_data_fields) {
                            $fieldopt = new PluginMetademandsFieldOption();
                            if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $idf])) {
                                foreach ($opts as $opt) {
                                    $check_value = $opt["check_value"];
                                    if ($fieldopt->getFromDBByCrit([
                                        "plugin_metademands_fields_id" => $idf,
                                        "check_value" => $check_value
                                    ])) {
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

                        //Drop empty values for launch check_values
                        $post = array_filter($post);

                        //Clean $post & $data & $_POST
                        $dataOld = $data;

                        // Double appel for prevent order fields
                        $_POST['field'] = $post;

                        //end fields_link to be mandatory
                        foreach ($data as $id => $value) {
                            $field = new PluginMetademandsField();
                            if ($field->getFromDB($id)) {
                                $parameters = PluginMetademandsField::getAllParamsFromField($field);
                            }

                            if ($parameters['is_mandatory'] == 1) {
                                if (!isset($post[$id])) {
                                    continue;
                                }
                            }

                            if (isset($value['options'])) {
                                $check_values = $value['options'];
                                foreach ($check_values as $key => $check) {
                                    //Permit to launch child metademand on check value
                                    $value['check_value'] = $key;
                                    if (isset($check['hidden_link'])) {
                                        $value['plugin_metademands_tasks_id'] = $check['hidden_link'];
                                    }
                                    $value['fields_link'] = $check['fields_link'] ?? 0;
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

                            if ($value['item'] == 'ITILCategory_Requestevolutions') {
                                $_POST['field'][$id] = isset($_POST['field_plugin_requestevolutions_itilcategories_id']) ? $_POST['field_plugin_requestevolutions_itilcategories_id'] : 0;
                            }
                            $checks[] = PluginMetademandsWizard::checkvalues($value, $id, $_POST, 'field');
                        }

                        foreach ($checks as $check) {
                            if ($check['result'] == true) {
                                $KO = true;
                            }
//                            $content = array_merge($content, $check['content']);
                        }

                        if ($KO === false) {
                            // Save requester user
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
                            // Case of simple ticket convertion
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['tickets_id'] = $_POST['tickets_id'];
                            //case of child metademands for link it
                            if (isset($ancestor_tickets_id)) {
                                $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['ancestor_tickets_id'] = $ancestor_tickets_id;
                            }
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['current_ticket_id'] = $current_ticket_id;

                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['meta_validated'] = $meta_validated;

                            // Resources id
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
                            // Resources step
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];

                            //Category id if have category field
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
                                (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
                                    && $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'];

                        }

                        if ($KO) {
                            $step = $_POST['step'];
                        } elseif (isset($_POST['create_metademands'])) {
                            if (isset($_POST['quantity'])) {
                                $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['quantities'] = $_POST['quantity'];
                            }
                            $step = PluginMetademandsMetademand::STEP_CREATE;
                        }
                    }
                }
            }
        }
        if ($KO === false) {
            echo 0;
        } else {
            echo $KO;
        }
    } else {
        $KO = true;
        echo $KO;
    }
}
