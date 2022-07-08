<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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

$wizard      = new PluginMetademandsWizard();
$metademands = new PluginMetademandsMetademand();
$fields      = new PluginMetademandsField();

if (empty($_POST['metademands_id'])) {
   $_POST['metademands_id'] = 0;
}

if (empty($_GET['metademands_id'])) {
   $_GET['metademands_id'] = 0;
}

if (empty($_GET['tickets_id'])) {
   $_GET['tickets_id'] = 0;
}

if (empty($_GET['resources_id'])) {
   $_GET['resources_id'] = 0;
   if (isset($_SESSION['plugin_metademands']['fields']['resources_id']) && !empty($_SESSION['plugin_metademands']['fields']['resources_id'])) {
      $_GET['resources_id'] = $_SESSION['plugin_metademands']['fields']['resources_id'];
   } else if (isset($_SESSION['plugin_metademands']['fields'])) {
      foreach ($_SESSION['plugin_metademands']['fields'] as $fieldKey => $field) {
         if (!is_array($field)) {
            $metademandsField = new PluginMetademandsField();
            $metademandsField->getFromDB($fieldKey);
            if ($metademandsField->getField('item') == 'PluginResourcesResource') {
               $_GET['resources_id']                                     = $field;
               $_SESSION['plugin_metademands']['fields']['resources_id'] = $field;
            }
         }
      }

   }
} else{
   $_SESSION['plugin_metademands']['fields']['resources_id'] = $_GET['resources_id'];
}

if (empty($_GET['resources_step'])) {
   $_GET['resources_step'] = '';
}

if (empty($_GET['step'])) {
   $_GET['step'] = PluginMetademandsMetademand::STEP_LIST;
}

// Url Redirect case
if (isset($_GET['id'])) {
   $_GET['metademands_id'] = $_GET['id'];
   $_GET['step']           = PluginMetademandsMetademand::STEP_SHOW;
   $_GET['tickets_id']     = "0";
}

if (isset($_POST['next'])) {
   $KO   = false;
   $step = $_POST['step'] + 1;
   if (isset($_POST['update_fields'])) {
      if ($metademands->canCreate()
          || PluginMetademandsGroup::isUserHaveRight($_POST['form_metademands_id'])) {

         $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
         $metademands->getFromDB($_POST['form_metademands_id']);
         $plugin = new Plugin();
         $meta   = [];
         if ($plugin->isActivated('orderprojects')
             && $metademands->fields['is_order'] == 1) {
            $orderprojects = new PluginOrderprojectsMetademand();
            $meta          = $orderprojects->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
         }

         if (count($meta) == 1) {
            $orderprojects->createFromMetademands($_POST);
            Html::back();

         } else {

            $nblines = 0;
            //Create ticket
            if ($metademands->fields['is_order'] == 1) {
               $basketline   = new PluginMetademandsBasketline();
               $basketToSend = $basketline->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                                                  'users_id'                          => Session::getLoginUserID()]);

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

                     $_SESSION['plugin_metademands']['basket'][$nblines] = $basket;
                     $nblines++;
                  }
                  $_POST['field'] = $basket;

               } else {
                  $KO = true;
                  Session::addMessageAfterRedirect(__("There is no line on the basket", "metademands"), false, ERROR);
               }
            }
            if ($nblines == 0) {
               $post    = $_POST['field'];
               $nblines = 1;
            }
            if ($KO === false) {

               $checks  = [];
               $content = [];

               for ($i = 0; $i < $nblines; $i++) {

                  if ($metademands->fields['is_order'] == 1) {
                     $post = $_SESSION['plugin_metademands']['basket'][$i];
                  }


                  //Clean $post & $data & $_POST
                  $dataOld = $data;
                  // Double appel for prevent order fields
                  PluginMetademandsWizard::unsetHidden($data, $post);
                  PluginMetademandsWizard::unsetHidden($dataOld, $post);
                  $_POST['field'] = $post;

                  foreach ($data as $id => $value) {
                     if (!isset($post[$id])) {
                        $post[$id] = [];
                     }
                     //Permit to launch child metademand on check value
                     $checkchild = PluginMetademandsField::_unserialize($value['check_value']);
                     if (is_array($checkchild)) {

                        // Check if no form values block the creation of meta
                        $metademandtasks_tasks_id = PluginMetademandsMetademandTask::getSonMetademandTaskId($_POST['form_metademands_id']);

                        if (!is_null($metademandtasks_tasks_id)) {

                           $_SESSION['son_meta'] = $metademandtasks_tasks_id;
                           if (!isset($post)) {
                              $post[$id] = 0;
                           }
                           foreach ($checkchild as $keyId => $check_value) {
                              $plugin_metademands_tasks_id = PluginMetademandsField::_unserialize($value['plugin_metademands_tasks_id']);
                              $wizard->checkValueOk($check_value, $plugin_metademands_tasks_id[$keyId], $metademandtasks_tasks_id, $id, $value, $post);
                           }
                        }

                        foreach ($checkchild as $keyId => $check_value) {
                           $value['check_value']                 = $check_value;
                           $value['plugin_metademands_tasks_id'] = PluginMetademandsField::_unserialize($value['hidden_link'])[$keyId];
                           $value['fields_link']                 = isset(PluginMetademandsField::_unserialize($value['fields_link'])[$keyId]) ? PluginMetademandsField::_unserialize($value['fields_link'])[$keyId] : 0;
                        }
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
                     $_SESSION['plugin_metademands']['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
                     // Case of simple ticket convertion
                     $_SESSION['plugin_metademands']['fields']['tickets_id'] = $_POST['tickets_id'];
                     // Resources id
                     $_SESSION['plugin_metademands']['fields']['resources_id'] = $_POST['resources_id'];
                     // Resources step
                     $_SESSION['plugin_metademands']['fields']['resources_step'] = $_POST['resources_step'];

                     //Category id if have category field
                     $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] = isset($_POST['field_plugin_servicecatalog_itilcategories_id']) ? $_POST['field_plugin_servicecatalog_itilcategories_id'] : 0;
                     $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] =
                        (isset($_POST['basket_plugin_servicecatalog_itilcategories_id']) && $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
                     $_SESSION['plugin_metademands']['field_type']                                    = $metademands->fields['type'];
                  }

                  if ($KO) {
                     if (isset($_SESSION['metademands_hide'])) {
                        unset($_SESSION['metademands_hide']);
                     }
                     $step = $_POST['step'];
                  } else if (isset($_POST['create_metademands'])) {
                     $step = PluginMetademandsMetademand::STEP_CREATE;
                  }
               }
            }
         }
      }
   }

   Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $step);

} else
   if (isset($_POST['previous'])) {
      if (isset($_SESSION['metademands_hide'])) {
         unset($_SESSION['metademands_hide']);
      }
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmenu");
      } else {
         $plugin = new Plugin();
         if ($plugin->isActivated('servicecatalog')) {
            PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Create a demand', 'metademands'));
         } else {
            Html::helpHeader(__('Create a demand', 'metademands'));
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
      $metademands->getFromDB($_POST['form_metademands_id']);
      $type = $metademands->fields['type'];

      // Resource previous wizard steps
      if ($_POST['step'] == PluginMetademandsMetademand::STEP_SHOW
          && !empty($_POST['resources_id'])
          && !empty($_POST['resources_step'])) {
         switch ($_POST['resources_step']) {
            case 'second_step':
               $resources              = new PluginResourcesResource();
               $values['target']       = Toolbox::getItemTypeFormURL('PluginResourcesWizard');
               $values['withtemplate'] = 0;
               $values['new']          = 0;
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
               $resource         = new PluginResourcesResource();
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
         switch ($_POST['step']) {
            case 1:
               $_POST['step'] = PluginMetademandsMetademand::STEP_INIT;
               break;
            default:
               $_POST['step'] = $_POST['step'] - 1;
               break;
         }
         $plugin = new Plugin();
         if ($plugin->isActivated('servicecatalog')
             && $_POST['step'] == PluginMetademandsMetademand::STEP_LIST
             && Session::haveRight("plugin_servicecatalog", READ)) {
            if ($itilcategories == 0) {
               if (isset($_SERVER['HTTP_REFERER'])
                   && strpos($_SERVER['HTTP_REFERER'], "wizard.form.php") !== false) {
                  Html::redirect($wizard->getFormURL() . "?step=" . PluginMetademandsMetademand::STEP_INIT);
               } else {
                  Html::redirect($CFG_GLPI["root_doc"] . "/plugins/servicecatalog/front/main.form.php");
               }
            } else if ($itilcategories > 0 && $type > 0) {
               Html::redirect($CFG_GLPI["root_doc"] . "/plugins/servicecatalog/front/main.form.php?choose_category&type=$type&level=1");
            } else if ($itilcategories > 0 && $type == 0) {
               Html::redirect($CFG_GLPI["root_doc"] . "/plugins/servicecatalog/front/main.form.php");
            }
         } else if ($_POST['step'] == PluginMetademandsMetademand::STEP_SHOW) {
            if (isset($_SESSION['metademands_hide'])) {
               unset($_SESSION['metademands_hide']);
            }
            if (isset($_SESSION['son_meta'])) {
               unset($_SESSION['son_meta']);
            }
         }

         $options = ['step'              => $_POST['step'],
                     'metademands_id'    => $_POST['metademands_id'],
                     'itilcategories_id' => $itilcategories
         ];

         $wizard->showWizard($options);
      }

      if (Session::getCurrentInterface() != 'central'
          && $plugin->isActivated('servicecatalog')) {

         PluginServicecatalogMain::showNavBarFooter('metademands');
      }

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['return'])) {
      if (isset($_SESSION['metademands_hide'])) {
         unset($_SESSION['metademands_hide']);
      }

      Html::redirect($wizard->getFormURL() . "?step=" . PluginMetademandsMetademand::STEP_INIT);

   } else if (isset($_POST['add_to_basket'])) {

      $KO   = false;
      $step = PluginMetademandsMetademand::STEP_SHOW;

      $checks  = [];
      $content = [];
      $data    = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                                'is_basket'                         => 1]);


      //Clean $post & $data & $_POST
      $dataOld = $data;
      $post    = $_POST['field'];
      // Double appel for prevent order fields
      PluginMetademandsWizard::unsetHidden($data, $post);
      PluginMetademandsWizard::unsetHidden($dataOld, $post);
      $_POST['field'] = $post;


      foreach ($data as $id => $value) {

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
         //            if ($value['type'] == 'informations'
         //                || $value['type'] == 'title') {
         //               if (!isset($_POST['field_basket_' . $line][$id])) {
         //                  $_POST['field_basket_' . $line][$id] = "";
         //               }
         //            }
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

      if ($KO === false && count($content) > 0) {
         $basketline = new PluginMetademandsBasketline();
         $basketline->addToBasket($content, $_POST['form_metademands_id']);
      } else {
         Session::addMessageAfterRedirect(__("There is a problem with the basket", "metademands"), false, ERROR);
      }
      Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $step);

   } else if (isset($_POST['update_basket_line'])) {

      $line = $_POST['update_basket_line'];
      if (isset($_POST['field_basket_' . $line])) {
         $KO = false;

         $checks  = [];
         $content = [];
         $data    = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);

         foreach ($data as $id => $value) {

            if ($value['type'] == 'radio') {
               if (!isset($_POST['field_basket_' . $line][$id])) {
                  $_POST['field_basket_' . $line][$id] = NULL;
               }
            }
            if ($value['type'] == 'checkbox') {
               if (!isset($_POST['field_basket_' . $line][$id])) {
                  $_POST['field_basket_' . $line][$id] = "";
               }
            }
            if ($value['type'] == 'informations'
                || $value['type'] == 'title') {
               if (!isset($_POST['field_basket_' . $line][$id])) {
                  $_POST['field_basket_' . $line][$id] = "";
               }
            }
            if ($value['item'] == 'ITILCategory_Metademands') {
               $_POST['field_basket_' . $line][$id] = isset($_POST['basket_plugin_servicecatalog_itilcategories_id']) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
            }
            $fieldname = 'field_basket_' . $line;
            $checks[]  = PluginMetademandsWizard::checkvalues($value, $id, $_POST, $fieldname, true);

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

   } else if (isset($_POST['delete_basket_line'])) {

      $basketline = new PluginMetademandsBasketline();
      $basketline->deleteFromBasket($_POST);

      Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);

   } else if (isset($_POST['delete_basket_file'])) {

      $basketline = new PluginMetademandsBasketline();
      $basketline->deleteFileFromBasket($_POST);

      Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);

   } else if (isset($_POST['clear_basket'])) {

      $basketline = new PluginMetademandsBasketline();
      $basketline->deleteByCriteria(['plugin_metademands_metademands_id' => $_POST['metademands_id'],
                                     'users_id'                          => Session::getLoginUserID()]);

      Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . PluginMetademandsMetademand::STEP_SHOW);

      //   } else if (isset($_POST['save_draft'])) {
      //      $nblines = 0;
      //      $KO      = false;
      //      //Create ticket
      //      if ($nblines == 0) {
      //         $post    = $_POST['field'];
      //         $nblines = 1;
      //      }
      //
      //      if ($KO === false) {
      //
      //         $checks  = [];
      //         $content = [];
      //
      //         for ($i = 0; $i < $nblines; $i++) {
      //
      //
      //            //            //Clean $post & $data & $_POST
      //            //            $data  = $fields->find(['plugin_metademands_metademands_id' => $_POST['metademands_id']]);
      //            //            $dataOld = $data;
      //            //            // Double appel for prevent order fields
      //            //            PluginMetademandsWizard::unsetHidden($data, $post);
      //            //            PluginMetademandsWizard::unsetHidden($dataOld, $post);
      //
      //            $_POST['field']   = $post;
      //            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
      //            if (count($metademands_data)) {
      //               foreach ($metademands_data as $form_step => $data) {
      //                  $docitem = null;
      //                  foreach ($data as $form_metademands_id => $line) {
      //                     foreach ($line['form'] as $id => $value) {
      //                        if (!isset($post[$id])) {
      //                           if (isset($_SESSION['plugin_metademands']['fields'][$id]) && $value['plugin_metademands_metademands_id'] != $_POST['form_metademands_id']) {
      //                              $_POST['field'][$id] = $_SESSION['plugin_metademands']['fields'][$id];
      //                           } else {
      //                              $_POST['field'][$id] = [];
      //                           }
      //                        } else  {
      //                           $_SESSION['plugin_metademands']['fields'][$id] = $post[$id];
      //                        }
      //
      //                        if ($value['type'] == 'radio') {
      //                           if (!isset($_POST['field'][$id])) {
      //                              $_POST['field'][$id] = NULL;
      //                           }
      //                        }
      //                        if ($value['type'] == 'checkbox') {
      //                           if (!isset($_POST['field'][$id])) {
      //                              $_POST['field'][$id] = 0;
      //                           }
      //                        }
      //                        if ($value['type'] == 'informations'
      //                            || $value['type'] == 'title') {
      //                           if (!isset($_POST['field'][$id])) {
      //                              $_POST['field'][$id] = 0;
      //                           }
      //                        }
      //                        if ($value['item'] == 'ITILCategory_Metademands') {
      //                           $_POST['field'][$id] = isset($_POST['field_plugin_servicecatalog_itilcategories_id']) ? $_POST['field_plugin_servicecatalog_itilcategories_id'] : 0;
      //                        }
      //                     }
      //
      //                  }
      //               }
      //            }
      //            $metademands->getFromDB($_POST['metademands_id']);
      //            if ($KO === false) {
      //               // Save requester user
      //               $_SESSION['plugin_metademands']['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
      //               // Case of simple ticket convertion
      //               $_SESSION['plugin_metademands']['fields']['tickets_id'] = $_POST['tickets_id'];
      //               // Resources id
      //               $_SESSION['plugin_metademands']['fields']['resources_id'] = $_POST['resources_id'];
      //               // Resources step
      //               $_SESSION['plugin_metademands']['fields']['resources_step'] = $_POST['resources_step'];
      //
      //               //Category id if have category field
      //               $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] = isset($_POST['field_plugin_servicecatalog_itilcategories_id']) ? $_POST['field_plugin_servicecatalog_itilcategories_id'] : 0;
      //               $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] =
      //                  (isset($_POST['basket_plugin_servicecatalog_itilcategories_id']) && $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
      //               $_SESSION['plugin_metademands']['field_type']                                    = $metademands->fields['type'];
      //            }
      //
      //            $drafts = new PluginMetademandsDraft();
      //            if (isset($_POST['plugin_metademands_drafts_id']) && !empty($_POST['plugin_metademands_drafts_id'])) {
      //               $draft_id = $_POST['plugin_metademands_drafts_id'];
      //               $drafts->getFromDB($_POST['plugin_metademands_drafts_id']);
      //               $drafts_values = new PluginMetademandsDraft_Value();
      //               $drafts_values->deleteByCriteria(['plugin_metademands_drafts_id' => $draft_id]);
      //               $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
      //               if (count($metademands_data)) {
      //                  foreach ($metademands_data as $form_step => $data) {
      //                     $docitem = null;
      //                     foreach ($data as $form_metademands_id => $line) {
      //                        PluginMetademandsDraft::setDraftValues($line['form'], $_POST['field'], $draft_id);
      //                     }
      //                  }
      //               }
      //               PluginMetademandsDraft::loadDraftValues($draft_id);
      //            } else {
      //               if (!isset($_POST['draft_name']) || (isset($_POST['draft_name']) && empty($_POST['draft_name']))) {
      //                  Session::addMessageAfterRedirect(__('Draft name is required', 'metademands'), false, ERROR);
      //                  Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $_POST['step']);
      //               }
      //               $inputs         = [];
      //               $inputs['name'] = Toolbox::addslashes_deep($_POST['draft_name']);
      //               //               $inputs['name'] = 'd1';
      //               $inputs['users_id']                          = Session::getLoginUserID();
      //               $inputs['plugin_metademands_metademands_id'] = $_POST['metademands_id'];
      //               $inputs['date']                              = date('Y-m-d H:i:s');
      //
      //               $draft_id         = $drafts->add($inputs);
      //               $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
      //               if (count($metademands_data)) {
      //                  foreach ($metademands_data as $form_step => $data) {
      //                     $docitem = null;
      //                     foreach ($data as $form_metademands_id => $line) {
      //                        PluginMetademandsDraft::setDraftValues($line['form'], $_POST['field'], $draft_id);
      //                     }
      //                  }
      //               }
      //
      //
      //            }
      //            $_SESSION['plugin_metademands']['plugin_metademands_drafts_id']   = $draft_id;
      //            $_SESSION['plugin_metademands']['plugin_metademands_drafts_name'] = $_POST['draft_name'];
      //
      //
      //         }
      //      }
      //      Session::addMessageAfterRedirect(__('Draft saved', 'metademands'), false);
      //      Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $_POST['step']);
      //
   } else if (isset($_POST['clean_form'])) {

      unset($_SESSION['plugin_metademands']);
      Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $_POST['step']);

   } else {
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmenu", "wizard");

      } else {
         $plugin = new Plugin();
         if ($plugin->isActivated('servicecatalog')) {
            PluginServicecatalogMain::showDefaultHeaderHelpdesk(__('Create a demand', 'metademands'));
         } else {
            Html::helpHeader(__('Create a demand', 'metademands'));
         }

      }

      if (isset($_SESSION['metademands_hide'])) {
         unset($_SESSION['metademands_hide']);
      }
      $itilcategories_id = 0;
      if (isset($_GET['itilcategories_id']) && $_GET['itilcategories_id'] > 0) {
         $itilcategories_id = $_GET['itilcategories_id'];
      }
//      if (!isset($_GET['itilcategories_id']) && isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
//         $itilcategories_id = $_SESSION['servicecatalog']['sc_itilcategories_id'];
//      }

      $options = ['step'              => $_GET['step'],
                  'metademands_id'    => $_GET['metademands_id'],
                  'preview'           => false,
                  'tickets_id'        => $_GET['tickets_id'],
                  'resources_id'      => $_GET['resources_id'],
                  'resources_step'    => $_GET['resources_step'],
                  'itilcategories_id' => $itilcategories_id];

      if (isset($_GET['see_form']) && $_GET['see_form'] > 0) {
         $options['seeform'] = true;
      }
      if (isset($_GET['current_ticket_id']) && $_GET['current_ticket_id'] > 0) {
         $options['current_ticket_id'] = $_GET['current_ticket_id'];
      }
      if (isset($_GET['meta_validated'])) {
         if($_GET['meta_validated'] > 0) {
            $options['meta_validated'] = true;
         } else {
            $options['meta_validated'] = false;
         }

      }
      $wizard->showWizard($options);

      if (Session::getCurrentInterface() != 'central'
          && $plugin->isActivated('servicecatalog')) {

         PluginServicecatalogMain::showNavBarFooter('metademands');
      }


      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }
   }
