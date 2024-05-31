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


if (isset($_POST['save_draft'])) {
   $nblines = 0;
   $KO      = false;
   //Create ticket
   if ($nblines == 0) {
       if(isset($_POST['field'])){
           $post    =  $_POST['field'];
       }else{
           $post['field']  = [];
       }

       $nblines = 1;
   }

   if ($KO === false) {

      $checks  = [];
      $content = [];

      for ($i = 0; $i < $nblines; $i++) {

          if (isset($_POST['_filename'])) {
              foreach ($_POST['_filename'] as $key => $filename) {
                  $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']['_prefix_filename'][] = $_POST['_prefix_filename'][$key];
                  $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']['_tag_filename'][] = $_POST['_tag_filename'][$key];
                  $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields']['files']['_filename'][] = $_POST['_filename'][$key];
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

          if (!isset($_POST['field']) || !is_array($_POST['field'])) {
              $_POST['field'] = [];
          }

         if (count($metademands_data)) {
            foreach ($metademands_data as $form_step => $data) {
               $docitem = null;
               foreach ($data as $form_metademands_id => $line) {
                  foreach ($line['form'] as $id => $value) {
                     if (!isset($post[$id])) {
                        if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id])
                            && $value['plugin_metademands_metademands_id'] != $_POST['form_metademands_id']) {
                           $_POST['field'][$id] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id];
                        }
                     } else {
                        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$id] = $post[$id];
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

                      if ($value['type'] == 'basket' && isset($_POST['quantity'])) {
                          $_POST['field'][$id] = $_POST['quantity'][$id];
                      }

                      if ($value['type'] == 'free_input' && isset($_POST['freeinputs']) && !empty($_POST['freeinputs'])) {

                          $_POST['field'][$id] = $_POST['freeinputs'];
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
              $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
                  (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
                      && $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
//                $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_type']                                    = $metademands->fields['type'];
          }

         $drafts = new PluginMetademandsDraft();
         if (isset($_POST['plugin_metademands_drafts_id'])
             && !empty($_POST['plugin_metademands_drafts_id'])) {
            $draft_id = $_POST['plugin_metademands_drafts_id'];
            $drafts->getFromDB($_POST['plugin_metademands_drafts_id']);
            $drafts_values = new PluginMetademandsDraft_Value();
            $drafts_values->deleteByCriteria(['plugin_metademands_drafts_id' => $draft_id]);
            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
            if (count($metademands_data)) {
               foreach ($metademands_data as $form_step => $data) {
                  $docitem = null;
                  foreach ($data as $form_metademands_id => $line) {
                     PluginMetademandsDraft_Value::setDraftValues($_POST['metademands_id'], $line['form'], $_POST['field'], $draft_id);
                  }
               }
            }
            PluginMetademandsDraft_Value::loadDraftValues($_POST['metademands_id'], $draft_id);
            $_POST['draft_name'] = $drafts->getField('name');
         } else {
            if (!isset($_POST['draft_name']) || (isset($_POST['draft_name']) && empty($_POST['draft_name']))) {
               Session::addMessageAfterRedirect(__('Draft name is required', 'metademands'), false, ERROR);
               //               Html::redirect($wizard->getFormURL() . "?metademands_id=" . $_POST['metademands_id'] . "&step=" . $_POST['step']);
               break;
            }
            $inputs         = [];
            $inputs['name'] = Toolbox::addslashes_deep($_POST['draft_name']);
            //               $inputs['name'] = 'd1';
            $inputs['users_id']                          = Session::getLoginUserID();
            $inputs['plugin_metademands_metademands_id'] = $_POST['metademands_id'];
            $inputs['date']                              = date('Y-m-d H:i:s');

            $draft_id         = $drafts->add($inputs);
            $metademands_data = $metademands->constructMetademands($_POST['metademands_id']);
            if (count($metademands_data)) {
               foreach ($metademands_data as $form_step => $data) {
                  $docitem = null;
                  foreach ($data as $form_metademands_id => $line) {
                     PluginMetademandsDraft_Value::setDraftValues($_POST['metademands_id'], $line['form'], $_POST['field'], $draft_id);
                  }
               }
            }
         }
         $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_drafts_id']   = $draft_id;
         $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_drafts_name'] = $_POST['draft_name'];

      }
   }
}

if ($KO === false) {
   echo 0;
} else {
   echo $KO;
}



