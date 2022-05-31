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

if (isset($_POST['save_form'])) {
   $nblines = 0;
   $KO      = false;

   if ($nblines == 0) {
      $post    = $_POST['field'];
      $nblines = 1;
   }

   if ($KO === false) {

      $checks  = [];
      $content = [];

      for ($i = 0; $i < $nblines; $i++) {

         $_POST['field']   = $post;
         $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
         if (count($metademands_data)) {
            foreach ($metademands_data as $form_step => $data) {
               $docitem = null;
               foreach ($data as $form_metademands_id => $line) {
                  foreach ($line['form'] as $id => $value) {
                     if (!isset($post[$id])) {
                        if (isset($_SESSION['plugin_metademands']['fields'][$id])
                            && $value['plugin_metademands_metademands_id'] != $_POST['form_metademands_id']) {
                           $_POST['field'][$id] = $_SESSION['plugin_metademands']['fields'][$id];
                        } else {
                           $_POST['field'][$id] = [];
                        }
                     } else {
                        $_SESSION['plugin_metademands']['fields'][$id] = $post[$id];
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
                  }

               }
            }
         }
         $metademands->getFromDB($_POST['metademands_id']);
         if ($KO === false) {
            // Save requester user
            $_SESSION['plugin_metademands']['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
            // Case of simple ticket convertion
            if (isset($_POST['items_id']) && $_POST['itemtype'] == 'Ticket') {
               $_SESSION['plugin_metademands']['fields']['tickets_id'] = $_POST['items_id'];
            }
            // Resources id
            $_SESSION['plugin_metademands']['fields']['resources_id'] = $_POST['resources_id'];
            // Resources step
            $_SESSION['plugin_metademands']['fields']['resources_step'] = $_POST['resources_step'];

            //Category id if have category field
            $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
            $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] =
               (isset($_POST['basket_plugin_servicecatalog_itilcategories_id']) && $_SESSION['plugin_metademands']['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
            $_SESSION['plugin_metademands']['field_type']                                    = $metademands->fields['type'];
         }

         $forms = new PluginMetademandsForm();
         //         if (isset($_POST['plugin_metademands_forms_id'])
         //             && !empty($_POST['plugin_metademands_forms_id'])) {
         //            $form_id = $_POST['plugin_metademands_forms_id'];
         //            $forms->getFromDB($_POST['plugin_metademands_forms_id']);
         //            $forms_values = new PluginMetademandsForm_Value();
         //            $forms_values->deleteByCriteria(['plugin_metademands_forms_id' => $form_id]);
         //            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
         //            if (count($metademands_data)) {
         //               foreach ($metademands_data as $form_step => $data) {
         //                  $docitem = null;
         //                  foreach ($data as $form_metademands_id => $line) {
         //                     PluginMetademandsForm_Value::setFormValues($line['form'], $_POST['field'], $form_id);
         //                  }
         //               }
         //            }
         //            PluginMetademandsForm_Value::loadFormValues($form_id);
         //            $_POST['form_name'] = $forms->getField('name');
         //         } else {
         if (!isset($_POST['form_name']) || (isset($_POST['form_name']) && empty($_POST['form_name']))) {
            Session::addMessageAfterRedirect(__('Form name is required', 'metademands'), false, ERROR);
            break;
         }
         $inputs                                      = [];
         $inputs['name']                              = Toolbox::addslashes_deep($_POST['form_name']);
         $inputs['users_id']                          = Session::getLoginUserID();
         $inputs['plugin_metademands_metademands_id'] = $_POST['metademands_id'];
         $inputs['date']                              = date('Y-m-d H:i:s');
         if (isset($_POST['is_model'])) {
            $inputs['is_model'] = $_POST['is_model'];
         }

         if (isset($_POST['resources_id']) && $_POST['resources_id'] > 0) {
            $resForm = $forms->find(['plugin_metademands_metademands_id' => $_POST['metademands_id'],
                                     'resources_id'                      => $_POST['resources_id']]);
            if (count($resForm)) {
               foreach ($resForm as $res) {
                  $last = $res['id'];
               }
            } else {
               $last = 0;
            }
            $_SESSION['plugin_metademands']['form_to_compare'] = $last;
         }

         if ($form_new_id = $forms->add($inputs)) {
            $_SESSION['plugin_metademands']['plugin_metademands_forms_id']   = $form_new_id;
            $_SESSION['plugin_metademands']['plugin_metademands_forms_name'] = $_POST['form_name'];

            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
            if (count($metademands_data) && $form_new_id > 0) {
               foreach ($metademands_data as $form_step => $data) {
                  $docitem = null;
                  foreach ($data as $form_metademands_id => $line) {
                     PluginMetademandsForm_Value::setFormValues($line['form'], $_POST['field'], $form_new_id);
                  }
               }
            }
         } else {
            $KO = false;
         }
         //         }
      }
   }
}
if ($KO === false) {
   echo 0;
} else {
   echo $KO;
}



