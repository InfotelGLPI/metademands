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
class PluginMetademandsStepform extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Step a metademands form', 'Steps a metademands form', $nb, 'metademands');
    }

    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return string
     */
//    public static function showFormsForUserMetademand($users_id, $plugin_metademands_metademands_id, $is_model = false)
//    {
//        $self      = new self();
//        $condition = ['users_id'                          => $users_id,
//                      'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id];
//        if ($is_model == true) {
//            $condition['is_model'] = 1;
//        } else {
//            $condition['is_model'] = 0;
//        }
//        $forms = $self->find($condition, ['date DESC']);
//
//        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_name'])) {
//            $formname = Html::cleanInputText(Toolbox::stripslashes_deep($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_name'])) ?? '';
//        } else {
//            $formname = '';
//        }
//        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id'])) {
//            $form_id = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id'];
//        } else {
//            $form_id = 0;
//        }
//        $return = "<span class=''>";
//        $rand   = mt_rand();
//        if ($is_model == true) {
//            if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_name'])) {
//                $return .= "<div class='card-header'>";
//                $return .= __("Current form", 'metademands');
//                $return .= "</div>";
//                $return .= "<table class='tab_cadre_fixe'>";
//                $return .= "<tr class=''>";
//                $return .= "<td colspan='4' class='center'>";
//                $return .= Html::hidden('plugin_metademands_forms_id', ['value' => $form_id, 'id' => 'plugin_metademands_forms_id']);
//                $title  = "<i class='fas fa-1x fa-save pointer'></i>&nbsp;";
//                $return .= Html::input('form_name', ['value'       => $formname,
//                                                     'maxlength'   => 250,
//                                                     'size'        => 20,
//                                                     'class'       => ' ',
//                                                     'placeholder' => __('Form name', 'metademands')]);
//                $self->getFromDB($form_id);
//                if ($self->fields['is_model'] == true) {
//                    $title .= _sx('button', 'Save model', 'metademands');
//                } else {
//                    $title .= _sx('button', 'Save as model', 'metademands');
//                }
//
//                $return .= "&nbsp;";
//                $return .= Html::submit($title, ['name'  => 'save_model',
//                                                 'form'  => '',
//                                                 'id'    => 'FormSave' . $rand,
//                                                 'class' => 'btn btn-success btn-sm']);
//                $return .= "&nbsp;";
//                $title  = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
//                $title  .= _sx('button', 'Clean form', 'metademands');
//                $return .= Html::submit($title, ['name'  => 'clean_form',
//                                                 'class' => 'btn btn-warning btn-sm']);
//                $return .= "<br>";
//                $return .= "</td></tr>";
//                $return .= "</table>";
//            } else {
//                $return .= "<div class='card-header'>";
//                $return .= __("New model", 'metademands');
//                $return .= "</div>";
//                $return .= "<table class='tab_cadre_fixe'>";
//                $return .= "<tr class=''>";
//                $return .= "<td colspan='4' class='center'>";
//                $return .= "<br>";
//                $return .= Html::input('form_name', ['maxlength'   => 250,
//                                                     'size'        => 40,
//                                                     'placeholder' => __('Form name', 'metademands')]);
//                $return .= "<br>";
//                $title  = "<i class='fas fa-1x fa-cloud-upload-alt pointer'></i>&nbsp;";
//                $title  .= _sx('button', 'Save as model', 'metademands');
//
//                $return .= Html::submit($title, ['name'  => 'save_form',
//                                                 'form'  => '',
//                                                 'id'    => 'FormAdd' . $rand,
//                                                 'class' => 'btn btn-success btn-sm']);
//                $return .= "&nbsp;";
//                $title  = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
//                $title  .= _sx('button', 'Clean form', 'metademands');
//                $return .= Html::submit($title, ['name'  => 'clean_form',
//                                                 'class' => 'btn btn-warning btn-sm']);
//                $return .= "<br>";
//                $return .= "</td></tr>";
//            }
//        }
//
//        $return .= "<table class='tab_cadre_fixe'>";
//        //      $return .= "<tr class='tab_bg_1'><th colspan='4' class='center'>";
//        //      $return .= "<div class='card-header'>";
//        //      if ($is_model == true) {
//        //         $return .= __("Your models", 'metademands');
//        //      } else {
//        //         $return .= __("Your created forms", 'metademands');
//        //      }
//        //
//        //      $return .= "</div>";
//        $return .= "<p class='card-text'>";
//        //      $return .= "</th></tr>";
//        $return .= "<tbody id='bodyForm'>";
//        if (count($forms) > 0) {
//            foreach ($forms as $form) {
//                $return .= "<tr class=''>";
//                $return .= "<td>" . Toolbox::stripslashes_deep($form['name']) . "</td>";
//                $return .= "<td>" . Html::convDateTime($form['date']) . "</td>";
//
//                //            $return .= "<td><i class='".($form['is_model'] > 0 ? 'fas' : 'far')." fa-star fa-xs mark-default me-1'
//                //            title='".($form['is_model'] > 0 ? __('Used as model', 'metademands') : __('Mark as model', 'metademands'))."'
//                //            data-bs-toggle='tooltip' data-bs-placement='right' role='button'></i>";
//                //            $return .= "</td>";
//                $return .= "<td>";
//                $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm(" . $form['id'] . ")\">";
//                $return .= "<i class='fas fa-1x fa-cloud-download-alt pointer' title='" . _sx('button', 'Load form', 'metademands') . "'
//                           data-hasqtip='0' aria-hidden='true'></i>";
//                $return .= "</button>";
//                $return .= "</td>";
//                if ($is_model == true) {
//                    $return .= "<td>";
//                    $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteForm(" . $form['id'] . ")\">";
//                    $return .= "<i class='fas fa-1x fa-trash pointer' title='" . _sx('button', 'Delete form', 'metademands') . "'
//                           data-hasqtip='0' aria-hidden='true'></i>";
//                    $return .= "</button>";
//                    $return .= "</td>";
//                }
//                $return .= "</tr>";
//            }
//        } else {
//            $return .= "<tr class=''>";
//            $return .= "<td>";
//            $return .= __("No existing forms founded", 'metademands');
//            $return .= "</td>";
//            $return .= "</tr>";
//        }
//        $return .= "</tbody>";
//        $return .= "</table>";
//        $return .= "</p>";
//        if ($is_model == true) {
//            $return .= "<script>
//                       var meta_id = {$plugin_metademands_metademands_id};
//
//                      function deleteForm(form_id) {
//                          var self_delete = false;
//                          if($form_id == form_id ){
//                              self_delete = true;
//                          }
//                          $('#ajax_loader').show();
//                          $.ajax({
//                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/deleteform.php',
//                                type: 'POST',
//                                data:
//                                  {
//                                    users_id:$users_id,
//                                    plugin_metademands_metademands_id: meta_id,
//                                    forms_id: form_id,
//                                    self_delete: self_delete
//                                  },
//                                success: function(response){
//                                    $('#bodyForm').html(response);
//                                    $('#ajax_loader').hide();
//                                    if(self_delete){
//                                        document.location.reload();
//                                    }
//                                 },
//                                error: function(xhr, status, error) {
//                                   console.log(xhr);
//                                   console.log(status);
//                                   console.log(error);
//                                 }
//                             });
//                       };
//                     </script>";
//        }
//        $step   = PluginMetademandsMetademand::STEP_SHOW;
//        $return .= "<script>
//                      var meta_id = {$plugin_metademands_metademands_id};
//                      var step = {$step};
//
//                      function loadForm(form_id) {
//                         $('#ajax_loader').show();
//                         var data_send = $('#wizard_form').serializeArray();
//                         data_send.push({name: 'plugin_metademands_forms_id', value: form_id}, {name: 'metademands_id', value: meta_id});
//                          $.ajax({
//                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadform.php',
//                                type: 'POST',
//                                data: data_send,
//                                success: function(response){
//                                    $('#ajax_loader').hide();
//                                    if (response == 1) {
//                                       document.location.reload();
//                                    } else {
//                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=' + step;
//                                    }
//                                 }
//                             });
//                       };
//                     </script>";
//        if ($is_model == true) {
//            $return .= "<script>
//                          $('#FormAdd$rand').click(function() {
//
//                             if(typeof tinyMCE !== 'undefined'){
//                                tinyMCE.triggerSave();
//                             }
//                             jQuery('.resume_builder_input').trigger('change');
//                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
//                             $('#ajax_loader').show();
//                             arrayDatas = $('#wizard_form').serializeArray();
//                             arrayDatas.push({name: \"save_form\", value: true});
//                             arrayDatas.push({name: \"is_model\", value: 1});
//                             $.ajax({
//                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/addform.php',
//                                   type: 'POST',
//                                   data: arrayDatas,
//                                   success: function(response){
//                                       $('#ajax_loader').hide();
//                                       document.location.reload();
//                                    },
//                                   error: function(xhr, status, error) {
//                                      console.log(xhr);
//                                      console.log(status);
//                                      console.log(error);
//                                    }
//                                });
//                          });
//                        </script>";
//        }
//        $return .= "<script>
//                          $('#FormSave$rand').click(function() {
//
//                             if(typeof tinyMCE !== 'undefined'){
//                                tinyMCE.triggerSave();
//                             }
//                             jQuery('.resume_builder_input').trigger('change');
//                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
//                             $('#ajax_loader').show();
//                             arrayDatas = $('#wizard_form').serializeArray();
//                             arrayDatas.push({name: \"save_model\", value: true});
//                             arrayDatas.push({name: \"is_model\", value: 1});
//                             $.ajax({
//                                url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/updateform.php',
//                                   type: 'POST',
//                                   data: arrayDatas,
//                                   success: function(response){
//                                       $('#ajax_loader').hide();
//                                       document.location.reload();
//                                    },
//                                   error: function(xhr, status, error) {
//                                      console.log(xhr);
//                                      console.log(status);
//                                      console.log(error);
//                                    }
//                                });
//                          });
//                        </script>";
//        $return .= "</span>";
//
//        return $return;
//    }

    /**
     * Display tab for each itel object
     *
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsMetademand') {
            $form_metademand_data = $this->find(['plugin_metademands_metademands_id' => $item->fields['id']]);

            if (
                $this->canView()
                && !$withtemplate) {

                $total                = count($form_metademand_data);
                $name                 = _n('Form in progress', 'Forms in progress', $total, 'metademands');

                return self::createTabEntry(
                    $name,
                    $total
                );
            }
        }
        return '';
    }



    /**
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
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $DB;

        switch ($item->getType()) {

            case 'PluginMetademandsMetademand':
                self::listFormFromMetademand($item);
                break;
        }

        return true;
    }


    /**
     * @param $item
     * @return void
     */
    public static function listFormFromMetademand($item)
    {
        $stepform = new self();

        $stepforms = $stepform->find(['plugin_metademands_metademands_id' => $item->fields['id']]);

        if (count($stepforms) > 0) {
            echo "<table class='tab_cadrehov'>";
            echo "<tr>";
            echo "<th>" . __('ID') . "</th>";
            echo "<th>" . __('Publisher', 'metademands') . "</th>";
            echo "<th>" . __('Next user in charge of demand', 'metademands') . "</th>";
            echo "<th>" . __('Date') . "</th>";
            echo "<th></th>";
            echo "</tr>";
            foreach ($stepforms as $id => $form) {

                echo "<tr>";
                echo "<td>";
                echo $id;
                echo "</td>";
                echo "<td>";
                echo getUserName($form['users_id'], 0, true);
                echo "</td>";
                echo "<td>";
                echo getUserName($form['users_id_dest'], 0, true);
                echo "</td>";
                echo "<td>";
                echo Html::convDateTime($form['date']);
                echo "</td>";
                echo "<td>";
                if (Session::haveRight("plugin_metademands_cancelform", READ)) {
                    $target = PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.form.php";
                    echo "<br><span style='color:darkred'>";
                    Html::showSimpleForm(
                        $target,
                        'delete_form_from_metademands',
                        _sx('button', 'Delete form', 'metademands'),
                        ['plugin_metademands_stepforms_id' => $id],
                        'fa-trash-alt fa-1x'
                    );
                    echo "</span>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='alert alert-important alert-info center'>" . __('No item found') . "</div>";
        }
    }
    public function post_addItem()
    {

        $options = [
            'entities_id' => $_SESSION['glpiactive_entity'],
            'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
        ];
        NotificationEvent::raiseEvent("new_step_form", $this, $options);
    }


    /**
     * Actions done after the UPDATE of the item in the database
     *
     * @param boolean $history store changes history ? (default 1)
     *
     * @return void
     **/
    public function post_updateItem($history = 1)
    {

        $options = [
            'entities_id' => $_SESSION['glpiactive_entity'],
            'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
        ];
        NotificationEvent::raiseEvent("update_step_form", $this, $options);
        parent::post_updateItem($history); // TODO: Change the autogenerated stub
    }


    /**
     * @return array
     */
    public static function getWaitingForms()
    {
        $group_user   = new Group_User();
        $groups_users = $group_user->find(['users_id' => Session::getLoginUserID()]);
        $groups       = [];
        foreach ($groups_users as $gu) {
            $groups[] = $gu['groups_id'];
        }
        $stepforms  = [];
        $condition = [];
        if (count($groups)> 0) {
            $condition = ['groups_id' => $groups];
        }
        $step       = new PluginMetademandsStep();

        $stepform   = new PluginMetademandsStepform();
        $waitingForms = [];
        $stepforms  = $stepform->find();

        foreach ($stepforms as $id => $form) {
            if((in_array($form['groups_id_dest'], $groups)
                    && $form['users_id_dest'] == 0)
                || Session::getLoginUserID() == $form['users_id_dest']) {
                $waitingForms[$id] = $form;
            }
        }
        return $waitingForms;
    }

    public static function showWaitingWarning() {

        $stepforms = self::getWaitingForms();
        if (count($stepforms) > 0) {
            echo "<div class='center alert alert-warning alert-dismissible fade show' role='alert'>";
            echo "<a href='#' class='close' data-bs-dismiss='alert' aria-label='close' style='float: right;'>&times;</a>";
            echo "<i class='fas fa-exclamation-triangle fa-2x'></i>";
            echo "<br>";
            $warnings = sprintf(__('You have %s', 'metademands'), count($stepforms));
            $warnings .= " " . _n('form', 'forms', count($stepforms), 'metademands');
            $warnings .= " " . __('to complete', 'metademands');

            echo $warnings;
            echo "<br>";

            $url = PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.php";
            echo "<a href=\"" . $url . "\">";
            if (count($stepforms) == 1) {
                echo __('Do you want to see him ?', 'metademands');
            } else {
                echo __('Do you want to see them ?', 'metademands');
            }
            echo "</a>";
            echo "</div>";
        }
    }

    public function showWaitingForm()
    {
        echo Html::css(PLUGIN_METADEMANDS_DIR_NOFULL . "/css/wizard.css.php");
        $rand         = mt_rand();

        $stepforms = self::getWaitingForms();

        if (!empty($stepforms)) {
            echo "<div class=\"row\">";
            echo "<div class=\"col-md-12\">";
            echo "<h4><div class='alert alert-dark' role='alert'>";
            $icon = "fa-share-alt";
            if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                $icon = $meta->fields['icon'];
            }
            $cnt = count($stepforms);
            echo "<i class='fa-2x fas $icon'></i>&nbsp;";
            echo _n('Your form to complete', 'Your forms to complete', $cnt, 'metademands');
            echo "</div></h4></div></div>";

            echo "<div id='listmeta'>";

            foreach ($stepforms as $id => $name) {
                $meta = new PluginMetademandsMetademand();
                if ($meta->getFromDB($name['plugin_metademands_metademands_id'])) {
                    $metaID = $name['plugin_metademands_metademands_id'];
                    $block_id = $name['block_id'];
                    echo '<div class="btnsc-normal" style="min-height: 260px" >';
                    $fasize = "fa-4x";
                    echo '<a class="bt-buttons" href="#" onclick="loadForm' . $rand . '(\'' . $id . '\',\'' . $metaID . '\',\'' . $block_id . '\')">';
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
                    echo "</a>";
                    //                    if (empty($comm = PluginMetademandsMetademand::displayField($meta->getID(), 'comment')) && !empty($meta->fields['comment'])) {
                    echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                    echo __('Edit by', 'metademands');
                    echo "&nbsp;";
                    echo User::getFriendlyNameById($name['users_id']);
                    echo "</span></em>";

                    echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";

                    echo Html::convDateTime($name['date']);
                    echo "</span></em>";
                    echo "<br><em><span style=\"font-weight: normal;font-size: 11px;padding-left:5px\">";
                    echo __('Step', 'metademands');
                    echo $block_id;
                    echo "</span></em>";
                    //TODO Change to new right
                    if (Session::haveRight("plugin_metademands_cancelform", READ)) {
                        $target = PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.form.php";
                        echo "<br><span style='color:darkred'>";
                        Html::showSimpleForm(
                            $target,
                            'delete_form_from_list',
                            _sx('button', 'Delete form', 'metademands'),
                            ['plugin_metademands_stepforms_id' => $id],
                            'fa-trash-alt fa-1x'
                        );
                        echo "</span>";
                    }
                    echo "</p></div>";
                }
            }
            echo "</div>";
        } else {
            echo "<br><div class='alert alert-important alert-info center'>";
            echo __("No existing forms founded", 'metademands');
            echo "</div>";
        }

        $users_id = Session::getLoginUserID();
        $step     = 2;
        echo "<script>
                      var step = {$step};
                      function loadForm$rand(form_id, meta_id, block_id) {
                         $('#ajax_loader').show();
                        
                         var data_send = {plugin_metademands_stepforms_id: form_id,
                                         metademands_id: meta_id,
                                         block_id: block_id,
                                         _users_id_requester: $users_id,
                                      };
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadstepform.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    if (response == 0) {
                                       $('#ajax_loader').hide();
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=' + meta_id + '&step=' + step  + '&block_id=' + block_id;
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


    function deleteAfterCreate($stepformID, $sendmail = false) {

        $self = new self();
        $self->getFromDB($stepformID);
        if ($sendmail == true) {
            $options = [
                'entities_id' => $_SESSION['glpiactive_entity'],
                'metademands_id' => $self->fields["plugin_metademands_metademands_id"]
            ];
            NotificationEvent::raiseEvent("delete_step_form", $self, $options);
        }

        $step      = new PluginMetademandsStepform();
        $step->deleteByCriteria(['id' => $stepformID]);

        $step_values      = new PluginMetademandsStepform_Value();
        $step_values->deleteByCriteria(['plugin_metademands_stepforms_id' => $stepformID]);

        $step_actor = new PluginMetademandsStepform_Actor();
        $step_actor->deleteByCriteria(['plugin_metademands_stepforms_id' => $stepformID]);

        return true;
    }



}
