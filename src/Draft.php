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
use DBConnection;
use Html;
use Migration;
use Session;
use Toolbox;

/**
 * Class Draft
 */
class Draft extends CommonDBTM
{
    public const DEFAULT_MODE = 1;
    public const BASKET_MODE = 2;

    public static $rightname = 'plugin_metademands';


    public static function getIcon()
    {
        return "ti ti-copy";
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `name`                              VARCHAR(255) NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `users_id`                          int {$default_key_sign} NOT NULL DEFAULT '0',
                        `date`                              timestamp    NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.3.0
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
        }
        //Displayprefs
        $prefs = [1 => 1, 2 => 2, 3 => 3, 99 => 4];
        foreach ($prefs as $num => $rank) {
            if (!countElementsInTable(
                "glpi_displaypreferences",
                [
                    'itemtype' => self::class,
                    'num' => $num,
                    'users_id' => 0,
                    'interface' => 'central',
                ]
            )
            ) {
                $query = $DB->buildUpdate(
                    'glpi_displaypreferences',
                    [
                        'itemtype' => self::class,
                    ],
                    [
                        'itemtype' => 'PluginMetademandsDraft',
                        'users_id' => 0,
                        'num' => $num,
                        'interface' => 'central',
                    ]
                );
                $DB->doQuery($query);
            }
        }

        $prefs = [1 => 1, 2 => 2, 3 => 3, 99 => 4];
        foreach ($prefs as $num => $rank) {
            if (!countElementsInTable(
                "glpi_displaypreferences",
                ['itemtype' => self::class,
                    'num' => $num,
                    'users_id' => 0,
                    'interface' => 'central',
                ]
            )
            ) {
                $DB->insert(
                    "glpi_displaypreferences",
                    ['itemtype' => self::class,
                        'num' => $num,
                        'rank' => $rank,
                        'users_id' => 0,
                        'interface' => 'central']
                );
            }
        }

        $query = $DB->buildUpdate(
            'glpi_savedsearches',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsDraft'
            ]
        );
        $DB->doQuery($query);

        $query = $DB->buildUpdate(
            'glpi_savedsearches_users',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsDraft'
            ]
        );
        $DB->doQuery($query);
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

        $itemtypes = ['Alert',
            'DisplayPreference',
            'Document_Item',
            'ImpactItem',
            'Item_Ticket',
            'Link_Itemtype',
            'Notepad',
            'SavedSearch',
            'DropdownTranslation',
            'NotificationTemplate',
            'Notification'];
        foreach ($itemtypes as $itemtype) {
            $item = new $itemtype;
            $item->deleteByCriteria(['itemtype' => self::class]);
        }
    }

    public static function getMenuContent()
    {
        $menu['title'] = self::getMenuName(2);
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);
        $menu['icon'] = static::getIcon();
        $menu['links']['add'] = PLUGIN_METADEMANDS_WEBDIR . "/front/draftcreation.php";

        return $menu;
    }

    /**
     * @param $users_id
     * @param $plugin_metademands_metademands_id
     *
     * @return int|void
     */
    public static function countDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id)
    {
        $self = new self();
        $drafts = $self->find([
            'users_id' => $users_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
        ]);

        return count($drafts);
    }

    public function cleanDBonPurge()
    {
        $temp = new Draft_Value();
        $temp->deleteByCriteria(['plugin_metademands_drafts_id' => $this->fields['id']]);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'massiveaction' => false,
            'datatype' => 'number',
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
    public static function showDraftsForUserMetademand($users_id, $plugin_metademands_metademands_id)
    {
        $self = new self();
        $drafts = $self->find([
            'users_id' => $users_id,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
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
            $draftname = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name'] ?? '';
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
            'placeholder' => __('Draft name', 'metademands'),
        ]);
        $return .= "<br>";
        $title =  _sx('button', 'Save as draft', 'metademands');
        $return .= Html::submit($title, [
            'name' => 'save_draft',
            'form' => '',
            'id' => 'submitSave',
            'icon' => 'ti ti-cloud-upload pointer',
            'class' => 'btn btn-success btn-sm'
        ]);
        $return .= "&nbsp;";
        $title = _sx('button', 'Clean form', 'metademands');
        $return .= Html::submit($title, [
            'name' => 'clean_form',
            'icon' => 'ti ti-playlist-x pointer',
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
                $return .= "<td>" . $draft['name'] . "</td>";
                $return .= "<td>" . Html::convDateTime($draft['date']) . "</td>";
                $return .= "</div>";

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-success btn-sm' onclick=\"loadDraft(" . $draft['id'] . ")\">";
                $return .= "<i class='ti ti-cloud-download pointer' title='" . _sx(
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
                    $return .= "<i class='ti ti-device-floppy pointer' title='" . _sx(
                        'button',
                        'Save draft',
                        'metademands'
                    ) . "'
                               data-hasqtip='0' aria-hidden='true'></i>";
                    $return .= "</button>";
                    $return .= "</td>";
                }

                $return .= "<td>";
                $return .= "<button form='' class='submit btn btn-danger btn-sm' onclick=\"deleteDraft(" . $draft['id'] . ")\">";
                $return .= "<i class='ti ti-trash pointer' title='" . _sx(
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
                          if ($draft_id == draft_id ){
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
                             });
                       };
                     </script>";

        $step = Metademand::STEP_SHOW;

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

        $metademands = new Metademand();
        $draft = new Draft();

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
            Draft_Value::loadDraftValues($metademand_id, $id_draft);
            $draft_name = $draft->getField('name');

            $_SESSION['plugin_metademands'][$metademand_id]['fields']['_users_id_requester'] = Session::getLoginUserID(
            );

            $_SESSION['plugin_metademands'][$metademand_id]['plugin_metademands_drafts_id'] = $id_draft;
            $_SESSION['plugin_metademands'][$metademand_id]['plugin_metademands_id'] = $metademand_id;
            $_SESSION['plugin_metademands'][$metademand_id]['plugin_metademands_drafts_name'] = $requester['name'];

            return $_SESSION['plugin_metademands'][$metademand_id];
        }

        return '';
    }

    public static function showDraft($datas)
    {
        global $DB;
        $metademands_id = $datas['plugin_metademands_id'];
        $draft_id = $datas['plugin_metademands_drafts_id'];
        $draft_name = $datas['plugin_metademands_drafts_name'];


        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_itilcategories.name',
            ],
            'FROM'      => 'glpi_plugin_metademands_drafts_values',
            'LEFT JOIN'       => [
                'glpi_itilcategories' => [
                    'ON' => [
                        'glpi_itilcategories' => 'id',
                        'glpi_plugin_metademands_drafts_values'          => 'value'
                    ]
                ],
                'glpi_plugin_metademands_fields' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields' => 'id',
                        'glpi_plugin_metademands_drafts_values'          => 'plugin_metademands_fields_id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_plugin_metademands_fields.item'  => 'ITILCategory_Metademands',
                'glpi_plugin_metademands_drafts_values.plugin_metademands_drafts_id'  => $draft_id
            ]
        ]);
        $cat_name = "";
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $cat_name = " - " . $data['name'];
            }
        }

        $metademands = new Metademand();
        $metademands_data = Metademand::constructMetademands($metademands_id);
        $metademands->getFromDB($metademands_id);

        echo "<div id ='content'>";
        echo "<div class='bt-container-fluid asset metademands_wizard_rank'> ";

        echo "<div id='meta-form' class='bt-block'> ";

        echo "<div class='row'>";

        $parameters['metademands_id'] = $metademands_id;
        $parameters['from_draft'] = 1;
        $parameters['cat_name'] = $cat_name;
        Wizard::showMetademandTitle($metademands, $parameters);

        echo "<div class='md-basket-wizard'>";
        echo "</div>";

        echo "<div class='md-wizard'>";

        $userid = Session::getLoginUserID();

        if (count($metademands_data)) {
            $see_summary = 0;
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    echo "<form id='wizard_form' method='post' class='formCustomDraft'
                        action= '" . Toolbox::getItemTypeFormURL(Wizard::class) . "'
                        enctype='multipart/form-data' class='metademands_img'>
                    ";
                    echo Html::hidden('tickets_id', ['value' => 0]);
                    echo Html::hidden('resources_id', ['value' => 0]);
                    echo Html::hidden('resources_step', ['value' => 0]);
                    echo Html::hidden('block_id', ['value' => 0]);
                    echo Html::hidden('ancestor_tickets_id', ['value' => 0]);
                    echo Html::hidden('step', ['value' => 1]);
                    echo Html::hidden('form_metademands_id', ['value' => $form_metademands_id]);
                    echo Html::hidden('metademands_id', ['value' => $metademands_id]);
                    echo Html::hidden('_users_id_requester', ['value' => $userid]);

                    Wizard::constructForm(
                        $metademands_id,
                        $metademands_data,
                        '',
                        $line['form'],
                        0,
                        0,
                        false,
                        0,
                        1,
                        $draft_id,
                        $draft_name,
                    );
                }
            }
        } else {
            echo "</div>";
            echo "<div class='center first-bloc'>";
            echo "<div class='row'>";
            echo "<div class=\"bt-feature col-md-12 \">";
            echo __('No results found');
            echo "</div>";
            echo "</div>";

            echo "<div class='row'>";
            echo "<div class=\"bt-feature col-md-12 \">";
            echo Html::submit(__('Previous'), ['name' => 'previous', 'class' => 'btn btn-primary']);
            echo Html::hidden('previous_metademands_id', ['value' => $metademands_id]);
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";

        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    public static function createDraftInput($type, $freetable = 0)
    {
        echo self::createDraftModalWindow("my_new_draft");

        $input_name = "<i class='fa-1x " . self::getIcon() . "'></i>&nbsp;";
        $input_name .= _sx('button', 'Save as draft', 'metademands');

        //correct css with condition
        if ($type == 1) {
//            if ($freetable == 'freetable') {
                $style = "display:inline-block;margin: 10px;display:none";
//            } else {
//                $style = "display:inline-block;margin: 10px;";
//            }
        } else {
            $style = "display:inline-block;float:left;margin-right: 10px;";
        }

        $trad = __('Careful all the lines are not confirm, are you sure you want to continue ?', 'metademands');

        // $content = "<br>";
        $content = "<div id='div_save_draft'  style='{$style}'>
                        <button form='' class='submit btn btn-primary' id='button_save_draft' type='submit' onclick='load_draft_modal()'>" . $input_name . "
                        </button>
                        <script>
                            function load_draft_modal(){

                                var tr_input = document.querySelectorAll('#freetable_table #tr_input input');
                                if (tr_input.length > 0) {
                                    var careful = false;

                                    for(var j = 0; j < tr_input.length; j++) {
                                       if(tr_input[j].value != '' && tr_input[j].value != '0'){
                                            careful = true;
                                       }
                                    }

                                    if(careful){
                                        if (!confirm('{$trad}')) {
                                            return;
                                        }
                                    }

                                }

                               document.querySelector('#my_new_draft').style = 'display:block;background-color: rgba(0, 0, 0, 0.1);';
                               document.querySelector('#my_new_draft').classList.remove('fade');
                            }
                        </script>
                      </div>";

        return $content;
    }

    public static function createDraftModalWindow($domid, $options = [])
    {
        $param = [
            'width' => 1050,
            'height' => 500,
            'modal' => true,
            'title' => '',
            'display' => true,
            'dialog_class' => 'modal-lg',
            'autoopen' => false,
            'reloadonclose' => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                if (isset($param[$key])) {
                    $param[$key] = $val;
                }
            }
        }

        $rand = mt_rand();

        $draft_name = __('Draft name', 'metademands');

        $input_name = Html::input('draft_name', [
            'value' => '',
            'maxlength' => 250,
            'size' => 40,
            'class' => 'draft_name',
            'placeholder' => __('Draft name', 'metademands'),
        ]);

        $titl_submit_button = _sx('button', 'Save as draft', 'metademands');
        $submit_button = Html::submit($titl_submit_button, [
            'name' => 'save_draft',
            'icon' => 'ti ti-cloud-upload pointer',
            'form' => '',
            'id' => 'submitSave',
            'class' => 'btn btn-success btn-sm',
            'onclick' => 'saveMyDraft()',
        ]);

        $html = <<<HTML
         <div id="$domid" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog {$param['dialog_class']}">
               <div class="modal-content">
                  <div class="modal-header">
                     <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                     <h3>{$draft_name}</h3>
                  </div>
                  <div id="divcontainer$domid" class="modal-body">
                    <div >
                     <div style="float: left">{$input_name}</div>
                     <div style="float: right">{$submit_button}</div>
                    </div>
                  </div>
               </div>
            </div>
         </div>
         <script>
            function saveMyDraft() {
                let draft_name = document.querySelector('.modal-dialog .modal-body .draft_name').value;
                udpateDraft('', draft_name)
            }
        </script>
HTML;

        $reloadonclose = $param['reloadonclose'] ? "true" : "false";
        $autoopen = $param['autoopen'] ? "true" : "false";
        $js = "$(function() {
         myModalEl{$rand} = document.getElementById('{$domid}');
         myModal{$rand}   = new bootstrap.Modal(myModalEl{$rand});

         // move modal to body
         $(myModalEl{$rand}).appendTo($('body'));


         myModalEl{$rand}.addEventListener('hide.bs.modal', function () {
            if ({$reloadonclose}) {
               window.location.reload()
            }
         });

         myModalEl{$rand}.querySelector('.btn-close').addEventListener('click', function () {
            document.querySelector('#my_new_draft').style = '';
            document.querySelector('#my_new_draft').classList.add('fade');
         });

         if ({$autoopen}) {
            myModal{$rand}.show();
         }

         document.getElementById('divcontainer$domid').onload = function() {
            if ({$param['height']} !== 'undefined') {
               var h =  {$param['height']};
            } else {
               var h =  $('#divcontainer{$domid}').contents().height();
            }
            if ({$param['width']} !== 'undefined') {
               var w =  {$param['width']};
            } else {
               var w =  $('#divcontainer{$domid}').contents().width();
            }

            $('#iframe{$domid}')
               .height(h);

            if (w >= 700) {
               $('#{$domid} .modal-dialog').addClass('modal-xl');
            } else if (w >= 500) {
               $('#{$domid} .modal-dialog').addClass('modal-lg');
            } else if (w <= 300) {
               $('#{$domid} .modal-dialog').addClass('modal-sm');
            }

            // reajust height to content
            myModal{$rand}.handleUpdate()
         };
      });";

        $out = "<script type='text/javascript'>$js</script>" . trim($html);

        if ($param['display']) {
            echo $out;
        } else {
            return $out;
        }
    }
}
