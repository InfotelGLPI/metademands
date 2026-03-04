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

use GlpiPlugin\Metademands\Basketline;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Fields\Basket;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;
use GlpiPlugin\Metademands\Group;
//use PluginOrderprojectsMetademand;

if (isset($_POST['see_basket_summary'])) {
    header("Content-Type: text/html; charset=UTF-8");
} else {
    header("Content-Type: application/json; charset=UTF-8");
}

Html::header_nocache();

Session::checkLoginUser();

$KO = false;
$step = $_POST['step'] + 1;
$metademands = new Metademand();
$wizard = new Wizard();
$fields = new Field();
$nofreetable = false;

//Add Ajax fields loaded by ulocationUpdate.php etc..
if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'])) {
    $session_fields = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'];
    foreach ($session_fields as $name => $session_field) {
        $_POST['field'][$name] = $session_field;
    }
}


//if (isset($_POST['is_freetable'])
//    && $_POST['is_freetable'] == 1
//    && isset($_SESSION['plugin_orderfollowup']['freetables'])) {
//    if (count($_SESSION['plugin_orderfollowup']['freetables']) == 0) {
//        $nofreetable = true;
//        $step = $_POST['step'];
//        unset($_POST['create_metademands']);
//        Session::addMessageAfterRedirect(__("There is no line on the basket", "metademands"), false, ERROR);
//
//        echo $nofreetable;
//    }
//}



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

if (isset($PLUGIN_HOOKS['metademands'])) {
	foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
		if (Plugin::isPluginActive($plug)) {
			echo Metademand::pluginPreItemAdd($plug);
		}
	}
}

if ($nofreetable == false) {
    if (isset($_POST['see_basket_summary']) && $_POST['see_basket_summary'] == 1) {
        $_POST['see_basket_summary'] = 0;

        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'])) {
            $freetables = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['freetables'];
            foreach ($freetables as $field_id => $freetable) {
                $_POST['freetables'][$field_id] = $freetable;
            }
        }

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
            echo Basket::displayBasketSummary($post);
        }


        if ($metademands->fields['is_order'] == 1) {
            $metademands_data = Metademand::constructMetademands($_POST['form_metademands_id']);
            //Reorder array
            $metademands_data = array_values($metademands_data);
            array_unshift($metademands_data, "", "");
            unset($metademands_data[0]);
            unset($metademands_data[1]);

            if (count($metademands_data)) {
                foreach ($metademands_data as $form_step => $data) {
                    foreach ($data as $form_metademands_id => $line) {
                        echo Basketline::displayBasketSummary(
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
            || Group::isUserHaveRight($_POST['form_metademands_id'])) {
            $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
            $metademands->getFromDB($_POST['form_metademands_id']);

            $meta = [];
//            if (Plugin::isPluginActive('orderprojects')
//                && $metademands->fields['is_order'] == 1) {
//                $orderprojects = new PluginOrderprojectsMetademand();
//                $meta = $orderprojects->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
//            }

            //            if (Plugin::isPluginActive('orderfollowup') && (!isset($_POST['field']) || empty($_POST['field']))) {
            //                if (isset($_SESSION['plugin_orderfollowup']['freeinputs'])) {
            //                    $freeinputs = $_SESSION['plugin_orderfollowup']['freeinputs'];
            //                    foreach ($freeinputs as $freeinput) {
            //                        $_POST['field'][] = $freeinput;
            //                    }
            //                }
            //            }

            if (isset($_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['freetables'])) {
                $freetables = $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['freetables'];
                foreach ($freetables as $freetable) {
                    $_POST['field'][] = $freetable;
                }
            }

            if (Plugin::isPluginActive('fdrpi') &&
                isset($_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fdrpi'])) {
                $fdrpis = $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fdrpi'];
                foreach ($fdrpis as $fdrpi) {
                    $_POST['field'][] = $fdrpi;
                }
            }
            if (count($meta) == 1) {
//                $orderprojects->createFromMetademands($_POST);
                Html::back();
            } else {
                $nblines = 0;
                //Create ticket
                if ($metademands->fields['is_order'] == 1) {
                    $basketline = new Basketline();
                    $basketToSend = $basketline->find([
                        'plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                        'users_id' => Session::getLoginUserID(),
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
                            $fieldopt = new FieldOption();
                            if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $idf])) {
                                foreach ($opts as $opt) {
                                    $check_value = $opt["check_value"];

                                    $data[$idf]["options"][$check_value]['plugin_metademands_tasks_id'][] = $opt['plugin_metademands_tasks_id'] ?? 0;
                                    $data[$idf]["options"][$check_value]['fields_link'][] = $opt['fields_link'] ?? 0;
                                    $data[$idf]["options"][$check_value]['hidden_link'][] = $opt['hidden_link'] ?? 0;
                                    $data[$idf]["options"][$check_value]['hidden_block'][] = $opt['hidden_block'] ?? 0;
                                    $data[$idf]["options"][$check_value]['users_id_validate'] = isset($opt['users_id_validate']) && $opt['users_id_validate'] > 0 ? $opt['users_id_validate'] : ($data[$idf]["options"][$check_value]['users_id_validate'] ?? 0);
                                    $data[$idf]["options"][$check_value]['childs_blocks'] = isset($opt['childs_blocks']) && $opt['childs_blocks'] != '[]' ? $opt['childs_blocks'] : (isset($data[$idf]["options"][$check_value]['childs_blocks']) && $data[$idf]["options"][$check_value]['childs_blocks'] != '[]' ? $data[$idf]["options"][$check_value]['childs_blocks'] : $opt['childs_blocks']);
                                    $data[$idf]["options"][$check_value]['checkbox_value'] = isset($opt['checkbox_value']) && $opt['checkbox_value'] > 0 ? $opt['checkbox_value'] : ($data[$idf]["options"][$check_value]['checkbox_value'] ?? 0);
                                    $data[$idf]["options"][$check_value]['checkbox_id'] = isset($opt['checkbox_id']) && $opt['checkbox_id'] > 0 ? $opt['checkbox_id'] : ($data[$idf]["options"][$check_value]['checkbox_id'] ?? 0);
                                    $data[$idf]["options"][$check_value]['parent_field_id'] = isset($opt['parent_field_id']) && $opt['parent_field_id'] > 0 ? $opt['parent_field_id'] : ($data[$idf]["options"][$check_value]['parent_field_id'] ?? 0);
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
                            $field = new Field();
                            if ($field->getFromDB($id)) {
                                $parameters = Field::getAllParamsFromField($field);
                            }

                            if ($parameters['is_mandatory'] == 1 && $value['item'] != 'ITILCategory_Metademands') {
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
                                $_POST['field'][$id] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                                $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'][$id] = $_POST['field'][$id];
                            }

                            if ($value['type'] == 'freetable') {
                                if (isset($_POST["is_freetable_mandatory"])) {
                                    $mandatories = $_POST["is_freetable_mandatory"];
                                    foreach ($mandatories as $fm => $mandatory) {
                                        if ($mandatory == 1 && $fm == $id) {
                                            if (!isset($_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['freetables'][$fm])
                                                || count(
                                                    $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['freetables'][$fm]
                                                ) == 0) {
                                                $msg = sprintf(__("There is no line on the mandatory table %s", "metademands"), $value['name']);
                                                Session::addMessageAfterRedirect(
                                                    $msg,
                                                    false,
                                                    ERROR
                                                );
                                                $KO = true;
                                            }
                                        }
                                    }
                                }
                            }


                            $checks[] = Wizard::checkvalues($value, $id, $_POST, 'field');
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
                            $step = Metademand::STEP_CREATE;
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
