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
 * Class PluginMetademandsStepform
 */
class PluginMetademandsStepform extends CommonDBTM {

   static $rightname = 'plugin_metademands';


   /**
    * @param $users_id
    * @param $plugin_metademands_metademands_id
    *
    * @return string
    */
   static function showFormsForUserMetademand($users_id, $plugin_metademands_metademands_id, $is_model = false) {

      $self      = new self();
      $condition = ['users_id'                          => $users_id,
                    'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id];
      if ($is_model == true) {
         $condition['is_model'] = 1;
      } else {
         $condition['is_model'] = 0;
      }
      $forms = $self->find($condition, ['date DESC']);

      if (isset($_SESSION['plugin_metademands']['plugin_metademands_forms_name'])) {
         $formname = Html::cleanInputText(Toolbox::stripslashes_deep($_SESSION['plugin_metademands']['plugin_metademands_forms_name'])) ?? '';
      } else {
         $formname = '';
      }
      if (isset($_SESSION['plugin_metademands']['plugin_metademands_forms_id'])) {
         $form_id = $_SESSION['plugin_metademands']['plugin_metademands_forms_id'];
      } else {
         $form_id = 0;
      }
      $return = "<span class=''>";
      $rand   = mt_rand();
      if ($is_model == true) {

         if (isset($_SESSION['plugin_metademands']['plugin_metademands_forms_name'])) {

            $return .= "<div class='card-header'>";
            $return .= __("Current form", 'metademands');
            $return .= "</div>";
            $return .= "<table class='tab_cadre_fixe'>";
            $return .= "<tr class=''>";
            $return .= "<td colspan='4' class='center'>";
            $return .= Html::hidden('plugin_metademands_forms_id', ['value' => $form_id, 'id' => 'plugin_metademands_forms_id']);
            $title  = "<i class='fas fa-1x fa-save pointer'></i>&nbsp;";
            $return .= Html::input('form_name', ['value'       => $formname,
                                                 'maxlength'   => 250,
                                                 'size'        => 20,
                                                 'class'       => ' ',
                                                 'placeholder' => __('Form name', 'metademands')]);
            $self->getFromDB($form_id);
            if ($self->fields['is_model'] == true) {
               $title .= _sx('button', 'Save model', 'metademands');
            } else {
               $title .= _sx('button', 'Save as model', 'metademands');
            }

            $return .= "&nbsp;";
            $return .= Html::submit($title, ['name'  => 'save_model',
                                             'form'  => '',
                                             'id'    => 'FormSave' . $rand,
                                             'class' => 'btn btn-success btn-sm']);
            $return .= "&nbsp;";
            $title  = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
            $title  .= _sx('button', 'Clean form', 'metademands');
            $return .= Html::submit($title, ['name'  => 'clean_form',
                                             'class' => 'btn btn-warning btn-sm']);
            $return .= "<br>";
            $return .= "</td></tr>";
            $return .= "</table>";
         } else {
            $return .= "<div class='card-header'>";
            $return .= __("New model", 'metademands');
            $return .= "</div>";
            $return .= "<table class='tab_cadre_fixe'>";
            $return .= "<tr class=''>";
            $return .= "<td colspan='4' class='center'>";
            $return .= "<br>";
            $return .= Html::input('form_name', ['maxlength'   => 250,
                                                 'size'        => 40,
                                                 'placeholder' => __('Form name', 'metademands')]);
            $return .= "<br>";
            $title  = "<i class='fas fa-1x fa-cloud-upload-alt pointer'></i>&nbsp;";
            $title  .= _sx('button', 'Save as model', 'metademands');

            $return .= Html::submit($title, ['name'  => 'save_form',
                                             'form'  => '',
                                             'id'    => 'FormAdd' . $rand,
                                             'class' => 'btn btn-success btn-sm']);
            $return .= "&nbsp;";
            $title  = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
            $title  .= _sx('button', 'Clean form', 'metademands');
            $return .= Html::submit($title, ['name'  => 'clean_form',
                                             'class' => 'btn btn-warning btn-sm']);
            $return .= "<br>";
            $return .= "</td></tr>";
         }
      }

      $return .= "<table class='tab_cadre_fixe'>";
      //      $return .= "<tr class='tab_bg_1'><th colspan='4' class='center'>";
      //      $return .= "<div class='card-header'>";
      //      if ($is_model == true) {
      //         $return .= __("Your models", 'metademands');
      //      } else {
      //         $return .= __("Your created forms", 'metademands');
      //      }
      //
      //      $return .= "</div>";
      $return .= "<p class='card-text'>";
      //      $return .= "</th></tr>";
      $return .= "<tbody id='bodyForm'>";
      if (count($forms) > 0) {
         foreach ($forms as $form) {
            $return .= "<tr class=''>";
            $return .= "<td>" . Toolbox::stripslashes_deep($form['name']) . "</td>";
            $return .= "<td>" . Html::convDateTime($form['date']) . "</td>";

            //            $return .= "<td><i class='".($form['is_model'] > 0 ? 'fas' : 'far')." fa-star fa-xs mark-default me-1'
            //            title='".($form['is_model'] > 0 ? __('Used as model', 'metademands') : __('Mark as model', 'metademands'))."'
            //            data-bs-toggle='tooltip' data-bs-placement='right' role='button'></i>";
            //            $return .= "</td>";
            $return .= "<td>";
            $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm(" . $form['id'] . ")\">";
            $return .= "<i class='fas fa-1x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load form', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
            $return .= "</button>";
            $return .= "</td>";
            if ($is_model == true) {
               $return .= "<td>";
               $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteForm(" . $form['id'] . ")\">";
               $return .= "<i class='fas fa-1x fa-trash pointer' title='" . _sx('button', 'Delete form', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
               $return .= "</button>";
               $return .= "</td>";
            }
            $return .= "</tr>";
         }
      } else {
         $return .= "<tr class=''>";
         $return .= "<td>";
         $return .= __("No existing forms founded", 'metademands');
         $return .= "</td>";
         $return .= "</tr>";
      }
      $return .= "</tbody>";
      $return .= "</table>";
      $return .= "</p>";
      if ($is_model == true) {

         $return .= "<script>
                       var meta_id = {$plugin_metademands_metademands_id};
                       
                      function deleteForm(form_id) {
                          var self_delete = false;
                          if($form_id == form_id ){
                              self_delete = true;
                          }
                          $('#ajax_loader').show();
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/deleteform.php',
                                type: 'POST',
                                data:
                                  {
                                    users_id:$users_id,
                                    plugin_metademands_metademands_id: meta_id,
                                    forms_id: form_id,
                                    self_delete: self_delete
                                  },
                                success: function(response){
                                    $('#bodyForm').html(response);
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
      }
      $step   = PluginMetademandsMetademand::STEP_SHOW;
      $return .= "<script>
                      var meta_id = {$plugin_metademands_metademands_id};
                      var step = {$step};
                      function loadForm(form_id) {
                         $('#ajax_loader').show();
                         var data_send = $('form').serializeArray();
                         data_send.push({name: 'plugin_metademands_forms_id', value: form_id});
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadform.php',
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
      if ($is_model == true) {
         $return .= "<script>
                          $('#FormAdd$rand').click(function() {

                             if(typeof tinyMCE !== 'undefined'){
                                tinyMCE.triggerSave();
                             }
                             jQuery('.resume_builder_input').trigger('change');
                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                             $('#ajax_loader').show();
                             arrayDatas = $('form').serializeArray();
                             arrayDatas.push({name: \"save_form\", value: true});
                             arrayDatas.push({name: \"is_model\", value: 1});
                             $.ajax({
                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
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
      }
      $return .= "<script>
                          $('#FormSave$rand').click(function() {

                             if(typeof tinyMCE !== 'undefined'){
                                tinyMCE.triggerSave();
                             }
                             jQuery('.resume_builder_input').trigger('change');
                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                             $('#ajax_loader').show();
                             arrayDatas = $('form').serializeArray();
                             arrayDatas.push({name: \"save_model\", value: true});
                             arrayDatas.push({name: \"is_model\", value: 1});
                             $.ajax({
                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/updateform.php',
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

   /**
    * Display tab for each itel object
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (($item->getType() == 'Ticket' && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk')
          || $item->getType() == 'Problem'
          || $item->getType() == 'Change') {
         if ($this->canView()
             && !$withtemplate
             && countElementsInTable("glpi_plugin_metademands_forms", ["itemtype" => $item->getType(),
                                                                       "items_id" => $item->fields['id']])) {

            $form_metademand_data = $this->find(['itemtype' => $item->getType(), 'items_id' => $item->fields['id']]);
            $total                = count($form_metademand_data);
            $name                 = _n('Initial form', 'Initial forms', $total, 'metademands');

            return self::createTabEntry($name,
                                        $total);
         }

      } else if ($item->getType() == 'User') {
         if ($this->canView()
             && !$withtemplate
             && countElementsInTable("glpi_plugin_metademands_forms", ["users_id" => $item->fields['id']])) {
            $form_metademand_data = $this->find(['users_id' => $item->fields['id']]);
            $total                = count($form_metademand_data);
            $name                 = _n('Associated form', 'Associated forms', $total, 'metademands');

            return self::createTabEntry($name,
                                        $total);

         }
      }
      return '';
   }

   /**
    * Display content for each users
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    * @throws \GlpitestSQLError
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $form = new self();

      switch ($item->getType()) {
         case 'Ticket':
         case 'Problem':
         case 'Change':
            $form->showFormsForItilObject($item);
            break;
         case 'User':
            $form->showFormsForUser($item);
            break;
      }

      return true;
   }

   /**
    * @param $ticket
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   function showFormsForItilObject($item) {

      if (!$this->canView()) {
         return false;
      }
      $form_metademand_data = $this->find(['itemtype' => $item->getType(),
                                           'items_id' => $item->fields['id'],
                                           'is_model' => 0], ['date DESC']);

      if (count($form_metademand_data)) {
         $name = _n('Initial form', 'Initial forms', count($form_metademand_data), 'metademands');
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='center'>";
         echo "<th colspan='4'>" . $name . "</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th>" . __('Name') . "</th>";
         echo "<th>" . __('Creation date') . "</th>";
         echo "<th>" . __('By') . "</th>";
         echo "<th>" . __('See form', 'metademands') . "</th>";
         echo "</tr>";

         foreach ($form_metademand_data as $form_metademand_fields) {

            $plugin_metademands_metademands_id = $form_metademand_fields['plugin_metademands_metademands_id'];
            $users_id                          = $form_metademand_fields['users_id'];
            $items_id                          = $item->fields['id'];
            $itemtype                          = $item->getType();
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            $meta = new PluginMetademandsMetademand();
            $meta->getFromDB($plugin_metademands_metademands_id);
            echo $meta->getName();
            echo "</td>";

            echo "<td>";
            echo Html::convDateTime($form_metademand_fields['date']);
            echo "</td>";

            echo "<td>";
            echo User::getFriendlyNameById($form_metademand_fields['users_id']);
            echo "</td>";

            echo "<td>";
            $rand = mt_rand();
            echo "<button form='' class='submit btn btn-info btn-sm' onclick=\"loadForm$rand(" . $form_metademand_fields['id'] . ", " . $form_metademand_fields['plugin_metademands_metademands_id'] . ")\">";
            echo "<i class='fas fa-2x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load form', 'metademands') . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
            echo "</button>";
            $step = PluginMetademandsMetademand::STEP_SHOW;
            $is_validate = 1;
            $metaValidation = new PluginMetademandsMetademandValidation();
            if ($metaValidation->getFromDBByCrit(['tickets_id' => $items_id])) {
               $is_validate = $metaValidation->fields['validate'];
            }
            echo "<script>
                      var step = {$step};
                      function loadForm$rand(form_id, meta_id) {
                         $('#ajax_loader').show();
                         var data_send = {plugin_metademands_forms_id: form_id,
                                         metademands_id: meta_id,
                                         _users_id_requester: $users_id,
                                         items_id: $items_id,
                                         itemtype: '$itemtype'};
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadform.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    if (response == 0) {
                                       $('#ajax_loader').hide();
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?current_ticket_id=$items_id&meta_validated=$is_validate&see_form=1&metademands_id=' + meta_id + '&step=' + step;
                                    }
                                }
                             });
                       };
                     </script>";
         }
         echo "</td>";
         echo "</tr>";
         echo "</table>";
      } else {
         //         echo "<div class='alert alert-important alert-info center'>" . __('No item found') . "</div>";
      }
   }

   /**
    * @param $ticket
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   function showFormsForUser($user) {

      if (!$this->canView()) {
         return false;
      }
      $forms_metademands = $this->find(['users_id' => $user->fields['id'],
                                        'is_model' => 0], ['date DESC']);

      if (count($forms_metademands)) {
         $name = _n('Associated form', 'Associated forms', count($forms_metademands), 'metademands');
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='center'>";
         echo "<th colspan='3'>" . $name . "</th>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<th>" . __('Name') . "</th>";
         echo "<th>" . __('Creation date') . "</th>";
         echo "<th>" . __('See form', 'metademands') . "</th>";
         echo "</tr>";
         foreach ($forms_metademands as $forms_metademand) {

            $plugin_metademands_metademands_id = $forms_metademand['plugin_metademands_metademands_id'];
            $users_id                          = $user->fields['id'];
            $items_id                          = $forms_metademand['items_id'];
            $itemtype                          = $forms_metademand['itemtype'];
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            $meta = new PluginMetademandsMetademand();
            $meta->getFromDB($plugin_metademands_metademands_id);
            echo $meta->getName();
            echo "</td>";

            echo "<td>";
            echo Html::convDateTime($forms_metademand['date']);
            echo "</td>";

            echo "<td>";
            $rand = mt_rand();
            echo "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm$rand(" . $forms_metademand['id'] . ", " . $forms_metademand['plugin_metademands_metademands_id'] . ")\">";
            echo "<i class='fas fa-2x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load form', 'metademands') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
            echo "</button>";

         }
         echo "</td>";
         echo "</tr>";
         echo "</table>";
      } else {
         echo "<div class='alert alert-important alert-info center'>" . __('No item found') . "</div>";
      }
   }

   function post_addItem()
   {
       $options = [
           'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
       ];
       NotificationEvent::raiseEvent("add_step_form", $this, $options);

   }

   function post_updateItem($history = 1)
   {
       $options = [
           'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
       ];
       NotificationEvent::raiseEvent("update_step_form", $this, $options);
       parent::post_updateItem($history); // TODO: Change the autogenerated stub
   }


    function showWaitingForm() {

       echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.php");
       $rand = mt_rand();
       $group_user = new Group_User();
       $groups_users = $group_user->find(['users_id' => Session::getLoginUserID()]);
       $groups = [];
       foreach ($groups_users as $gu) {
           $groups[] = $gu['groups_id'];
       }
       $list_blocs = [];
       $step = new PluginMetademandsStep();
       $steps = $step->find(['groups_id' => $groups]);
       $stepform = new PluginMetademandsStepform();
       $stepforms = [];
       foreach ($steps as $s) {
           if($forms = $stepform->find(['plugin_metademands_metademands_id' => $s['plugin_metademands_metademands_id'], 'bloc_id' => $s['bloc_id']])) {
               foreach ($forms as $id => $form) {
                   $stepforms[$id] = $form;
               }

//               $metas_id[$s['plugin_metademands_metademands_id']] = $s['plugin_metademands_metademands_id'];
           }

       }
//       $stepforms = $stepform->find(['plugin_metademands_metademands_id' => $metas_id ]);
       //'plugin_metademands_metademands_id' => $ID
       echo "<div class=\"row\">";
       echo "<div class=\"col-md-12\">";
       echo "<h4><div class='alert alert-dark' role='alert'>";
       $icon = "fa-share-alt";
       if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
           $icon = $meta->fields['icon'];
       }
       echo "<i class='fa-2x fas $icon'></i>&nbsp;";
       echo __('Your form to complete', 'metademands');
       echo "</div></h4></div></div>";
       echo "<div id='listmeta' >";
        if(!empty($stepforms)) {
            foreach ($stepforms as $id => $name) {

                $meta = new PluginMetademandsMetademand();
                if ($meta->getFromDB($name['plugin_metademands_metademands_id'])) {
                    $metaID = $name['plugin_metademands_metademands_id'];
//               echo "<a class='bt-buttons' href='" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $meta->getID() . "&step=2'>";
                    echo '<div class="btnsc-normal" onclick="loadForm'.$rand.'(\''.$id.'\',\''.$metaID.'\')" >';
                    $fasize = "fa-4x";
                    echo "<div class='center'>";
                    $icon = "fa-share-alt";
                    if (!empty($meta->fields['icon'])) {
                        $icon = $meta->fields['icon'];
                    }
                    echo "<i class='bt-interface fa-menu-md fas $icon $fasize' style=\"font-family:'Font Awesome 5 Free', 'Font Awesome 5 Brands';\"></i>";//$style
                    echo "</div>";

                    echo "<br><p>";
                    if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
                        echo $meta->getName();
                    } else {
                        echo $n;
                    }

//                    if (empty($comm = PluginMetademandsMetademand::displayField($meta->getID(), 'comment')) && !empty($meta->fields['comment'])) {
                        echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                        echo __('Edit by ','metademands');
                        echo User::getFriendlyNameById($name['users_id']);
                        echo "</span></em>";

                        echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";

                        echo Html::convDateTime($name['date']);
                        echo "</span></em>";
                        echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                        echo __('Step','metademands');
                        echo $name['bloc_id'];
                        echo "</span></em>";
//                    }

//               if ($config['use_draft']) {
//                   $count_drafts = PluginMetademandsDraft::countDraftsForUserMetademand(Session::getLoginUserID(), $id);
//                   if ($count_drafts > 0) {
//                       echo "<br><em><span class='mydraft-comment'>";
//                       echo sprintf(_n('You have %d draft', 'You have %d drafts', $count_drafts, 'metademands'),
//                           $count_drafts);
//                       echo "</span>";
//                   }
//               }

                    echo "</p></div>";
//               echo "</a>";
                }
            }
        } else {
//            echo "<div class='center first-bloc'>";
//            echo "<div class=\"row\">";
//            echo "<div class=\"bt-feature col-md-12 \">";
//            echo __('No item to display');
//            echo "</div></div>";
//            echo "<div class=\"row\">";
//            echo "<div class=\"bt-feature col-md-12 \">";
////            echo Html::submit(__('Previous'), ['name' => 'previous', 'class' => 'btn btn-primary']);
////            echo Html::hidden('previous_metademands_id', ['value' => $metademands_id]);
//            echo "</td>";
//            echo "</tr>";
//            echo "</div></div>";
            echo "<div class='alert alert-important alert-info center'>" . __('No item found') . "</div>";
        }

       echo "</div>";

       $users_id = Session::getLoginUserID();
       $step = 2;
       echo "<script>
                      var step = {$step};
                      function loadForm$rand(form_id, meta_id, bloc_id) {
                         $('#ajax_loader').show();
                        
                         var data_send = {plugin_metademands_stepforms_id: form_id,
                                         metademands_id: meta_id,
                                         bloc_id: bloc_id,
                                         _users_id_requester: $users_id,
                                      };
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadstepform.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    if (response == 0) {
                                       $('#ajax_loader').hide();
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=' + step;
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
   }
}
