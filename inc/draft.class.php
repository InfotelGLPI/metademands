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
class PluginMetademandsDraft extends CommonDBTM
{

    static $rightname = 'plugin_metademands';

    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return int|void
     */
    static function countDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id)
    {
        $self = new self();
        $drafts = $self->find([
            'users_id' => $users_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id
        ]);

        return count($drafts);
    }

    public function cleanDBonPurge()
    {
        $temp = new PluginMetademandsDraft_Value();
        $temp->deleteByCriteria(['plugin_metademands_drafts_id' => $this->fields['id']]);
    }

    function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'massiveaction' => false,
            'datatype' => 'number'
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

        $tab[] = [
            'id' => '99',
            'table' => 'glpi_plugin_metademands_metademands',
            'field' => 'name',
            'linkfield' => 'plugin_metademands_metademands_id',
            'name' => _n('form', 'forms', 1, 'metademands'),
            'massiveaction' => false,
        ];


        return $tab;
    }

    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return string
     */
    static function showDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id)
    {
        $self = new self();
        $drafts = $self->find([
            'users_id' => $users_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id
        ]);
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
            $draftname = Html::cleanInputText(
                Toolbox::stripslashes_deep(
                    $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name']
                )
            ) ?? '';
        } else {
            $draftname = '';
        }


        $return .= "<div class='card-header'>";
        $return .= __("New draft", 'metademands');
        $return .= " <span class='red'>*</span></div>";
        $return .= "<table class='tab_cadre_fixe'>";
        $return .= "<tr class=''>";
        $return .= "<td colspan='4' class='center'>";
        $return .= "<br>";
        $return .= Html::input('draft_name', [
            'value' => '',
            'maxlength' => 250,
            'size' => 40,
            'placeholder' => __('Draft name', 'metademands')
        ]);
        $return .= "<br>";
        $title = "<i class='fas fa-1x fa-cloud-upload-alt pointer'></i>&nbsp;";
        $title .= _sx('button', 'Save as draft', 'metademands');
        $return .= Html::submit($title, [
            'name' => 'save_draft',
            'form' => '',
            'id' => 'submitSave',
            'class' => 'btn btn-success btn-sm'
        ]);
        $return .= "&nbsp;";
        $title = "<i class='fas fa-1x fa-broom pointer'></i>&nbsp;";
        $title .= _sx('button', 'Clean form', 'metademands');
        $return .= Html::submit($title, [
            'name' => 'clean_form',
            'class' => 'btn btn-warning btn-sm'
        ]);
        $return .= "<br>";
        $return .= "</td></tr>";

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
                $return .= "<i class='fas fa-1x fa-cloud-download-alt pointer' title='" . _sx(
                        'button',
                        'Load draft',
                        'metademands'
                    ) . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
                $return .= "</button>";
                $return .= "</td>";

                if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'])
                    && $draft['id'] == $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id']) {
                    $return .= "<td>";
                    $return .= "<button  class='submit btn btn-success btn-sm' onclick=\"event.preventDefault();event.stopPropagation();udpateDraft(" . $draft['id'] . ", '" . $draft['name'] . "')\">";
                    $return .= "<i class='fas fa-1x fa-save pointer' title='" . _sx(
                            'button',
                            'Save model',
                            'metademands'
                        ) . "' 
                               data-hasqtip='0' aria-hidden='true'></i>";
                    $return .= "</button>";
                    $return .= "</td>";
                }

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteDraft(" . $draft['id'] . ")\">";
                $return .= "<i class='fas fa-1x fa-trash pointer' title='" . _sx(
                        'button',
                        'Delete draft',
                        'metademands'
                    ) . "' 
                           data-hasqtip='0' aria-hidden='true'></i>";
                $return .= "</button>";
                $return .= "</td>";
                $return .= "</tr>";
            }
        } else {
            $return .= "<tr class=''><td colspan='4' class='center'>" . __(
                    'No draft available for this form',
                    'metademands'
                ) . "</td></tr>";
        }
        $return .= "</tbody>";
        $return .= "</table>";
        $return .= "</p>";

        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'])) {
            $draft_id = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'];
        } else {
            $draft_id = 0;
        }
        $return .= Html::hidden(
            'plugin_metademands_drafts_id',
            ['value' => $draft_id, 'id' => 'plugin_metademands_drafts_id']
        );

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
        $step = PluginMetademandsMetademand::STEP_SHOW;
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
                          function udpateDraft(draft_id, draft_name) {
                             if(typeof tinyMCE !== 'undefined'){
                                tinyMCE.triggerSave();
                             }
                             jQuery('.resume_builder_input').trigger('change');
                             $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                             $('#ajax_loader').show();
                             arrayDatas = $('#wizard_form').serializeArray();
                             arrayDatas.push({name: \"save_draft\", value: true});
                             arrayDatas.push({name: \"plugin_metademands_drafts_id\", value: draft_id});
                             arrayDatas.push({name: \"draft_name\", value: draft_name});
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
                          }
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

    public static function loadDatasDraft($id_draft)
    {
        global $DB;

        $metademands = new PluginMetademandsMetademand();
        $draft = new PluginMetademandsDraft();

        $requester = $DB->request([
            'SELECT' => ['name', 'plugin_metademands_metademands_id'],
            'FROM' => $draft::getTable(),
            'WHERE' => [
                'id' => $id_draft,
            ],
            'LIMIT' => '1',
        ])->current();

        if ($requester != null) {
            $metademand_id = $requester['plugin_metademands_metademands_id'];

            $metademands->getFromDB($metademand_id);
            PluginMetademandsDraft_Value::loadDraftValues($metademand_id, $id_draft);
            $draft_name = $draft->getField('name');

            $_SESSION['plugin_metademands'][$metademand_id]['fields']['_users_id_requester'] = Session::getLoginUserID();

            $_SESSION['plugin_metademands'][$metademand_id]['plugin_metademands_drafts_id'] = $id_draft;
            $_SESSION['plugin_metademands'][$metademand_id]['plugin_metademands_id'] = $metademand_id;
            $_SESSION['plugin_metademands'][$metademand_id]['plugin_metademands_drafts_name'] = $requester['name'];

            return $_SESSION['plugin_metademands'][$metademand_id];
        }

        return '';
    }

    public static function showDraft($datas)
    {
        $metademands_id = $datas['plugin_metademands_id'];
        $draft_id = $datas['plugin_metademands_drafts_id'];
        $draft_name = $datas['plugin_metademands_drafts_name'];

        $metademands = new PluginMetademandsMetademand();
        $metademands_data = $metademands->constructMetademands($metademands_id);
        $metademands->getFromDB($metademands_id);

        echo "<div class=\"row\">";

        echo "<div class=\"col-md-12 md-title\">";
        echo "<div style='background-color: #FFF'>";

        echo "<div class='justify-content-between align-items-center md-color'>";// alert alert-light

        $meta = new PluginMetademandsMetademand();
        if ($meta->getFromDB($metademands_id)) {
            if (isset($meta->fields['icon']) && !empty($meta->fields['icon'])) {
                $icon = $meta->fields['icon'];
            }
        }

        echo "<h2 class='card-title' style='color: #303f62;font-weight: normal;text-align: center;width: 85%;padding: 10px 0;margin: auto;'> ";
        if (!empty($icon)) {
            echo "<i class='fa-2x fas $icon'\"></i>&nbsp;";
        }
        if (empty($n = PluginMetademandsMetademand::displayField($meta->getID(), 'name'))) {
            echo $meta->getName();
        } else {
            echo $n;
        }
        echo "</h2>";
        echo "<div class='md-basket-wizard'>";
        echo "</div>";

        echo "<div class='md-wizard' style='width: 85%;margin:auto'>";

        if (count($metademands_data)) {
            $see_summary = 0;
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    echo "<form id='draft_form'>
                <input type='hidden' name='tickets_id' value='0'>
                <input type='hidden' name='resources_id' value='0'>
                <input type='hidden' name='resources_step'>
                <input type='hidden' name='block_id' value='0'>
                <input type='hidden' name='ancestor_tickets_id' value='0'>
                <input type='hidden' name='form_metademands_id' value='$form_metademands_id'>
                
                ";
                    self::createForm(
                        $metademands_id,
                        $metademands_data,
                        $line['form'],
                        0,
                        0,
                        $draft_id,
                        $draft_name,
                    );
                }
            }
        }

        else {
            echo "</div>";
            echo "<div class='center first-bloc'>";
            echo "<div class=\"row\">";
            echo "<div class=\"bt-feature col-md-12 \">";
            echo __('No item to display');
            echo "</div></div>";
            echo "<div class=\"row\">";
            echo "<div class=\"bt-feature col-md-12 \">";
            echo Html::submit(__('Previous'), ['name' => 'previous', 'class' => 'btn btn-primary']);
            echo Html::hidden('previous_metademands_id', ['value' => $metademands_id]);
            echo "</div></div>";
        }
        echo "</div>";
    }

    public static function getIcon()
    {
        return "fa-regular fa-copy";
    }

    public static function createForm($metademands_id, $metademands_data, $lines, $preview, $itilcategories_id, $draft_id, $draft_name)
    {
        $metademands = new PluginMetademandsMetademand();
        $dbu = new DbUtils();
        $metademands->getFromDB($metademands_id);

        $users_id = Session::getLoginUserID();

        $lineForStepByStep = [];
        $data_form = [];
        $values_saved = $_SESSION['plugin_metademands'][$metademands_id]['fields'] ?? [];

        // fields arranged by their ranks
        $allfields = [];
        foreach ($lines as $fields) {
            if (array_key_exists($fields["rank"], $allfields)) {
                $allfields[$fields["rank"]][] = $fields;
            } else {
                $allfields[$fields["rank"]] = [$fields];
            }
        }

        $use_as_step = 0;
        $stepConfig = new PluginMetademandsConfigstep();
        $stepConfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id]);

        if ($metademands->fields['step_by_step_mode'] == 1) {
            if (isset($stepConfig->fields['step_by_step_interface'])) {
                switch ($stepConfig->fields['step_by_step_interface']) {
                    case PluginMetademandsConfigstep::BOTH_INTERFACE:
                        $use_as_step = 1;
                        break;
                    case PluginMetademandsConfigstep::ONLY_HELPDESK_INTERFACE:
                        if (Session::getCurrentInterface() == 'helpdesk') {
                            $use_as_step = 1;
                        }
                        break;
                    case PluginMetademandsConfigstep::ONLY_CENTRAL_INTERFACE:
                        if (Session::getCurrentInterface() == 'central') {
                            $use_as_step = 1;
                        }
                        break;
                }
            }
        }

        $hidden_blocks = [];
        $all_hidden_blocks = [];

        $count = 0;
        $columns = 2;
        $cpt = 0;

        $basketline = new PluginMetademandsBasketline();
        if ($basketlinesFind = $basketline->find(['plugin_metademands_metademands_id' => $metademands_id,
            'users_id' => Session::getLoginUserID()])) {
            echo "<div class='alert alert-warning d-flex'>";
            echo "<b>" . __('You have items on your basket', 'metademands') . "</b></div>";
        }

        if (count($lines)) {
            if ($use_as_step == 0) {
                echo "<div class='tab-nostep'>";
                $cpt = 1;
            }
            // #meta-form to avoid hijacking the whole page
            // e.preventDefault() to avoid reloading the page and lose filled values
            echo Html::scriptBlock('$("#meta-form").keypress(function(e){
                            if (e.which == 13){
                                var target = $(e.target);
                                if(!target.is("textarea")) {
                                     e.preventDefault();
                                     $("#submitjob").click();
                                     $("#nextBtn").click();
                                }
                            }
                });');
            sleep(1);

            foreach ($allfields as $block => $line) {
                if ($use_as_step == 1 && $metademands->fields['is_order'] == 0) {
                    if (!in_array($block, $all_hidden_blocks)) {
                        echo "<div class='tab-step'>";
                        $cpt++;
                    }
                }

                $style_left_right = 'padding: 0.5rem 0.5rem;';
                $keys = array_keys($line);
                $keyIndexes = array_flip($keys);

                $style = "";


                if (isset($metademands->fields['background_color'])
                    && !empty($metademands->fields['background_color'])) {
                    $background_color = $metademands->fields['background_color'];
                    $style .= ";background-color:" . $background_color . ";";
                }

                echo "<div bloc-id='bloc" . $block . "' style='$style' class='card tab-sc-child-" . $block . "'>";

                if ($line[$keys[0]]['type'] == 'title-block') {

                    $data = $line[$keys[0]];
                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $line[$keys[0]]['id']])) {
                        unset($fieldparameter->fields['plugin_metademands_fields_id']);
                        unset($fieldparameter->fields['id']);

                        $params = $fieldparameter->fields;
                        $data = array_merge($line[$keys[0]], $params);
                        if (isset($fieldparameter->fields['default'])) {
                            $line[$keys[0]]['default_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['default']);
                        }

                        if (isset($fieldparameter->fields['custom'])) {
                            $line[$keys[0]]['custom_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['custom']);
                        }
                    }

                    $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
                    $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

                    //Block Title
                    if (isset($line[$keys[0]]['type'])
                        && in_array($line[$keys[0]]['type'], $allowed_customvalues_types)
                        || in_array($line[$keys[0]]['item'], $allowed_customvalues_items)) {
                        $field_custom = new PluginMetademandsFieldCustomvalue();
                        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $line[$keys[0]]['id']], "rank")) {
                            if (count($customs) > 0) {
                                $line[$keys[0]]['custom_values'] = $customs;
                            }
                        }
                    }

                    PluginMetademandsField::displayFieldByType($metademands_data, $data, $preview, $itilcategories_id);

                }

                echo "<div class='card-body' bloc-hideid='bloc" . $block . "'>";

                if ($preview) {
                    echo "<div class=\"row preview-md preview-md-$block\" data-title='" . $block . "'>";
                } else {
                    echo "<div class=\"row\" style='$style'>";
                }

                foreach ($line as $key => $data) {
                    $config_link = "";
                    if (Session::getCurrentInterface() == 'central' && $preview) {
                        $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL('PluginMetademandsField') . "?id=" . $data['id'] . "'>";
                        $config_link .= "<i class='fas fa-wrench'></i></a>";
                    }

                    $fieldparameter            = new PluginMetademandsFieldParameter();
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['id']])) {
                        unset($fieldparameter->fields['plugin_metademands_fields_id']);
                        unset($fieldparameter->fields['id']);

                        $params = $fieldparameter->fields;
                        $data = array_merge($data, $params);

                        if (isset($fieldparameter->fields['default'])) {
                            $data['default_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['default']);
                        }

                        if (isset($fieldparameter->fields['custom'])) {
                            $data['custom_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['custom']);
                        }
                    }

                    $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
                    $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

                    if (isset($data['type'])
                        && (in_array($data['type'], $allowed_customvalues_types)
                            || in_array($data['item'], $allowed_customvalues_items))
                        && $data['item'] != "urgency"
                        && $data['item'] != "impact") {
                        $field_custom = new PluginMetademandsFieldCustomvalue();
                        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
                            if (count($customs) > 0) {
                                $data['custom_values'] = $customs;
                            }
                        }
                    }

                    // Manage ranks ???
                    if (isset($keyIndexes[$key])
                        && isset($keys[$keyIndexes[$key] - 1])
                        && $data['rank'] != $line[$keys[$keyIndexes[$key] - 1]]['rank']) {
                        //End bloc-hideid
                        echo "</div>";

                        echo "</div>";
                        echo "</div>";
                        echo "<div bloc-id='bloc" . $block . "'>";

                        // Title block field
                        if ($data['type'] == 'title-block') {
                            if ($preview) {

                                $color = PluginMetademandsField::setColor($block);
                                $style = 'padding-top:5px;
                                          padding-bottom:10px;
                                          border-top :3px solid #' . $color . ';
                                          border-left :3px solid #' . $color . ';
                                          border-right :3px solid #' . $color;
                                echo '<style type="text/css">
                                        .preview-md-';
                                echo $block;
                                echo ':before {
                                                 content: attr(data-title);
                                                 background: #';
                                echo $color . ";";
                                echo 'position: absolute;
                                       padding: 0 20px;
                                       color: #fff;
                                       right: 0;
                                       top: 0;
                                   }
                                  </style>';
                                echo "<div class=\"row preview-md preview-md-$block\" data-title='" . $block . "' style='$style'>";
                            } else {
                                echo "<div>";
                            }
                            echo "<br><h4 class=\"alert alert-light\"><span style='color:" . $data['color'] . ";'>";

                            if (empty($label = PluginMetademandsField::displayField($data['id'], 'name'))) {
                                $label = $data['name'];
                            }

                            echo $label;
                            echo $config_link;
                            if (isset($data['label2']) && !empty($data['label2'])) {
                                echo "&nbsp;";
                                if (empty($label2 = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                                    $label2 = $data['label2'];
                                }
                                Html::showToolTip(
                                    Glpi\RichText\RichText::getSafeHtml($label2),
                                    ['awesome-class' => 'fa-info-circle']
                                );
                            }
                            echo "<i id='up" . $block . "' class='fa-1x fas fa-chevron-up pointer' style='right:40px;position: absolute;color:" . $data['color'] . ";'></i>";
                            $rand = mt_rand();
                            echo Html::scriptBlock("
                                 var myelement$rand = '#up" . $block . "';
                                 var bloc$rand = 'bloc" . $block . "';
                                 $(myelement$rand).click(function() {     
                                     if($('[bloc-hideid =' + bloc$rand + ']:visible').length) {
                                         $('[bloc-hideid =' + bloc$rand + ']').hide();
                                         $(myelement$rand).toggleClass('fa-chevron-up fa-chevron-down');
                                     } else {
                                         $('[bloc-hideid =' + bloc$rand + ']').show();
                                         $(myelement$rand).toggleClass('fa-chevron-down fa-chevron-up');
                                     }
                                 });");
                            echo "</span></h4>";
                            if (!empty($data['comment'])) {
                                if (empty($comment = PluginMetademandsField::displayField($data['id'], 'comment'))) {
                                    $comment = $data['comment'];
                                }
                                $comment = htmlspecialchars_decode(stripslashes($comment));
                                echo "<label><i>" . $comment . "</i></label>";
                            }

                            echo "</div>";
                            // Other fields
                        }

                        echo "<div bloc-hideid='bloc" . $block . "'>";

                        if ($preview) {

                            $color = PluginMetademandsField::setColor($block);
                            echo '<style type="text/css">
                           .preview-md-';
                            echo $block;
                            echo ':before {
                             content: attr(data-title);
                             background: #';
                            echo $color . ";";
                            echo 'position: absolute;
                                   padding: 0 20px;
                                   color: #fff;
                                   right: 0;
                                   top: 0;
                               }
                              </style>';
                            $style = 'padding-top:5px;
                            padding-bottom:10px;
                            border-top :3px solid #' . $color . ';
                            border-left :3px solid #' . $color . ';
                            border-right :3px solid #' . $color;
                            echo "<div class=\"row preview-md preview-md-$block\" data-title='" . $block . "' style='$style'>";
                        } else {
                            $background_color = "";
                            if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                                $background_color = $meta->fields['background_color'];
                            }
                            echo "<div class=\"row class1\" style='background-color: " . $background_color . ";padding: 0.5rem 0.5rem;'>";
                        }

                        $count = 0;
                    }

                    if(isset($_SESSION['draft_creation']) && $_SESSION['draft_creation']){
                        foreach ($_SESSION['plugin_metademands'][$metademands->getID()]['fields'] as $key => $item) {
                            $_SESSION['plugin_metademands'][$metademands->getID()]['fields'][$key] = '';
                        }
                        $_SESSION['draft_creation'] = false;
                    }

                    // If values are saved in session we retrieve it
                    if (isset($_SESSION['plugin_metademands'][$metademands->getID()]['fields'])) {
                        foreach ($_SESSION['plugin_metademands'][$metademands->getID()]['fields'] as $id => $value) {
                            if (strval($data['id']) === strval($id)) {
                                $data['value'] = $value;
                            } elseif ($data['id'] . '-2' === $id) {
                                $data['value-2'] = $value;
                            }
                        }
                    }

                    // Title field
                    if ($data['type'] != 'title-block') {
                        // start wrapper div classes
                        if ($data['type'] == 'title') {
                            $data['row_display'] = 1;
                            $data['is_mandatory'] = 0;
                        }
                        $style = "";
                        $class = "";
                        if ($data['row_display'] == 1 && $data['type'] == "link") {
                            $class = "center";
                        }
                        //Add possibility to hide field
                        if ($data['type'] == 'dropdown_meta'
                            && $data['item'] == "ITILCategory_Metademands"
                            && Session::getCurrentInterface() != 'central') {
                            $class .= " itilmeta";
                        }
                        if ($data['type'] != 'informations') {
                            $class = "form-group ";
                        }
                        $bottomclass = "";
                        if ($data['type'] != 'informations') {
                            $bottomclass = "md-bottom";
                        }
                        if ($data['row_display'] == 1) {
                            echo "<div id-field='field" . $data["id"] . "' $style class=\"$bottomclass $class\">";
                            $count++;
                        } else {
                            echo "<div id-field='field" . $data["id"] . "' $style class=\"col-md-5 $bottomclass $class\">";
                        }
                        // end wrapper div classes
                        //see fields
                        PluginMetademandsField::displayFieldByType($metademands_data, $data, $preview, $itilcategories_id);

                        // Label 2 (date interval)
                        if (!empty($data['label2'])
                            && $data['type'] != 'link') {
                            $required = "";
                            $required_icon = "";
                            if ($data['is_mandatory']) {
                                $required = "style='color:red'";
                                $required_icon = " * ";
                            }

                            if ($data['type'] == 'datetime_interval' || $data['type'] == 'date_interval') {
                                echo "</div><div class=\"form-group col-md-5 md-bottom\">";
                            }
                            if (empty($label2 = PluginMetademandsField::displayField($data['id'], 'label2'))) {
                                $label2 = htmlspecialchars_decode(stripslashes($data['label2']));
                            }
                            $style = "";
                            if ($data['type'] != 'informations') {
                                $style = "style='padding: 10px;margin-top:10px'";
                            }

                            if ($data['type'] != 'informations') {
                                if ($data['type'] != 'datetime_interval' && $data['type'] != 'date_interval') {
                                    echo "<div class='alert alert-secondary' $style>";
                                    echo Glpi\RichText\RichText::getSafeHtml($label2);
                                    echo "</div>";
                                } else {
                                    echo "<span for='field[" . $data['id'] . "-2]' class='col-form-label metademand-label'>" . RichText::getTextFromHtml($label2) . "<span $required>" . $required_icon . "</span></label>";
                                }
                            }
                            $value2 = '';
                            if (isset($data['value-2'])) {
                                $value2 = $data['value-2'];
                            }

                            if ($data['type'] == 'datetime_interval' || $data['type'] == 'date_interval') {
                                echo "<span style='width: 50%!important;display: -webkit-box;'>";
                                switch ($data['type']) {
                                    case 'date_interval':
                                        Html::showDateField("field[" . $data['id'] . "-2]", ['value' => $value2, 'required' => ($data['is_mandatory'] ? "required" : "")]);
                                        $count++; // If date interval : pass to next line
                                        break;
                                    case 'datetime_interval':
                                        Html::showDateTimeField("field[" . $data['id'] . "-2]", ['value' => $value2, 'required' => ($data['is_mandatory'] ? "required" : "")]);
                                        $count++; // If date interval : pass to next line
                                        break;
                                }
                                echo "</span>";
                            }
                        }
                        echo "</div>";
                    }

                    // Next row
                    if ($count > $columns) {
                        if ($preview) {
                            $color = PluginMetademandsField::setColor($data['rank']);
                            $style_left_right = 'padding-bottom:10px;
                                       border-left :3px solid #' . $color . ';
                                       border-right :3px solid #' . $color;
                        }

                        echo "</div>";

                        $background_color = "";
                        if (isset($meta->fields['background_color']) && !empty($meta->fields['background_color'])) {
                            $background_color = $meta->fields['background_color'];
                        }
                        if ($preview) {
                            echo "<div class=\"row class2\" style='background-color: " . $background_color . ";'>";
                        } else {
                            echo "<div class=\"row class2\" style='background-color: " . $background_color . ";$style_left_right'>";
                        }

                        $count = 0;
                    }
                }

                echo "</div>";
                echo "</div>";
                echo "</div>";

                // Fields linked
                foreach ($line as $data) {

                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['id']])) {
                        unset($fieldparameter->fields['plugin_metademands_fields_id']);
                        unset($fieldparameter->fields['id']);

                        $params = $fieldparameter->fields;
                        $data = array_merge($data, $params);

                        if (isset($fieldparameter->fields['default'])) {
                            $data['default_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['default']);
                        }

                        if (isset($fieldparameter->fields['custom'])) {
                            $data['custom_values'] = PluginMetademandsFieldParameter::_unserialize($fieldparameter->fields['custom']);
                        }
                    }

                    $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
                    $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

                    if (isset($data['type'])
                        && in_array($data['type'], $allowed_customvalues_types)
                        || in_array($data['item'], $allowed_customvalues_items)) {
                        $field_custom = new PluginMetademandsFieldCustomvalue();
                        if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
                            if (count($customs) > 0) {
                                $data['custom_values'] = $customs;
                            }
                        }
                    }

                    //verifie si une sous metademande doit etre lancé
                    PluginMetademandsFieldOption::taskScript($data);

                    //Active champs obligatoires sur les fields_link
                    PluginMetademandsFieldOption::fieldsLinkScript($data);

                    //Affiche les hidden_link
                    PluginMetademandsFieldOption::fieldsHiddenScript($data);

                    //cache ou affiche les hidden_block & child_blocks
                    PluginMetademandsFieldOption::blocksHiddenScript($data);

                    PluginMetademandsFieldOption::checkboxScript($data);
                }

                if ($use_as_step == 1 && $metademands->fields['is_order'] == 0) {
                    if (!in_array($block, $all_hidden_blocks)) {
                        echo "</div>";
                    }
                }
            }
            if ($use_as_step == 0) {
                echo "</div>";
            }

            $return ="<div class='boutons_draft'>";
            $return .= "<button  class='submit btn btn-success btn-sm update_draft' onclick=\"udpateDraft(" . $draft_id . ", '" . $draft_name . "')\">";
            $return .= __('Upgrade');
            $return .= "</button>";



            $return .= "<button class='submit btn btn-danger btn-sm delete_draft' onclick=\"deleteDraft(" . $draft_id . ")\">";
            $return .=  __('Delete');
            $return .= "</button>";

            $return .= "</div></form>";

            echo $return;

            echo "<script> 
                    document.querySelector('#freeinput_table .add_item').addEventListener('click',function() {
                        if(document.querySelector('#freeinput_table #add_freeinputs')){
                            document.querySelector('#freeinput_table #add_freeinputs').parentNode.parentNode.remove();
                        }
                    });
                    
                    function udpateDraft(draft_id, draft_name) {
                         if(typeof tinyMCE !== 'undefined'){
                            tinyMCE.triggerSave();
                         }
                         jQuery('.resume_builder_input').trigger('change');
                         $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                         $('#ajax_loader').show();
                         arrayDatas = $('#draft_form').serializeArray();
                         arrayDatas.push({name: \"save_draft\", value: true});
                         arrayDatas.push({name: \"plugin_metademands_drafts_id\", value: draft_id});
                         arrayDatas.push({name: \"draft_name\", value: draft_name});
                         arrayDatas.push({name: \"step\", value: 2});
                         arrayDatas.push({name: \"_users_id_requester\", value: $users_id});
                         arrayDatas.push({name: \"metademands_id\", value: $metademands_id});
                                                  
                         $.ajax({
                            url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/adddraft.php',
                               type: 'POST',
                               data: arrayDatas,
                               success: function(response){
                                   document.location.reload();
                                },
                               error: function(xhr, status, error) {
                                  console.log(xhr);
                                  console.log(status);
                                  console.log(error);
                                } 
                         });
                    }
                    
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
                                    plugin_metademands_metademands_id: $metademands_id,
                                    drafts_id: draft_id,
                                    self_delete: self_delete
                                  },
                                success: function(response){
                                    $('#bodyDraft').html(response);
                                    $('#ajax_loader').hide();
                                    window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/draft.php'
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

    static function getMenuContent() {

        $menu['title']           = self::getMenuName(2);
        $menu['page']            = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);
        $menu['icon']            = static::getIcon();
        $menu['links']['add']    = PLUGIN_ORDERFOLLOWUP_DIR_NOFULL . "/front/draftcreation.php";

        return $menu;
    }

    public static function checkLastCreate($last_id = 0){
        global $DB;
        $draft = new PluginMetademandsDraft();

        $requester = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $draft::getTable(),
            'WHERE' => [
                'users_id' => Session::getLoginUserID(),
            ],
            'ORDER' => ['id DESC'],
            'LIMIT' => '1',
        ])->current();

        if ($requester != null) {
            //Security, we want to check, if server data are updated, and check if the ID is the last
            if($last_id == $requester['id']){
                return $requester['id'];
            }else{
                return self::checkLastCreate();
            }
        }else {
            //if there is no ID, that's mean, SQL is currently insert datas, so we launch again the function
            return self::checkLastCreate();
        }

    }
}
