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

/**
 * Class PluginMetademandsDraft
 */
class PluginMetademandsDraft extends CommonDBTM {

   static $rightname = 'plugin_metademands';


   static function showDraftsForUserMetademand($users_id,$plugin_metademands_metademands_id){
      $self   = new self();
      $drafts = $self->find(['users_id' => $users_id, 'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);
      $return = "<table class='tab_cadre_fixe'>";
      $return .= "<tr class='tab_bg_1'><th colspan='4' class='center'>".sprintf(__("My drafts for %1s",'metademands'),Dropdown::getDropdownName('glpi_plugin_metademands_metademands',$plugin_metademands_metademands_id))."</th></tr>";
      $return .= "<tr class='tab_bg_1'>";
      $return .= "<th>".__("Name")."</th>";
      $return .= "<th>".__("Date")."</th>";
      $return .= "<th>"."</th>";
      $return .= "<th>"."</th>";
      $return .= "</tr>";
      $return .= "<tbody id='bodyDraft'>";
      if(count($drafts)>0){
         foreach ($drafts as $draft){
            $return .= "<tr class='tab_bg_1'>";
            $return .= "<td>".$draft['name']."</td>";
            $return .= "<td>".Html::convDateTime(draft['date'])."</td>";
            $return .=  "<td><input type='button' id='loadDraft' onclick=\"loadDraft(".$draft['id'].")\"  class='submit' name='next_button' value='" . _sx('button', 'Load this draft','metademands') . "'></td>";
            $return .=  "<td><input type='button' id='deleteDraft' onclick=\"deleteDraft(".$draft['id'].")\"  class='submit' name='next_button' value='" . _sx('button', 'Delete this draft','metademands') . "'></td>";
            $return .= "</tr>";
         }
      }else{
         $return .= "<tr class='tab_bg_1'><td colspan='4' class='center'>".__('No draft available for this Meta-Demand','metademands')."</td></tr>";
      }
      $return .= "</tbody>";
      $return .= "</table>";

      return $return;

   }



}