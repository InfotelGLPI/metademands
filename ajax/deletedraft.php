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

include ('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();


$users_id = $_POST['users_id'];
$plugin_metademands_metademands_id = $_POST['plugin_metademands_metademands_id'];
$draft_id = $_POST['drafts_id'];

$self   = new PluginMetademandsDraft();
$self->deleteByCriteria(['id'=>$draft_id]);
$drafts = $self->find(['users_id' => $users_id, 'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);
$return = "";
if(count($drafts)>0){
   foreach ($drafts as $draft){
      $return .= "<tr class='tab_bg_1'>";
      $return .= "<td>".Toolbox::stripslashes_deep($draft['name'])."</td>";
      $return .= "<td>".Html::convDateTime($draft['date'])."</td>";
      $return .=  "<td><input type='submit' draft_id='".$draft['id']."'  
                        class='submit' name='load_draft' value='" . _sx('button', 'Load','metademands') . "'></td>";
      $return .=  "<td><i class='fas fa-trash pointer'  data-hasqtip='0' aria-hidden='true' onclick=\"deleteDraft(".$draft['id'].")\"></i></td>";
      $return .= "</tr>";
   }
}else{
   $return .= "<tr class='tab_bg_1'><td colspan='4' class='center'>".__('No draft available for this Meta-Demand','metademands')."</td></tr>";
}

echo $return;


