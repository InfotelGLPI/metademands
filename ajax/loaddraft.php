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

$KO = false;

$metademands = new PluginMetademandsMetademand();
$wizard      = new PluginMetademandsWizard();
$draft      = new PluginMetademandsDraft();

if ($draft->getFromDB($_POST['plugin_metademands_drafts_id'])) {

   $metademands->getFromDB($_POST['metademands_id']);
   PluginMetademandsDraft_Value::loadDraftValues($_POST['metademands_id'], $_POST['plugin_metademands_drafts_id']);
   $draft_name = $draft->getField('name');

   $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['_users_id_requester'] = $_POST['_users_id_requester'];
   // Case of simple ticket convertion
    if (isset($_POST['items_id']) && $_POST['itemtype'] == 'Ticket') {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['tickets_id'] = $_POST['items_id'];
    }
   // Resources id
    if (isset($_POST['resources_id'])) {
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_id'] = $_POST['resources_id'];
        // Resources step
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields']['resources_step'] = $_POST['resources_step'];
    }
   //Category id if have category field
   $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] = isset($_POST['field_plugin_servicecatalog_itilcategories_id']) ? $_POST['field_plugin_servicecatalog_itilcategories_id'] : 0;
   $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] =
      (isset($_POST['basket_plugin_servicecatalog_itilcategories_id'])
          && $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_servicecatalog_itilcategories_id'] == 0) ? $_POST['basket_plugin_servicecatalog_itilcategories_id'] : 0;
//   $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_type']                                    = $metademands->fields['type'];
    if(isset($_POST['field_plugin_requestevolutions_itilcategories_id'])){
        //Category id if have category field
        $_SESSION['plugin_metademands'][$_POST['metademands_id']]['field_plugin_requestevolutions_itilcategories_id'] = $_POST['field_plugin_requestevolutions_itilcategories_id'];
    }
    $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_drafts_id']                  = $_POST['plugin_metademands_drafts_id'];
   $_SESSION['plugin_metademands'][$_POST['metademands_id']]['plugin_metademands_drafts_name']                = $draft_name;

} else {
   $KO = true;
}
if ($KO === false) {
   echo 0;
} else {
   echo $KO;
}
