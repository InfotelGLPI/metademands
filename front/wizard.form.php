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
$field       = new PluginMetademandsField();

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
}

if (empty($_GET['resources_step'])) {
   $_GET['resources_step'] = '';
}

if (empty($_GET['itilcategories_id'])) {
   $_GET['itilcategories_id'] = '';
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
   $KO              = false;
   $onlybasketdatas = false;
   $step            = $_POST['step'] + 1;
   if (isset($_POST['update_fields'])) {

      if ($metademands->canCreate()
          || PluginMetademandsGroup::isUserHaveRight($_POST['form_metademands_id'])) {

         $field = new PluginMetademandsField();
         $data  = $field->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
         $metademands->getFromDB($_POST['form_metademands_id']);
         $plugin = new Plugin();
         if ($plugin->isActivated('orderprojects') && $metademands->fields['is_order'] == 1) {
            $orderprojects   = new PluginOrderprojectsMetademand();
            $onlybasketdatas = true;
            $meta            = $orderprojects->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
            if ($meta) {
               $orderprojects->createFromMetademands($_POST);
               Html::back();
            }
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

                  $basketline->deleteByCriteria(['plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                                                 'users_id'                          => Session::getLoginUserID()]);
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

                  foreach ($data as $id => $value) {

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
                           $value['plugin_metademands_tasks_id'] = PluginMetademandsField::_unserialize($value['plugin_metademands_tasks_id'])[$keyId];
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
                     if ($value['item'] == 'itilcategory') {
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
                  if ($KO) {
                     $step = $_POST['step'];
                  } else if (isset($_POST['create_metademands'])) {
                     $step = PluginMetademandsMetademand::STEP_CREATE;
                  }
               }
            }
         }
      }
   }

   if (Session::getCurrentInterface() == 'central') {
      Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
   } else {
      Html::helpHeader(__('Create a demand', 'metademands'));
   }

   $options = ['step'           => $step,
               'metademands_id' => $_POST['metademands_id']];
   $wizard->showWizard($options);

   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }

} else
   if (isset($_POST['previous'])) {
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

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
            Html::redirect($CFG_GLPI["root_doc"] . "/plugins/servicecatalog/front/main.form.php?choose_category&type=metademands");
         } else if ($_POST['step'] == PluginMetademandsMetademand::STEP_SHOW) {
            if (isset($_SESSION['metademands_hide'])) {
               unset($_SESSION['metademands_hide']);
            }
            if (isset($_SESSION['son_meta'])) {
               unset($_SESSION['son_meta']);
            }
         }
         $options = ['step' => $_POST['step'], 'metademands_id' => $_POST['metademands_id']];
         $wizard->showWizard($options);
      }

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['return'])) {
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      $wizard->showWizard($options['step'] = PluginMetademandsMetademand::STEP_INIT);

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['upload_files'])) {
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      if (!empty($_FILES['filename']['tmp_name'][0])) {
         $wizard->uploadFiles($_POST);
      } else {
         $wizard->showWizard($options['step'] = PluginMetademandsMetademand::STEP_INIT);
      }
      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['add_to_basket'])) {

      $KO   = false;
      $step = PluginMetademandsMetademand::STEP_SHOW;

      $checks  = [];
      $content = [];
      $data    = $field->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
                               'is_basket'                         => 1]);

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
         if ($value['type'] == 'informations'
             || $value['type'] == 'title') {
            if (!isset($_POST['field'][$id])) {
               $_POST['field'][$id] = 0;
            }
         }
         if ($value['item'] == 'itilcategory') {
            $_POST['field'][$id] = isset($_POST['field_plugin_servicecatalog_itilcategories_id'])?$_POST['field_plugin_servicecatalog_itilcategories_id']:0;
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

         $basketline = new PluginMetademandsBasketline();
         $basketline->addToBasket($content, $_POST['form_metademands_id']);
      }
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      $options = ['step'           => $step,
                  'metademands_id' => $_POST['metademands_id']];

      $wizard->showWizard($options);

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['update_basket_line'])) {

      $line = $_POST['update_basket_line'];
      if (isset($_POST['field_basket_' . $line])) {
         $KO = false;

         $checks  = [];
         $content = [];
         $data = $field->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);

         foreach ($data as $id => $value) {

            if ($value['type'] == 'radio') {
               if (!isset($_POST['field_basket_' . $line][$id])) {
                  $_POST['field_basket_' . $line][$id] = NULL;
               }
            }
            if ($value['type'] == 'checkbox') {
               if (!isset($_POST['field_basket_' . $line][$id])) {
                  $_POST['field_basket_' . $line][$id] = 0;
               }
            }
            if ($value['type'] == 'informations'
                || $value['type'] == 'title') {
               if (!isset($_POST['field_basket_' . $line][$id])) {
                  $_POST['field_basket_' . $line][$id] = "";
               }
            }
            if ($value['item'] == 'itilcategory') {
               $_POST['field_basket_' . $line][$id] = isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])?$_POST['basket_plugin_servicecatalog_itilcategories_id']:0;
            }
            $fieldname = 'field_basket_' . $line;
            $checks[] = PluginMetademandsWizard::checkvalues($value, $id, $_POST, $fieldname, true);

            if ($value['type'] == 'upload') {
               if (isset($_POST['_filename'])) {
                  foreach ($_POST['_filename'] as $key => $filename) {
                     $files[$key]['_prefix_filename'] = $_POST['_prefix_filename'][$key];
                     $files[$key]['_tag_filename']    = $_POST['_tag_filename'][$key];
                     $files[$key]['_filename']        = $_POST['_filename'][$key];
                  }

                  $_POST['field_basket_' . $line][$id] = json_encode($files);
               }
            }
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
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      $options = ['step'           => PluginMetademandsMetademand::STEP_SHOW,
                  'metademands_id' => $_POST['metademands_id']];

      $wizard->showWizard($options);

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['delete_basket_line'])) {

      $basketline = new PluginMetademandsBasketline();
      $basketline->deleteFromBasket($_POST);

      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      $options = ['step'           => PluginMetademandsMetademand::STEP_SHOW,
                  'metademands_id' => $_POST['metademands_id']];

      $wizard->showWizard($options);

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else if (isset($_POST['delete_basket_file'])) {

      $basketline = new PluginMetademandsBasketline();
      $basketline->deleteFileFromBasket($_POST);

      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      $options = ['step'           => PluginMetademandsMetademand::STEP_SHOW,
                  'metademands_id' => $_POST['metademands_id']];

      $wizard->showWizard($options);

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }

   } else {
      if (Session::getCurrentInterface() == 'central') {
         Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");

      } else {
         Html::helpHeader(__('Create a demand', 'metademands'));
      }

      if (isset($_SESSION['metademands_hide'])) {
         unset($_SESSION['metademands_hide']);
      }
      $options = ['step'              => $_GET['step'],
                  'metademands_id'    => $_GET['metademands_id'],
                  'preview'           => false,
                  'tickets_id'        => $_GET['tickets_id'],
                  'resources_id'      => $_GET['resources_id'],
                  'resources_step'    => $_GET['resources_step'],
                  'itilcategories_id' => $_GET['itilcategories_id']];

      $wizard->showWizard($options);

      if (Session::getCurrentInterface() == 'central') {
         Html::footer();
      } else {
         Html::helpFooter();
      }
   }