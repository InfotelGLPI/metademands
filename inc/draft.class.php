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

/**
 * Class PluginMetademandsDraft
 */
class PluginMetademandsDraft extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   /**
    * @param $users_id
    * @param $plugin_metademands_metademands_id
    *
    * @return int|void
    */
   static function countDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id) {

      $self   = new self();
      $drafts = $self->find(['users_id'                          => $users_id,
                             'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);

      return count($drafts);
   }

    public function cleanDBonPurge()
    {
        $temp = new PluginMetademandsDraft_Value();
        $temp->deleteByCriteria(['plugin_metademands_drafts_id' => $this->fields['id']]);
    }

    function rawSearchOptions() {

        $tab = [];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number'
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'date',
            'name' => __('Date'),
            'datatype' => 'datetime',
        ];

        return $tab;

    }


   /**
    * @param $users_id
    * @param $plugin_metademands_metademands_id
    *
    * @return string
    */
   static function showDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id) {

      $self   = new self();
      $drafts = $self->find(['users_id'                          => $users_id,
                             'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);
      $return = "<span class=''>";//draft

      $return .= Html::scriptBlock(
         "$('[name=\"wizard_form\"]').submit(function() {
                            $('#ajax_loader').show();
                            $('[name=\"from\"]').html('');
                            var val = $(\"input[type=submit][clicked=true]\").attr('draft_id');
//                            console.log(val);
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
      if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name'])) {
         $draftname = Html::cleanInputText(Toolbox::stripslashes_deep($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name'])) ?? '';
      } else {
         $draftname = '';
      }


      if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name'])) {

         $return .= "<div class='card-header'>";
         $return .= __("Current draft", 'metademands');
         $return .= "</div>";
         $return .= "<table class='tab_cadre_fixe'>";
         $return .= "<tr class=''>";
         $return .= "<td colspan='4' class='center'>";
         $title  = "<i class='fas fa-1x fa-save pointer'></i>&nbsp;";
         $title  .= _sx('button', 'Save draft', 'metademands') . "&nbsp;(" . $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name'] . ")";
         $return .= Html::submit($title, ['name'  => 'save_draft',
                                          'form'  => '',
                                          'id'    => 'submitSave',
                                          'class' => 'btn btn-success btn-sm']);
         $return .= "&nbsp;";
         $title  = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
         $title  .= _sx('button', 'Clean form', 'metademands');
         $return .= Html::submit($title, ['name'  => 'clean_form',
                                          'class' => 'btn btn-warning btn-sm']);
         $return .= "<br>";
         $return .= "</td></tr>";
      } else {
         $return .= "<div class='card-header'>";
         $return .= __("New draft", 'metademands');
         $return .= " <span class='red'>*</span></div>";
         $return .= "<table class='tab_cadre_fixe'>";
         $return .= "<tr class=''>";
         $return .= "<td colspan='4' class='center'>";
         $return .= "<br>";
         $return .= Html::input('draft_name', ['value'       => $draftname,
                                               'maxlength'   => 250,
                                               'size'        => 40,
                                               'placeholder' => __('Draft name', 'metademands')]);
         $return .= "<br>";
         $title  = "<i class='fas fa-1x fa-cloud-upload-alt pointer'></i>&nbsp;";
         $title  .= _sx('button', 'Save as draft', 'metademands');
         $return .= Html::submit($title, ['name'  => 'save_draft',
                                          'form'  => '',
                                          'id'    => 'submitSave',
                                          'class' => 'btn btn-success btn-sm']);
         $return .= "&nbsp;";
         $title  = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
         $title  .= _sx('button', 'Clean form', 'metademands');
         $return .= Html::submit($title, ['name'  => 'clean_form',
                                          'class' => 'btn btn-warning btn-sm']);
         $return .= "<br>";
         $return .= "</td></tr>";
      }
      $return .= "</table>";

      $return .= "<table class='tab_cadre_fixe'>";
      //      $return .= "<tr class='tab_bg_1'><th colspan='4' class='center'>";
      $return .= "<div class='card-header'>";
      $return .= __("Your drafts", 'metademands');
      $return .= "</div>";
      $return .= "<p class='card-text'>";
      //      $return .= "</th></tr>";
      $return .= "<tbody id='bodyDraft'>";
      if (count($drafts) > 0) {
         foreach ($drafts as $draft) {
            $return .= "<tr class=''>";
            $return .= "<td>" . Toolbox::stripslashes_deep($draft['name']) . "</td>";
            $return .= "<td>" . Html::convDateTime($draft['date']) . "</td>";
            $return .= "</div>";
            $return .= "<td>";
            $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadDraft(" . $draft['id'] . ")\">";
            $return .= "<i class='fas fa-1x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load draft', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
            $return .= "</button>";
            $return .= "</td>";
            $return .= "<td>";
            $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteDraft(" . $draft['id'] . ")\">";
            $return .= "<i class='fas fa-1x fa-trash pointer' title='" . _sx('button', 'Delete draft', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
            $return .= "</button>";
            $return .= "</td>";
            $return .= "</tr>";
         }
      } else {
         $return .= "<tr class=''><td colspan='4' class='center'>" . __('No draft available for this form', 'metademands') . "</td></tr>";
      }
      $return .= "</tbody>";
      $return .= "</table>";
      $return .= "</p>";

      if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'])) {
         $draft_id = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'];
      } else {
         $draft_id = 0;
      }
      $return .= Html::hidden('plugin_metademands_drafts_id', ['value' => $draft_id, 'id' => 'plugin_metademands_drafts_id']);

      $return .= "<script>
                       var meta_id = {$plugin_metademands_metademands_id};
                       
                      function deleteDraft(draft_id) {
                          var self_delete = false;
                          if($draft_id == draft_id ){
                              self_delete = true;
                          }
                          $('#ajax_loader').show();
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/deletedraft.php',
                                type: 'POST',
                                data:
                                  {
                                    users_id:$users_id,
                                    plugin_metademands_metademands_id: meta_id,
                                    drafts_id: draft_id,
                                    self_delete: self_delete
                                  },
                                success: function(response){
                                    $('#bodyDraft').html(response);
                                    $('#ajax_loader').hide();
                                    if(self_delete){
                                        document.location.reload();
                                    }
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
                         $('#ajax_loader').show();
                         var data_send = $('#wizard_form').serializeArray();
                         data_send.push({name: 'plugin_metademands_drafts_id', value: draft_id},{name: 'metademands_id', value: meta_id});
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loaddraft.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    $('#ajax_loader').hide();
                                    if (response == 1) {
                                       document.location.reload();
                                    } else {
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=' + step;
                                    }
                                 }
                             });
                       };
                     </script>";
      $return .= "<script>
                          $('#submitSave').click(function() {
                           
                             if(typeof tinyMCE !== 'undefined'){
                                tinyMCE.triggerSave();
                             }
                             jQuery('.resume_builder_input').trigger('change');
                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                             $('#ajax_loader').show();
                             arrayDatas = $('#wizard_form').serializeArray();
                             arrayDatas.push({name: \"save_draft\", value: true});
                             $.ajax({
                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/adddraft.php',
                                   type: 'POST',
                                   data: arrayDatas,
                                   success: function(response){
                                       $('#ajax_loader').hide();
                                       document.location.reload();
                                    },
                                   error: function(xhr, status, error) {
                                      console.log(xhr);
                                      console.log(status);
                                      console.log(error);
                                    } 
                                });
                          });
                        </script>";
      $return .= "</span>";

      return $return;

   }
}
