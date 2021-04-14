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
      global $CFG_GLPI;
      $self   = new self();
      $drafts = $self->find(['users_id' => $users_id, 'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);
      $return  = "<span class='draft'>";
      $return .=  "<button type='button' class='speechcloseButton' onclick='$(this).parent().parent().hide();'>x</button>";
      $return .= "<table class='tab_cadre_fixe'>";
      $return .= "<tr class='tab_bg_1'><th colspan='4' class='center'>".
                 sprintf(__("My drafts for %1s",'metademands'),Dropdown::getDropdownName('glpi_plugin_metademands_metademands',$plugin_metademands_metademands_id))
                 ."</th></tr>";
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
      $return .= "</tbody>";
      $return .= "</table>";
      if(isset( $_SESSION['plugin_metademands']['plugin_metademands_drafts_id'])){
         $draft_id =  $_SESSION['plugin_metademands']['plugin_metademands_drafts_id'];
      } else {
         $draft_id = 0;
      }
      $return .= "<input type=\"hidden\" name=\"plugin_metademands_drafts_id\" id='plugin_metademands_drafts_id' value=\"$draft_id\" />";
      $return .= "<script>
                      function deleteDraft(draft_id) {
                          $.ajax({
                             url: '" . $CFG_GLPI['root_doc'] . "/plugins/metademands/ajax/deletedraft.php',
                                type: 'POST',
                                data:
                                  {
                                    users_id:$users_id,
                                    plugin_metademands_metademands_id: $plugin_metademands_metademands_id,
                                    drafts_id: draft_id
                                  },
                                success: function(response){
                                    $('#bodyDraft').html(response);
                                                                     
                                 },
                                error: function(xhr, status, error) {
                                   console.log(xhr);
                                   console.log(status);
                                   console.log(error);
                                 } 
                             });
                       };
                   
                      
                     </script>";
      $return .= "</span>";

      return $return;

   }

   /**
    * @param $parent_fields
    * @param $values
    * @param $tickets_id
    */
   static function setDraftValues($parent_fields, $values, $draft_id) {

      if (count($parent_fields)) {
         foreach ($parent_fields as $fields_id => $field) {
            $field['value'] = '';
            if (isset($values[$fields_id]) && !is_array($values[$fields_id])) {
               $field['value'] = $values[$fields_id];
            } else if (isset($values[$fields_id]) && is_array($values[$fields_id])) {
               $field['value'] = json_encode($values[$fields_id]);
            }
            $draft_value = new PluginMetademandsDraft_Value();
            //TODO CHANGE
            $draft_value->add(['value'                        => $field['value'],
                        'plugin_metademands_drafts_id'                   => $draft_id,
                        'plugin_metademands_fields_id' => $fields_id]);
         }
      }
   }

   /**
    * @param $plugin_metademands_drafts_id
    * @param $users_id
    */
   static function loadDraftValues($plugin_metademands_drafts_id){
      $draft_value = new PluginMetademandsDraft_Value();
      $drafts_values = $draft_value->find([ 'plugin_metademands_drafts_id' => $plugin_metademands_drafts_id]);
      foreach ($drafts_values as $values){
         $_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id']] = Toolbox::addslashes_deep(json_decode($values['value'])) ?? Toolbox::addslashes_deep($values['value']);

      }
   }



}