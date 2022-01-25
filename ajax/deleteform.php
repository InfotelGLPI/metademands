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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$users_id                          = $_POST['users_id'];
$plugin_metademands_metademands_id = $_POST['plugin_metademands_metademands_id'];
$form_id                          = $_POST['forms_id'];

$self = new PluginMetademandsForm();
$self->deleteByCriteria(['id' => $form_id]);

$values = new PluginMetademandsForm_Value();
$values->deleteByCriteria(['plugin_metademands_forms_id' => $form_id]);

$forms = $self->find(['users_id'                          => $users_id,
                       'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
                      'is_model' => 1]);
if($_POST['self_delete'] == true){
   unset($_SESSION['plugin_metademands']['plugin_metademands_forms_id']);
   unset($_SESSION['plugin_metademands']['plugin_metademands_forms_name']);
}
$return = "";
if (count($forms) > 0) {
   foreach ($forms as $form) {
      $return .= "<tr class='tab_bg_1'>";
      $return .= "<td>" . Toolbox::stripslashes_deep($form['name']) . "</td>";
      $return .= "<td>" . Html::convDateTime($form['date']) . "</td>";
      $return .= "<td>";
      $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm(" . $form['id'] . ")\">";
      $return .= "<i class='fas fa-1x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load form', 'metademands') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
      $return .= "</button>";
      $return .= "</td>";
      $return .= "<td>";
      $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteForm(" . $form['id'] . ")\">";
      $return .= "<i class='fas fa-1x fa-trash pointer' title='" . _sx('button', 'Delete form', 'metademands') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
      $return .= "</button>";
      $return .= "</tr>";
   }
} else {
   $return .= "<tr class='tab_bg_1'><td colspan='4' class='center'>" . __("No existing forms founded", 'metademands') . "</td></tr>";
}

echo $return;


