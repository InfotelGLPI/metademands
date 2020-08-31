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
   $_GET['step'] = "1";
}

// Url Redirect case
if (isset($_GET['id'])) {
   $_GET['metademands_id'] = $_GET['id'];
   $_GET['step']           = "2";
   $_GET['tickets_id']     = "0";
}

if (isset($_POST['next'])) {
   $KO   = false;
   $step = $_POST['step'] + 1;
   if (isset($_POST['update_fields'])) {
      if ($metademands->canCreate()
          || PluginMetademandsGroup::isUserHaveRight($_POST['form_metademands_id'])) {

         $data = $field->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
         foreach ($data as $id => $value) {
            if ($value['type'] == 'datetime_interval' && !isset($value['second_date_ok'])) {
               $value['second_date_ok'] = true;
               $value['id']             = $id . '-2';
               $value['label']          = $value['label2'];
               $data[$id . '-2']        = $value;
            }
            // Check if no form values block the creation of meta
            $metademandtasks_tasks_id = PluginMetademandsMetademandTask::getSonMetademandTaskId($_POST['form_metademands_id']);

            if (isset($_POST['field'][$id])
                && !empty($value['check_value'])
                && $metademandtasks_tasks_id == $value['plugin_metademands_tasks_id']) {
               if (!PluginMetademandsTicket_Field::isCheckValueOK($_POST['field'][$id], $value['check_value'], $value['type'])) {
                  $step = $_POST['step'] + 2;
               }
            }
         }

         foreach ($data as $id => $value) {

            if (isset($_POST['field'][$id])) {
               if (!$wizard->checkMandatoryFields($value, ['id' => $id, 'value' => $_POST['field'][$id]], $_POST['field'])) {
                  $KO = true;
               }
               foreach ($_POST['field'] as $key => $field) {
                  $field                = str_replace('\r\n', '&#x0A;', $field);
                  $_POST['field'][$key] = $field;
               }

               if ($value == 'checkbox') {// Checkbox
                  $_SESSION['plugin_metademands']['fields'][$id] = 1;
               } else {// Other fields
                  if (is_array($_POST['field'][$id]) && $value['type'] !== 'dropdown_multiple') {
                     $_POST['field'][$id] = PluginMetademandsField::_serialize($_POST['field'][$id]);
                  }
                  $_SESSION['plugin_metademands']['fields'][$id] = $_POST['field'][$id];
               }

            } else if ($value['type'] == 'checkbox') {
               if (!isset($_POST['field'])
                   || (isset($_POST['field']) && $wizard->checkMandatoryFields($value, ['id' => $id, 'value' => ''], $_POST['field']))) {
                  $_SESSION['plugin_metademands']['fields'][$id] = '';
               } else {
                  $KO = true;
               }
            } else if ($value['type'] == 'radio') {
               if ($value['is_mandatory'] == 1) {
                  if (isset($_POST['radio'])
                      && $wizard->checkMandatoryFields($value, ['id' => $id, 'value' => $_POST['radio'][$id]])) {
                     $_SESSION['plugin_metademands']['fields'][$id] = $_POST['radio'][$id];
                  } else {
                     $KO = true;
                  }
               } else if (isset($_POST['radio'][$id])) {
                  $_SESSION['plugin_metademands']['fields'][$id] = $_POST['radio'][$id];
               }
            } else if ($value['type'] == 'upload') {
               if (!$wizard->checkMandatoryFields($value, ['id' => $id, 'value' => 1])) {
                  $KO = true;
               } else {
                  if (isset($_POST['_filename'])) {
                     $_SESSION['plugin_metademands']['fields']['_filename'] = $_POST['_filename'];
                  }
                  if (isset($_POST['_prefix_filename'])) {
                     $_SESSION['plugin_metademands']['fields']['_prefix_filename'] = $_POST['_prefix_filename'];
                  }
               }
            }
         }

         $ticketfields_data = $metademands->formatTicketFields($_POST['form_metademands_id']);
         if (count($ticketfields_data)) {
            if (!isset($ticketfields_data['entities_id'])) {
               $ticketfields_data['entities_id'] = $_SESSION['glpiactive_entity'];
            }
            $metademands->getFromDB($_POST['form_metademands_id']);
            $ticketfields_data['itilcategories_id'] = $metademands->fields['itilcategories_id'];
            $tickettasks                            = new PluginMetademandsTicketTask();
            if (!$tickettasks->isMandatoryField($ticketfields_data, true, false, __('Mandatory fields of the metademand ticket must be configured', 'metademands'))) {
               $KO = true;
            }
         }

         // Save requester user
         $_SESSION['plugin_metademands']['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
         // Case of simple ticket convertion
         $_SESSION['plugin_metademands']['fields']['tickets_id'] = $_POST['tickets_id'];
         // Resources id
         $_SESSION['plugin_metademands']['fields']['resources_id'] = $_POST['resources_id'];
         // Resources step
         $_SESSION['plugin_metademands']['fields']['resources_step'] = $_POST['resources_step'];


         // FILE UPLOAD
         if (isset($_FILES['filename']['tmp_name'])) {
            if (!isset($_SESSION['plugin_metademands']['files'][$_POST['form_metademands_id']])) {
               foreach ($_FILES['filename']['tmp_name'] as $key => $tmp_name) {

                  if (!empty($tmp_name)) {
                     $_SESSION['plugin_metademands']['files'][$_POST['form_metademands_id']][$key]['base64'] = base64_encode(file_get_contents($tmp_name));
                     $_SESSION['plugin_metademands']['files'][$_POST['form_metademands_id']][$key]['name']   = $_FILES['filename']['name'][$key];
                  }
               }
            }
            unset($_FILES['filename']);
         }
      }
      if ($KO) {
         $step = $_POST['step'];
      } else if (isset($_POST['add_metademands'])) {
         $step = 'add_metademands';
      }

   }

   if (Session::getCurrentInterface() == 'central') {
      Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
   } else {
      Html::helpHeader(__('Create a demand', 'metademands'));
   }

   $wizard->showWizard($step, $_POST['metademands_id']);

   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }

} else if (isset($_POST['previous'])) {
   if (Session::getCurrentInterface() == 'central') {
      Html::header(__('Create a demand', 'metademands'), '', "helpdesk", "pluginmetademandsmetademand");
   } else {
      Html::helpHeader(__('Create a demand', 'metademands'));
   }

   // Resource previous wizard steps
   if ($_POST['step'] == 2 && !empty($_POST['resources_id']) && !empty($_POST['resources_step'])) {
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
      }
      // Else metademand wizard step
   } else {
      switch ($_POST['step']) {
         case 1:
            $_POST['step'] = 'initWizard';
            break;
         default:
            $_POST['step'] = $_POST['step'] - 1;
            break;
      }
      $plugin = new Plugin();
      if ($plugin->isActivated('servicecatalog')
          && $_POST['step'] == 1
          && Session::haveRight("plugin_servicecatalog", READ)) {
         Html::redirect($CFG_GLPI["root_doc"] . "/plugins/servicecatalog/front/main.form.php?choose_category&type=metademands");
      }
      $wizard->showWizard($_POST['step'], $_POST['metademands_id']);
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

   $wizard->showWizard('initWizard');

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
      $wizard->showWizard('initWizard');
   }
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

   $wizard->showWizard($_GET['step'], $_GET['metademands_id'], false, $_GET['tickets_id'], $_GET['resources_id'], $_GET['resources_step'], $_GET['itilcategories_id']);

   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }
}