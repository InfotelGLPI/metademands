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

$KO          = false;
$step        = $_POST['step'] + 1;
$metademands = new PluginMetademandsMetademand();
$wizard      = new PluginMetademandsWizard();
$fields      = new PluginMetademandsField();

if (isset($_POST['update_fields'])) {
   if ($metademands->canCreate()
       || PluginMetademandsGroup::isUserHaveRight($_POST['form_metademands_id'])) {


      $data = $fields->find(['plugin_metademands_metademands_id' => $_POST['form_metademands_id']]);
      $metademands->getFromDB($_POST['form_metademands_id']);

      $meta   = [];
      if (Plugin::isPluginActive('orderprojects')
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
            $post = $_POST['field'];
            if (isset($_POST['field_plugin_servicecatalog_itilcategories_id_key'])
                && isset($_POST['field_plugin_servicecatalog_itilcategories_id'])) {
               $post[$_POST['field_plugin_servicecatalog_itilcategories_id_key']] = $_POST['field_plugin_servicecatalog_itilcategories_id'];
            }
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
                  $toBeMandatory = PluginMetademandsWizard::getMandatoryFields($id, $value, $_POST['field']);
                  if (is_array($toBeMandatory) && !empty($toBeMandatory)) {
                     foreach ($toBeMandatory as $keyMandatory => $valueMandatory) {
                        if (isset($data[$valueMandatory]['type'])) {
                           $data[$valueMandatory]['is_mandatory'] = true;
                        }
                     }
                  }
               }

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
                        $value['check_value'] = $check_value;
                        if (isset(PluginMetademandsField::_unserialize($value['hidden_link'])[$keyId])) {
                           $value['plugin_metademands_tasks_id'] = PluginMetademandsField::_unserialize($value['hidden_link'])[$keyId];
                        }
                        $value['fields_link'] = isset(PluginMetademandsField::_unserialize($value['fields_link'])[$keyId]) ? PluginMetademandsField::_unserialize($value['fields_link'])[$keyId] : 0;
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
                  $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
                  $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] =
                     (isset($_POST['basket_plugin_servicecatalog_itilcategories_id']) && $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'];
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
if ($KO === false) {
   echo 0;
} else {
   echo $KO;
}



