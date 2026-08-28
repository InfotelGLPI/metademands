<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
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
                ],
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
                    ],
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
                ],
            )
            ) {
                $DB->insert(
                    "glpi_displaypreferences",
                    ['itemtype' => self::class,
                        'num' => $num,
                        'rank' => $rank,
                        'users_id' => 0,
                        'interface' => 'central'],
                );
            }
        }

        $query = $DB->buildUpdate(
            'glpi_savedsearches',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsDraft',
            ],
        );
        $DB->doQuery($query);

        $query = $DB->buildUpdate(
            'glpi_savedsearches_users',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsDraft',
            ],
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
            $item = new $itemtype();
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

        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'])) {
            $draft_id = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id'];
        } else {
            $draft_id = 0;
        }

        $entries = [];
        foreach ($drafts as $draft) {
            $entries[] = [
                'id'   => (int) $draft['id'],
                'name' => $draft['name'],
                'date' => Html::convDateTime($draft['date']),
            ];
        }

        return TemplateRenderer::getInstance()->render('@metademands/forms/drafts_list.html.twig', [
            'entries'  => $entries,
            'draft_id' => (int) $draft_id,
            'users_id' => (int) $users_id,
            'meta_id'  => (int) $plugin_metademands_metademands_id,
            'step'     => Metademand::STEP_SHOW,
            'webdir'   => PLUGIN_METADEMANDS_WEBDIR,
        ]);
    }

    public static function loadDatasDraft($id_draft)
    {
        global $DB;

        $metademands = new Metademand();
        $draft = new Draft();

        // Drafts are personal: scope the lookup to the current user so this
        // single source cannot be used to load another user's draft (IDOR).
        $requester = $DB->request([
            'SELECT' => ['name', 'plugin_metademands_metademands_id'],
            'FROM' => $draft::getTable(),
            'WHERE' => [
                'id'       => $id_draft,
                'users_id' => Session::getLoginUserID(),
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
                        'glpi_plugin_metademands_drafts_values'          => 'value',
                    ],
                ],
                'glpi_plugin_metademands_fields' => [
                    'ON' => [
                        'glpi_plugin_metademands_fields' => 'id',
                        'glpi_plugin_metademands_drafts_values'          => 'plugin_metademands_fields_id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_plugin_metademands_fields.item'  => 'ITILCategory_Metademands',
                'glpi_plugin_metademands_drafts_values.plugin_metademands_drafts_id'  => $draft_id,
            ],
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

        $parameters['metademands_id'] = $metademands_id;
        $parameters['from_draft'] = 1;
        $parameters['cat_name'] = $cat_name;

        ob_start();
        Wizard::showMetademandTitle($metademands, $parameters);
        $title_html = ob_get_clean();

        $userid = Session::getLoginUserID();
        $form_action = Toolbox::getItemTypeFormURL(Wizard::class);

        $forms = [];
        $previous_html = '';
        if (count($metademands_data)) {
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    $hidden_html  = Html::hidden('tickets_id', ['value' => 0]);
                    $hidden_html .= Html::hidden('resources_id', ['value' => 0]);
                    $hidden_html .= Html::hidden('resources_step', ['value' => 0]);
                    $hidden_html .= Html::hidden('block_id', ['value' => 0]);
                    $hidden_html .= Html::hidden('ancestor_tickets_id', ['value' => 0]);
                    $hidden_html .= Html::hidden('step', ['value' => 1]);
                    $hidden_html .= Html::hidden('form_metademands_id', ['value' => $form_metademands_id]);
                    $hidden_html .= Html::hidden('metademands_id', ['value' => $metademands_id]);
                    $hidden_html .= Html::hidden('_users_id_requester', ['value' => $userid]);

                    ob_start();
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
                    $form_html = ob_get_clean();

                    $forms[] = [
                        'hidden_html' => $hidden_html,
                        'form_html'   => $form_html,
                    ];
                }
            }
        } else {
            $previous_html  = Html::submit(__('Previous'), ['name' => 'previous', 'class' => 'btn btn-primary']);
            $previous_html .= Html::hidden('previous_metademands_id', ['value' => $metademands_id]);
        }

        echo TemplateRenderer::getInstance()->render('@metademands/forms/draft_show.html.twig', [
            'title_html'    => $title_html,
            'form_action'   => $form_action,
            'forms'         => $forms,
            'previous_html' => $previous_html,
        ]);
    }

    public static function createDraftInput($type, $freetable = 0)
    {
        echo self::createDraftModalWindow("my_new_draft");

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

        return TemplateRenderer::getInstance()->render('@metademands/forms/draft_save_button.html.twig', [
            'style'           => $style,
            'icon'            => self::getIcon(),
            'button_label'    => _sx('button', 'Save as draft', 'metademands'),
            'confirm_message' => __(
                'Careful all the lines are not confirm, are you sure you want to continue ?',
                'metademands',
            ),
        ]);
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

        $input_name = Html::input('draft_name', [
            'value' => '',
            'maxlength' => 250,
            'size' => 40,
            'class' => 'draft_name',
            'placeholder' => __('Draft name', 'metademands'),
        ]);

        $submit_button = Html::submit(_sx('button', 'Save as draft', 'metademands'), [
            'name' => 'save_draft',
            'icon' => 'ti ti-cloud-upload pointer',
            'form' => '',
            'id' => 'submitSave',
            'class' => 'btn btn-success btn-sm',
            'onclick' => 'saveMyDraft()',
        ]);

        $out = TemplateRenderer::getInstance()->render('@metademands/forms/draft_modal.html.twig', [
            'domid'         => $domid,
            'rand'          => $rand,
            'dialog_class'  => $param['dialog_class'],
            'draft_name'    => __('Draft name', 'metademands'),
            'input_name'    => $input_name,
            'submit_button' => $submit_button,
            'reloadonclose' => (bool) $param['reloadonclose'],
            'autoopen'      => (bool) $param['autoopen'],
            'height'        => (int) $param['height'],
            'width'         => (int) $param['width'],
        ]);

        if ($param['display']) {
            echo $out;
        } else {
            return $out;
        }
    }
}
