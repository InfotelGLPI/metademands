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


   static function showDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id) {
      global $CFG_GLPI;
      $self   = new self();
      $drafts = $self->find(['users_id'                          => $users_id,
                             'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);
      $return = "<span class='draft'>";

      $return .= Html::scriptBlock(
         "$('[name=\"wizard_form\"]').submit(function() {
                            $('[name=\"from\"]').html('');
                            var val = $(\"input[type=submit][clicked=true]\").attr('draft_id');
                            console.log(val);
                            if(val){
                              $('#plugin_metademands_drafts_id').val(val);
                            }
                            
                            
                        });
                      $(\"form input[type=submit]\").click(function() {
                          $(\"input[type=submit]\", $(this).parents(\"form\")).removeAttr(\"clicked\");
                          $(this).attr(\"clicked\", \"true\");
                      });
                        "

      );
      if (isset($_SESSION['plugin_metademands']['plugin_metademands_drafts_name'])) {
         $draftname = Html::cleanInputText(Toolbox::stripslashes_deep($_SESSION['plugin_metademands']['plugin_metademands_drafts_name'])) ?? '';
      } else {
         $draftname = '';
      }

      $return .= "<table class='tab_cadre_fixe'>";
      if (isset($_SESSION['plugin_metademands']['plugin_metademands_drafts_name'])) {
         $return .= "<tr class='tab_bg_1'>";
         $return .= "<td colspan='4' class='center'>";
         $title = _sx('button', 'Save draft', 'metademands') ."&nbsp;(".$_SESSION['plugin_metademands']['plugin_metademands_drafts_name'].")";
         $return .= "<button name='save_draft' type='submit' class='btn btn-success'><i class='fas fa-1x fa-save pointer' 
                    title='$title' 
                           data-hasqtip='0' aria-hidden='true' ></i></button>";
//         $return .= __("Save draft", 'metademands');
         $return .= "&nbsp;<button name='clean_form' type='submit' class='btn btn-warning'><i class='fas fa-1x fa-broom pointer' title='" . _sx('button', 'Clean form', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true' ></i></button><br>";
         $return .= "</td></tr>";
      } else {
         $return .= "<tr class='tab_bg_1'>";
         $return .= "<td colspan='4' class='center'>";
         $return .= "<input type='text' maxlength='250' 
         placeholder='" . __('Draft name', 'metademands') . "' name='draft_name' value=\"$draftname\"><br><br>";
         $return .= "<button name='save_draft' type='submit' class='btn btn-success'><i class='fas fa-1x fa-cloud-upload-alt pointer' title='" . _sx('button', 'Save as draft', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true' ></i></button>";

         $return .= "&nbsp;<button name='clean_form' type='submit' class='btn btn-warning'><i class='fas fa-1x fa-broom pointer' title='" . _sx('button', 'Clean form', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true' ></i></button><br>";
         $return .= "</td></tr>";
      }
      $return .= "</table>";

      $return .= "<table class='tab_cadre_fixe'>";
      $return .= "<tr class='tab_bg_1'><th colspan='4' class='center'>";
      $return .= __("Your drafts", 'metademands');
      $return .= "</th></tr>";
      $return .= "<tbody id='bodyDraft'>";
      if (count($drafts) > 0) {
         foreach ($drafts as $draft) {
            $return .= "<tr class='tab_bg_1'>";
            $return .= "<td>" . Toolbox::stripslashes_deep($draft['name']) . "</td>";
            $return .= "<td>" . Html::convDateTime($draft['date']) . "</td>";
            $return .= "</div>";
            $return .= "<td>";
            $return .= "<button form='' class='btn btn-success' onclick=\"loadDraft(" . $draft['id'] . ")\">";
            $return .= "<i class='fas fa-1x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load draft', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
            $return .= "</button>";
            $return .= "</td>";
            $return .= "<td>";
            $return .= "<button form='' class='btn btn-danger' onclick=\"deleteDraft(" . $draft['id'] . ")\">";
            $return .= "<i class='fas fa-1x fa-trash pointer' title='" . _sx('button', 'Delete draft', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
            $return .= "</button>";
            $return .= "</td>";
            $return .= "</tr>";
         }
      } else {
         $return .= "<tr class='tab_bg_1'><td colspan='4' class='center'>" . __('No draft available for this metademand', 'metademands') . "</td></tr>";
      }
      $return .= "</tbody>";
      $return .= "</table>";
      if (isset($_SESSION['plugin_metademands']['plugin_metademands_drafts_id'])) {
         $draft_id = $_SESSION['plugin_metademands']['plugin_metademands_drafts_id'];
      } else {
         $draft_id = 0;
      }
      $return .= "<input type=\"hidden\" name=\"plugin_metademands_drafts_id\" id='plugin_metademands_drafts_id' value=\"$draft_id\" />";

      $return .= "<script>
                       var meta_id = {$plugin_metademands_metademands_id};
                      function deleteDraft(draft_id) {
                          $.ajax({
                             url: '" . $CFG_GLPI['root_doc'] . "/plugins/metademands/ajax/deletedraft.php',
                                type: 'POST',
                                data:
                                  {
                                    users_id:$users_id,
                                    plugin_metademands_metademands_id: meta_id,
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
      $step   = PluginMetademandsMetademand::STEP_SHOW;
      $return .= "<script>
                      var meta_id = {$plugin_metademands_metademands_id};
                      var step = {$step};
                      function loadDraft(draft_id) {
                         var data_send = $('form').serializeArray();
                         data_send.push({name: 'plugin_metademands_drafts_id', value: draft_id});
                          $.ajax({
                             url: '" . $CFG_GLPI['root_doc'] . "/plugins/metademands/ajax/loaddraft.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    if (response == 1) {
                                       document.location.reload();
                                    } else {
                                       window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/metademands/front/wizard.form.php?metademands_id=' + meta_id + '&step=' + step;
                                    }
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
                               'plugin_metademands_drafts_id' => $draft_id,
                               'plugin_metademands_fields_id' => $fields_id]);
         }
      }
   }

   /**
    * @param $plugin_metademands_drafts_id
    * @param $users_id
    */
   static function loadDraftValues($plugin_metademands_drafts_id) {
      $draft_value   = new PluginMetademandsDraft_Value();
      $drafts_values = $draft_value->find(['plugin_metademands_drafts_id' => $plugin_metademands_drafts_id]);
      foreach ($drafts_values as $values) {
         $_SESSION['plugin_metademands']['fields'][$values['plugin_metademands_fields_id']] = Toolbox::addslashes_deep(json_decode($values['value'])) ?? Toolbox::addslashes_deep($values['value']);
      }
   }
}
