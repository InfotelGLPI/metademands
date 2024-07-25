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

global $CFG_GLPI;

$wizard = new PluginMetademandsWizard();
$metademands = new PluginMetademandsMetademand();
$fields = new PluginMetademandsField();

if (empty($_POST['metademands_id'])) {
    $_POST['metademands_id'] = 0;
}

if (empty($_GET['metademands_id'])) {
    $_GET['metademands_id'] = 0;
}

if (!isset($_GET['meta_type'])) {
    $_GET['meta_type'] = 0;
}

if (empty($_GET['tickets_id'])) {
    $_GET['tickets_id'] = 0;
}

if (empty($_GET['ancestor_tickets_id'])) {
    $_GET['ancestor_tickets_id'] = 0;
}

if (isset($_GET['ancestor_tickets_id'])) {
    $ancestor_tickets_id = $_GET['ancestor_tickets_id'];
}

if (empty($_GET['resources_id'])) {
    $_GET['resources_id'] = 0;
    if (isset($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'])
        && !empty($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'])) {
        $_GET['resources_id'] = $_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'];
    } elseif (isset($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields'])) {

        foreach ($_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields'] as $fieldKey => $field) {
            if (!is_array($field) && is_int($field)) {
                $metademandsField = new PluginMetademandsField();
                $metademandsField->getFromDB($fieldKey);
                if ($metademandsField->getField('item') == 'PluginResourcesResource') {
                    $_GET['resources_id'] = $field;
                    $_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'] = $field;
                }
            }

        }
    }
} else {
    $_SESSION['plugin_metademands'][$_GET['metademands_id']]['fields']['resources_id'] = $_GET['resources_id'];
}

if (empty($_GET['resources_step'])) {
    $_GET['resources_step'] = '';
}

if (!empty($_POST['step'])) {
    $_GET['step'] = $_POST['step'];
}

if (empty($_GET['step'])) {
    $_GET['step'] = PluginMetademandsMetademand::STEP_INIT;
}

$config = new PluginMetademandsConfig();
$config->getFromDB(1);

//unactivate because   ../index.php?redirect=PluginMetademandsWizard_X is broken
//if (Session::getCurrentInterface() != 'central'
//    && Plugin::isPluginActive('servicecatalog')
//    && ($_GET['step'] == PluginMetademandsMetademand::STEP_INIT || $_GET['step'] == PluginMetademandsMetademand::STEP_LIST)
//    && $config->getField('display_buttonlist_servicecatalog') == 0
//    && Session::haveRight("plugin_servicecatalog", READ)) {
//
//    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
//
//}

// Url Redirect case
if (isset($_GET['id'])) {
    $_GET['metademands_id'] = $_GET['id'];
    $_GET['step'] = PluginMetademandsMetademand::STEP_SHOW;
    $_GET['tickets_id'] = "0";
}

if (isset($_GET['metademands_id'])) {
    if ($metademands->getFromDB($_GET['metademands_id'])) {
        if (!Session::haveAccessToEntity($metademands->fields['entities_id'], $metademands->fields['is_recursive'])) {
            $message = __('This metademand cannot be used with this entity', 'metademands');
            Session::addMessageAfterRedirect($message, false, ERROR);


            if (Session::getCurrentInterface() != 'central'
                && Plugin::isPluginActive('servicecatalog')) {
                Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
                } else {
                Html::redirect($wizard->getFormURL() . "?step=" . PluginMetademandsMetademand::STEP_INIT);
            }
        }
    }
}

if (isset($_POST['next'])) {

    $KO = false;
    $step = $_POST['step'] + 1;
    if (isset($_POST['update_fields'])) {
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
                    $nblines = 1;
                }
                if ($KO === false) {
                    $checks = [];
                    $content = [];

                    for ($i = 0; $i < $nblines; $i++) {
                        if ($metademands->fields['is_order'] == 1) {
                            $post = $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['basket'][$i];
                        }


                        //Clean $post & $data & $_POST
                        $dataOld = $data;
                        // Double appel for prevent order fields
                        PluginMetademandsFieldOption::unsetHidden($data, $post);
                        PluginMetademandsFieldOption::unsetHidden($dataOld, $post);
                        $_POST['field'] = $post;

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
//                                    $metademandtasks_tasks_id = PluginMetademandsMetademandTask::getSonMetademandTaskId($_POST['form_metademands_id']);
//
//                                    if (!is_null($metademandtasks_tasks_id)) {
//                                        $_SESSION['son_meta'] = $metademandtasks_tasks_id;
//                                        if (!isset($post)) {
//                                            $post[$id] = 0;
//                                        }
//                                        $wizard->checkValueOk($key, $check['plugin_metademands_tasks_id'], $metademandtasks_tasks_id, $id, $value, $post);
//                                    }

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
                            //case of child metademands for link it
                            if (isset($ancestor_tickets_id)) {
                                $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['ancestor_tickets_id'] = $ancestor_tickets_id;
                            }
                            // Resources id
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
                            // Resources step
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];

                            //Category id if have category field
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
                                (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
                                    && $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
//                            $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['field_type']                                    = $metademands->fields['type'];

                            if(isset($_POST['field_plugin_requestevolutions_itilcategories_id'])){
                                //Category id if have category field
                                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_requestevolutions_itilcategories_id'] = $_POST['field_plugin_requestevolutions_itilcategories_id'];
                            }
                        }

                        if ($KO) {
//                            if (isset($_SESSION['metademands_hide'])) {
//                                unset($_SESSION['metademands_hide']);
//                            }
                            $step = $_POST['step'];
                        } elseif (isset($_POST['create_metademands'])) {
                            $step = PluginMetademandsMetademand::STEP_CREATE;
                        }
                    }
                }
            }
        }
    }

    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $step);
}

elseif (isset($_POST['previous'])) {

    if (isset($_SESSION['metademands_hide'])) {
        unset($_SESSION['metademands_hide']);
    }
    if (isset($_SESSION['metademands_child_meta'])) {
        unset($_SESSION['metademands_child_meta']);
    }
    if (Session::getCurrentInterface() == 'central') {
        Html::header(__('Create a metademand', 'metademands'), '', "helpdesk", "pluginmetademandsmenu");
    } else {
        if (Plugin::isPluginActive('servicecatalog')) {
            PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Create a metademand', 'metademands'));
        } else {
            Html::helpHeader(__('Create a metademand', 'metademands'));
        }
    }

    $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
    if (is_array($cats) && count($cats) == 1) {
        foreach ($cats as $cat) {
            $itilcategories = $cat;
        }
    } else {
        $itilcategories = $_SESSION['servicecatalog']['sc_itilcategories_id'] ?? 0;
    }

    // Resource previous wizard steps
    if ($_POST['step'] == PluginMetademandsMetademand::STEP_SHOW
        && !empty($_POST['resources_id'])
        && !empty($_POST['resources_step'])) {
        switch ($_POST['resources_step']) {
            case 'second_step':
                $resources = new PluginResourcesResource();
                $values['target'] = Toolbox::getItemTypeFormURL('PluginResourcesWizard');
                $values['withtemplate'] = 0;
                $values['new'] = 0;
                $resources->wizardSecondForm($_POST['resources_id'], $values);
                break;
            case 'third_step':
                $employee = new PluginResourcesEmployee();
                $employee->wizardThirdForm($_POST['resources_id']);
                break;
            case 'four_step':
                $choice = new PluginResourcesChoice();
                $choice->wizardFourForm($_POST['resources_id']);
                break;
            case 'five_step':
                $resource = new PluginResourcesResource();
                $values['target'] = Toolbox::getItemTypeFormURL('PluginResourcesWizard');
                $resource->wizardFiveForm($_POST['resources_id'], $values);
                break;
            case 'six_step':
                $resourcehabilitation = new PluginResourcesResourceHabilitation();
                $resourcehabilitation->wizardSixForm($_POST['resources_id']);
                break;
        }
        // Else metademand wizard step
    } else {

        if (isset($_POST['form_metademands_id'])) {
            $metademands->getFromDB($_POST['form_metademands_id']);
            $type = $metademands->fields['type'];

            switch ($_POST['step']) {
                case 2:
                case 1:
                    $_POST['step'] = PluginMetademandsMetademand::STEP_INIT;
                    break;
                default:
                    $_POST['step'] = $_POST['step'] - 1;
                    break;
            }

            if (Session::getCurrentInterface() != 'central'
                && Plugin::isPluginActive('servicecatalog')
                && $_POST['step'] == PluginMetademandsMetademand::STEP_LIST
                && Session::haveRight("plugin_servicecatalog", READ)) {
                if ($itilcategories == 0) {
                    if (isset($_SERVER['HTTP_REFERER'])
                        && strpos($_SERVER['HTTP_REFERER'], "wizard.form.php") !== false) {
                        Html::redirect($wizard->getFormURL() . "?step=" . PluginMetademandsMetademand::STEP_INIT);
                    } else {
                        Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
                    }
                } elseif ($itilcategories > 0 && $type > 0) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/choosecategory.form.php?type=$type&level=1");
                } elseif ($itilcategories > 0 && $type == 0) {
                    Html::redirect(PLUGIN_SERVICECATALOG_WEBDIR . "/front/main.form.php");
                }
            } elseif ($_POST['step'] == PluginMetademandsMetademand::STEP_SHOW) {
                if (isset($_SESSION['metademands_hide'])) {
                    unset($_SESSION['metademands_hide']);
                }
                if (isset($_SESSION['metademands_child_meta'])) {
                    unset($_SESSION['metademands_child_meta']);
                }
            }


            $options = ['step' => $_POST['step'],
                'metademands_id' => $_POST['metademands_id'],
                'itilcategories_id' => $itilcategories
            ];
            $wizard->showWizard($options);
        }

    }

    if (Session::getCurrentInterface() != 'central'
        && Plugin::isPluginActive('servicecatalog')) {
        PluginServicecatalogMain::showNavBarFooter('metademands');
    }

    if (Session::getCurrentInterface() == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}

elseif (isset($_POST['return'])) {
    if (isset($_SESSION['metademands_hide'])) {
        unset($_SESSION['metademands_hide']);
    }
    if (isset($_SESSION['metademands_child_meta'])) {
        unset($_SESSION['metademands_child_meta']);
    }

    Html::redirect($wizard->getFormURL() . "?step=" . PluginMetademandsMetademand::STEP_INIT);
}

elseif (isset($_POST['add_to_basket'])) {

    $KO = false;
    $step = PluginMetademandsMetademand::STEP_SHOW;

    $checks = [];
    $content = [];
    $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
//        'is_basket' => 1
    ]);


    //Clean $post & $data & $_POST
    $dataOld = $data;
    $post = $_POST['field'];
    // Double appel for prevent order fields
    PluginMetademandsFieldOption::unsetHidden($data, $post);
    PluginMetademandsFieldOption::unsetHidden($dataOld, $post);
    $_POST['field'] = $post;


    foreach ($data as $id => $value) {
        if ($value['type'] == 'radio') {
            if (!isset($_POST['field'][$id])) {
                $_POST['field'][$id] = null;
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


        $checks[] = PluginMetademandsWizard::checkvalues($value, $id, $_POST, 'field');
    }
    foreach ($checks as $check) {
        if ($check['result'] == true) {
            $KO = true;
        }
        $content = array_merge($content, $check['content']);
    }

    if ($KO === false && count($content) > 0) {
        $basketline = new PluginMetademandsBasketline();
        $basketline->addToBasket($content, $_POST['form_metademands_id']);
    } else {
        Session::addMessageAfterRedirect(__("There is a problem with the basket", "metademands"), false, ERROR);
    }
    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $step);

}

elseif (isset($_POST['update_basket_line'])) {

    $line = $_POST['update_basket_line'];
    if (isset($_POST['field_basket_' . $line])) {
        $KO = false;

        $checks = [];
        $content = [];
        $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['metademands_id']]);

        foreach ($data as $id => $value) {
            if ($value['type'] == 'radio') {
                if (!isset($_POST['field_basket_' . $line][$id])) {
                    $_POST['field_basket_' . $line][$id] = null;
                }
            }
            if ($value['type'] == 'checkbox') {
                if (!isset($_POST['field_basket_' . $line][$id])) {
                    $_POST['field_basket_' . $line][$id] = "";
                }
            }
            //            if ($value['type'] == 'informations'
            //                || $value['type'] == 'title') {
            //               if (!isset($_POST['field_basket_' . $line][$id])) {
            //                  $_POST['field_basket_' . $line][$id] = "";
            //               }
            //            }
            if ($value['item'] == 'ITILCategory_Metademands') {
                $_POST['field_basket_' . $line][$id] = $_POST['basket_plugin_servicecatalog_itilcategories_id'] ?? 0;
            }
            $fieldname = 'field_basket_' . $line;
            $checks[] = PluginMetademandsWizard::checkvalues($value, $id, $_POST, $fieldname, true);
        }
    }
    foreach ($checks as $check) {
        if ($check['result'] == true) {
            $KO = true;
        }
        //not used
        $content = array_merge($content, $check['content']);
    }

    if ($KO === false) {
        $basketline = new PluginMetademandsBasketline();
        $basketline->updateFromBasket($_POST, $line);
    }

    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);
}

elseif (isset($_POST['delete_basket_line'])) {
    $basketline = new PluginMetademandsBasketline();
    $basketline->deleteFromBasket($_POST);

    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);
}

elseif (isset($_POST['delete_basket_file'])) {
    $basketline = new PluginMetademandsBasketline();
    $basketline->deleteFileFromBasket($_POST);

    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);
}

elseif (isset($_POST['clear_basket'])) {
    $basketline = new PluginMetademandsBasketline();
    $basketline->deleteByCriteria(['plugin_metademands_metademands_id' => $_POST['metademands_id'],
        'users_id' => Session::getLoginUserID()]);

    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);

}

elseif (isset($_POST['clean_form'])) {
    unset($_SESSION['plugin_metademands']);
    Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $_POST['step']);
}

else {

    //Default wizard
    if (Session::getCurrentInterface() == 'central') {
        Html::header(__('Create a metademand', 'metademands'), '', "helpdesk", "pluginmetademandsmenu", "wizard");
    } else {
        if (Plugin::isPluginActive('servicecatalog')) {
            PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Create a metademand', 'metademands'));
        } else {
            Html::helpHeader(__('Create a metademand', 'metademands'));
        }
    }

    if (isset($_SESSION['metademands_hide'])) {
        unset($_SESSION['metademands_hide']);
    }
    if (isset($_SESSION['metademands_child_meta'])) {
        unset($_SESSION['metademands_child_meta']);
    }
    $itilcategories_id = 0;
    if (isset($_GET['itilcategories_id']) && $_GET['itilcategories_id'] > 0) {
        $itilcategories_id = $_GET['itilcategories_id'];
    }
    //      if (!isset($_GET['itilcategories_id']) && isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
    //         $itilcategories_id = $_SESSION['servicecatalog']['sc_itilcategories_id'];
    //      }


    $options = ['step' => $_GET['step'],
        'metademands_id' => $_GET['metademands_id'],
        'preview' => false,
        'tickets_id' => $_GET['tickets_id'],
        'ancestor_tickets_id' => $_GET['ancestor_tickets_id'],
        'resources_id' => $_GET['resources_id'],
        'resources_step' => $_GET['resources_step'],
        'itilcategories_id' => $itilcategories_id];

    if (isset($_GET['see_form']) && $_GET['see_form'] > 0) {
        $options['seeform'] = true;
    }
    if (isset($_GET['current_ticket_id']) && $_GET['current_ticket_id'] > 0) {
        $options['current_ticket_id'] = $_GET['current_ticket_id'];
    }
    if (isset($_GET['meta_validated'])) {
        if ($_GET['meta_validated'] > 0) {
            $options['meta_validated'] = true;
        } else {
            $options['meta_validated'] = false;
        }
    }
    if (isset($_GET['meta_type'])) {
        $options['meta_type'] = $_GET['meta_type'];
    }
    if (!isset($_GET['step'])) {
        $options['step'] = PluginMetademandsMetademand::STEP_INIT;
    }

    PluginMetademandsStepform::showWaitingWarning();

    $wizard->showWizard($options);

    if (Session::getCurrentInterface() != 'central'
        && Plugin::isPluginActive('servicecatalog')) {
        PluginServicecatalogMain::showNavBarFooter('metademands');
    }


    if (Session::getCurrentInterface() == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
