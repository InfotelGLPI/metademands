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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use Html;
use Session;
use CommonGLPI;
use User;

/**
 * Class Form
 */
class Form extends CommonDBTM
{

    public static function getIcon()
    {
        return "ti ti-eye";
    }

    public static $rightname = 'plugin_metademands';


    public function cleanDBonPurge()
    {
        $temp = new Form_Value();
        $temp->deleteByCriteria(['plugin_metademands_forms_id' => $this->fields['id']]);
    }

    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return string
     */
    public static function showFormsForUserMetademand($users_id, $plugin_metademands_metademands_id)
    {
        $self = new self();
        $condition = [
            'users_id' => $users_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
        ];
        $condition['is_model'] = 0;
        $forms = $self->find($condition, ['date DESC'], 20);

        $return = "<span class=''>";

        if (count($forms) > 0) {
            $return .= "<table class='tab_cadre_fixe'>";
            $return .= "<p class='card-text'>";
            $return .= "<tbody id='bodyForm'>";

            foreach ($forms as $form) {
                $return .= "<tr class=''>";
                $meta = new Metademand();
                $meta->getFromDB($form['plugin_metademands_metademands_id']);
                $itemtype = $form['itemtype'];

                $return .= "<td>" . $meta->getName() . " / " . Html::convDateTime($form['date']) . "</td>";

                $content = __("Name") . " : " . $form['name'];
                $content .= "<br>" . __("Date") . " : " . Html::convDateTime($form['date']);
                if ($itemtype != null && getItemForItemtype($itemtype)) {
                    $item = new $itemtype();
                    if ($item->getFromDB($form['items_id'])) {
                        $content .= "<br>" . __("URL") . " : " . $item->getLink();
                    }
                }

                $return .= "<td>";
                $return .= Html::showToolTip($content, ['awesome-class' => 'ti ti-info-circle','display' => false]);
                $return .= "</td>";

                $return .= "<td>";
                $return .= "</td>";

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm(" . $form['id'] . ")\">";
                $return .= "<i class='ti ti-cloud-download pointer' title='" . _sx(
                    'button',
                    'Load form',
                    'metademands'
                ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                $return .= "</button>";
                $return .= "</td>";
                $return .= "</tr>";
            }
            $return .= "</tbody>";
            $return .= "</p>";
            $return .= "</table>";
        }

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }
        $step = Metademand::STEP_SHOW;
        $return .= "<script>
                      var meta_id = {$plugin_metademands_metademands_id};
                      var step = {$step};
                      var itilcategories_id = {$itilcategories_id};
                      function loadForm(form_id) {
                         $('#ajax_loader').show();
                         var data_send = $('#wizard_form').serializeArray();
                         data_send.push({name: 'plugin_metademands_forms_id', value: form_id}, {name: 'metademands_id', value: meta_id});
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadform.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    $('#ajax_loader').hide();
                                    if (response == 1) {
                                       document.location.reload();
                                    } else {
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?itilcategories_id=' + itilcategories_id + '&metademands_id=' + meta_id + '&step=' + step;
                                    }
                                 }
                             });
                       };
                     </script>";

        $return .= "</span>";
        $return .= "<div class='center' style='color:lightgrey'>";
        $return .= __('Limited to your last 20 forms', 'metademands');
        $return .= "</div>";
        return $return;
    }


    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return string
     */
    public static function showPrivateFormsForUserMetademand($users_id, $plugin_metademands_metademands_id)
    {
        $self = new self();
        $condition = [
            'users_id' => $users_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
        ];
        $condition['is_model'] = 1;
        $forms_private = $self->find($condition, ['date DESC'], 20);

        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_name'])) {
            $formname = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_name'] ?? '';
        } else {
            $formname = '';
        }
        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id'])) {
            $form_id = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id'];
        } else {
            $form_id = 0;
        }

        $rand = mt_rand();
        $return = "<span class=''>";
        $return .= "<div class='card-header'>";
        $return .= __("New model", 'metademands');
        $return .= " <span class='red'>*</span>";
        $return .= "</div>";
        $return .= "<table class='tab_cadre_fixe'>";
        $return .= "<tr class=''>";
        $return .= "<td colspan='4' class='center'>";
        $return .= "<br>";
        $return .= Html::input('form_name', [
            'maxlength' => 250,
            'size' => 40,
            'placeholder' => __('Form name', 'metademands'),
        ]);
        $return .= "<br>";
        $title = _sx('button', 'Save as model', 'metademands');

        $return .= Html::submit($title, [
            'name' => 'save_form',
            'form' => '',
            'id' => 'FormAdd' . $rand,
            'icon' => 'ti ti-cloud-upload pointer',
            'class' => 'btn btn-success btn-sm',
        ]);
        $return .= "&nbsp;";
        $title = _sx('button', 'Clean form', 'metademands');
        $return .= Html::submit($title, [
            'name' => 'clean_form',
            'icon' => 'ti ti-brush pointer',
            'class' => 'btn btn-warning btn-sm',
        ]);
        $return .= "</td></tr>";
        $return .= "</table>";



        if (count($forms_private) > 0) {
            $return .= "<table class='tab_cadre_fixe'>";
            $return .= "<tr class=''>";
            $return .= "<th colspan='6'>";
            $return .= __('Your private models', 'metademands');
            $return .= "</th>";
            $return .= "</tr>";
            $return .= "<p class='card-text'>";
            $return .= "<tbody id='bodyForm'>";

            foreach ($forms_private as $form_private) {
                $return .= "<tr class=''>";
                $meta = new Metademand();
                $meta->getFromDB($form_private['plugin_metademands_metademands_id']);
                $itemtype = $form_private['itemtype'];

                $return .= "<td>" . $meta->getName() . " / " . Html::convDateTime($form_private['date']) . "</td>";

                $content = __("Name") . " : " . $form_private['name'];
                $content .= "<br>" . __("Date") . " : " . Html::convDateTime($form_private['date']);
                if ($itemtype != null && getItemForItemtype($itemtype)) {
                    $item = new $itemtype();
                    if ($item->getFromDB($form_private['items_id'])) {
                        $content .= "<br>" . __("URL") . " : " . $item->getLink();
                    }
                }

                $return .= "<td>";
                $return .= Html::showToolTip($content, ['awesome-class' => 'ti ti-info-circle','display' => false]);
                $return .= "</td>";

                $return .= "<td>";
                if (Session::haveRight("plugin_metademands_publicforms", READ)) {
                    if ($form_private['is_private'] == 1) {
                        $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"changeVisibility(" . $form_private['id'] . ", 0)\">";
                        $return .= "<i class='ti ti-lock-open pointer' title='" . _sx(
                            'button',
                            'Define as public',
                            'metademands'
                        ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                        $return .= "</button>";
                    } else {
                        $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"changeVisibility(" . $form_private['id'] . ", 1)\">";
                        $return .= "<i class='ti ti-lock pointer' title='" . _sx(
                            'button',
                            'Define as private',
                            'metademands'
                        ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                        $return .= "</button>";
                    }
                }
                $return .= "</td>";

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm(" . $form_private['id'] . ")\">";
                $return .= "<i class='ti ti-cloud-download pointer' title='" . _sx(
                    'button',
                    'Load form',
                    'metademands'
                ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                $return .= "</button>";
                $return .= "</td>";
                if ($form_id == $form_private['id']) {
                    $return .= "<td>";
                    $return .= "<button  class='submit btn btn-success btn-sm' onclick=\"event.preventDefault();event.stopPropagation();udpateForm(" . $form_private['id'] . ", '" . $form_private['name'] . "')\">";
                    $return .= "<i class='ti ti-device-floppy pointer' title='" . _sx(
                        'button',
                        'Save model',
                        'metademands'
                    ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                    $return .= "</button>";
                    $return .= "</td>";
                }

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteForm(" . $form_private['id'] . ")\">";
                $return .= "<i class='ti ti-trash pointer' title='" . _sx(
                    'button',
                    'Delete form',
                    'metademands'
                ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                $return .= "</button>";
                $return .= "</td>";
                $return .= "</tr>";
            }
            $return .= "</tbody>";
            $return .= "</table>";
            $return .= "</p>";
        }

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

        $return .= "<script>
                      $('#FormAdd$rand').click(function() {

                         if(typeof tinyMCE !== 'undefined'){
                            tinyMCE.triggerSave();
                         }
                         jQuery('.resume_builder_input').trigger('change');
                         $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                         $('#ajax_loader').show();
                         arrayDatas = $('#wizard_form').serializeArray();
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

        $return .= "<script>
                      function udpateForm(form_id, form_name) {

                         if(typeof tinyMCE !== 'undefined'){
                            tinyMCE.triggerSave();
                         }
                         jQuery('.resume_builder_input').trigger('change');
                         $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                         $('#ajax_loader').show();
                         arrayDatas = $('#wizard_form').serializeArray();
                         arrayDatas.push({name: \"save_model\", value: true});
                         arrayDatas.push({name: \"is_model\", value: 1});
                         arrayDatas.push({name: \"plugin_metademands_forms_id\", value: form_id});
                         arrayDatas.push({name: \"form_name\", value: form_name});

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
                      };
                    </script>";
        $return .= "<script>
                      function changeVisibility(form_id, is_private) {

                         if(typeof tinyMCE !== 'undefined'){
                            tinyMCE.triggerSave();
                         }
                         jQuery('.resume_builder_input').trigger('change');
                         $('select[id$=\"_to\"] option').each(function () { $(this).prop('selected', true); });
                         $('#ajax_loader').show();
                         arrayDatas = $('#wizard_form').serializeArray();
                         arrayDatas.push({name: \"save_model\", value: true});
                         arrayDatas.push({name: \"is_model\", value: 1});
                         arrayDatas.push({name: \"is_private\", value: is_private});
                         arrayDatas.push({name: \"plugin_metademands_forms_id\", value: form_id});

                         $.ajax({
                            url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/visibility.php',
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
                      };
                    </script>";

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }
        $step = Metademand::STEP_SHOW;
        $return .= "<script>
                      var meta_id = {$plugin_metademands_metademands_id};
                      var step = {$step};
                      var itilcategories_id = {$itilcategories_id};
                      function loadForm(form_id) {
                         $('#ajax_loader').show();
                         var data_send = $('#wizard_form').serializeArray();
                         data_send.push({name: 'plugin_metademands_forms_id', value: form_id}, {name: 'metademands_id', value: meta_id});
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadform.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    $('#ajax_loader').hide();
                                    if (response == 1) {
                                       document.location.reload();
                                    } else {
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?itilcategories_id=' + itilcategories_id + '&metademands_id=' + meta_id + '&step=' + step;
                                    }
                                 }
                             });
                       };
                     </script>";

        $return .= "</span>";

        return $return;
    }



    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return string
     */
    public static function showPublicFormsForUserMetademand($plugin_metademands_metademands_id)
    {
        $self = new self();
        $condition = [
            'is_model' => 1,
            'is_private' => 0,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
        ];
        $forms = $self->find($condition, ['date DESC'], 20);

        $return = "<span class=''>";

        if (count($forms) > 0) {
            $return .= "<table class='tab_cadre_fixe'>";
            $return .= "<tr class=''>";
            $return .= "<th colspan='6'>";
            $return .= __('Public models', 'metademands');
            $return .= "</th>";
            $return .= "</tr>";
            $return .= "<p class='card-text'>";
            $return .= "<tbody id='bodyForm'>";

            foreach ($forms as $form) {
                $return .= "<tr class=''>";
                $meta = new Metademand();
                $meta->getFromDB($form['plugin_metademands_metademands_id']);
                $itemtype = $form['itemtype'];

                $return .= "<td>" .$meta->getName() . "</td>";

                $content = __("Name") . " : " . $form['name'];
                $content .= "<br>" . __("Date") . " : " . Html::convDateTime($form['date']);
                $content .= "<br>" . __('Created by', 'metademands') . " : " . getUserName($form['users_id']);
                if ($itemtype != null && getItemForItemtype($itemtype)) {
                    $item = new $itemtype();
                    if ($item->getFromDB($form['items_id'])) {
                        $content .= "<br>" . __("URL") . " : " . $item->getLink();
                    }
                }

                $return .= "<td>";
                $return .= Html::showToolTip($content, ['awesome-class' => 'ti ti-info-circle','display' => false]);
                $return .= "</td>";

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm(" . $form['id'] . ")\">";
                $return .= "<i class='ti ti-cloud-download pointer' title='" . _sx(
                    'button',
                    'Load form',
                    'metademands'
                ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                $return .= "</button>";
                $return .= "</td>";

                $return .= "</tr>";
            }

            $return .= "</tbody>";
            $return .= "</table>";
            $return .= "</p>";
        }

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }
        $step = Metademand::STEP_SHOW;
        $return .= "<script>
                      var meta_id = {$plugin_metademands_metademands_id};
                      var step = {$step};
                      var itilcategories_id = {$itilcategories_id};
                      function loadForm(form_id) {
                         $('#ajax_loader').show();
                         var data_send = $('#wizard_form').serializeArray();
                         data_send.push({name: 'plugin_metademands_forms_id', value: form_id}, {name: 'metademands_id', value: meta_id});
                          $.ajax({
                             url: '" . PLUGIN_METADEMANDS_WEBDIR . "/ajax/loadform.php',
                                type: 'POST',
                                data: data_send,
                                success: function(response){
                                    $('#ajax_loader').hide();
                                    if (response == 1) {
                                       document.location.reload();
                                    } else {
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?itilcategories_id=' + itilcategories_id + '&metademands_id=' + meta_id + '&step=' + step;
                                    }
                                 }
                             });
                       };
                     </script>";

        $return .= "</span>";

        return $return;
    }

    /**
     * Display tab for each itel object
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (($item->getType() == 'Ticket' && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk')
            || $item->getType() == 'Problem'
            || $item->getType() == 'Change') {
            if ($this->canView()
                && !$withtemplate
                && countElementsInTable("glpi_plugin_metademands_forms", [
                    "itemtype" => $item->getType(),
                    "items_id" => $item->fields['id'],
                ])) {
                $form_metademand_data = $this->find(
                    ['itemtype' => $item->getType(), 'items_id' => $item->fields['id']]
                );
                $total = count($form_metademand_data);
                $name = _n('Initial form', 'Initial forms', $total, 'metademands');

                return self::createTabEntry(
                    $name,
                    $total
                );
            }
        } elseif ($item->getType() == 'User') {
            if ($this->canView()
                && !$withtemplate
                && countElementsInTable("glpi_plugin_metademands_forms", ["users_id" => $item->fields['id']])) {
                $form_metademand_data = $this->find(['users_id' => $item->fields['id']]);
                $total = count($form_metademand_data);
                $name = _n('Associated form', 'Associated forms', $total, 'metademands');

                return self::createTabEntry(
                    $name,
                    $total
                );
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
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool|true
     * @throws \GlpitestSQLError
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
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
    public function showFormsForItilObject($item)
    {
        if (!$this->canView()) {
            return false;
        }
        $form_metademand_data = $this->find([
            'itemtype' => $item->getType(),
            'items_id' => $item->fields['id'],
            'is_model' => 0,
        ], ['date DESC']);

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
                $users_id = $form_metademand_fields['users_id'];
                $items_id = $item->fields['id'];
                $itemtype = $item->getType();
                echo "<tr class='tab_bg_1'>";
                echo "<td>";
                $meta = new Metademand();
                $meta->getFromDB($plugin_metademands_metademands_id);
                echo $meta->getName();
                //            echo $form_metademand_fields['name'];
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
                echo "<i class='fas fa-2x fa-cloud-download-alt pointer' title='" . _sx(
                    'button',
                    'Load form',
                    'metademands'
                ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "</button>";
                $step = Metademand::STEP_SHOW;
                $is_validate = 0;
                $metaValidation = new MetademandValidation();
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
    public function showFormsForUser($user)
    {
        if (!$this->canView()) {
            return false;
        }
        $forms_metademands = $this->find([
            'users_id' => $user->fields['id'],
            'is_model' => 0,
        ], ['date DESC']);

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
                $users_id = $user->fields['id'];
                $items_id = $forms_metademand['items_id'];
                $itemtype = $forms_metademand['itemtype'];
                echo "<tr class='tab_bg_1'>";
                echo "<td>";
                $meta = new Metademand();
                $meta->getFromDB($plugin_metademands_metademands_id);
                echo $meta->getName();
                echo "</td>";

                echo "<td>";
                echo Html::convDateTime($forms_metademand['date']);
                echo "</td>";

                echo "<td>";
                $rand = mt_rand();
                echo "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadForm$rand(" . $forms_metademand['id'] . ", " . $forms_metademand['plugin_metademands_metademands_id'] . ")\">";
                echo "<i class='fas fa-2x fa-cloud-download-alt pointer' title='" . _sx(
                    'button',
                    'Load form',
                    'metademands'
                ) . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "</button>";
                $step = 2;
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
                                       window.location.href = '" . PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?see_form=1&metademands_id=' + meta_id + '&step=' + step;
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
            echo "<div class='alert alert-important alert-info center'>" . __('No item found') . "</div>";
        }
    }
}
