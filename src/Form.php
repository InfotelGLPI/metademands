<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands;

use Change;
use CommonDBTM;
use CommonGLPI;
use DBConnection;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Migration;
use Problem;
use Session;
use User;

/**
 * Class Form
 */
class Form extends CommonDBTM
{
    public static function getIcon()
    {
        return "ti ti-file-spark";
    }

    public static $rightname = 'plugin_metademands';


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
                        `name`                              VARCHAR(255) NOT NULL                   DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `items_id`                          int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `itemtype`                          varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `users_id`                          int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `date`                              timestamp    NOT NULL,
                        `is_model`                          tinyint      NOT NULL                   DEFAULT '0',
                        `resources_id`                      int {$default_key_sign} NOT NULL                   DEFAULT '0',
                        `is_private`                        tinyint      NOT NULL                   DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.1.0
        if (!$DB->fieldExists($table, "resources_id")) {
            $migration->addField($table, "resources_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.3.0
        if (!isIndex($table, "plugin_metademands_metademands_id")) {
            $migration->addKey($table, "plugin_metademands_metademands_id");
        }
        //version 3.4.0
        if (!$DB->fieldExists($table, "is_private")) {
            $migration->addField($table, "is_private", "tinyint NOT NULL DEFAULT 0");
            $migration->migrationOneTable($table);

            $query = $DB->buildUpdate(
                $table,
                [
                    'is_private' => 1,
                ],
                ['1' => '1']
            );
            $DB->doQuery($query);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

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

        $entries = [];
        foreach ($forms as $form) {
            $meta = new Metademand();
            $meta->getFromDB($form['plugin_metademands_metademands_id']);

            $url = null;
            $itemtype = $form['itemtype'];
            if ($itemtype != null && getItemForItemtype($itemtype)) {
                $item = new $itemtype();
                if ($item->getFromDB($form['items_id'])) {
                    $url = $item->getLink();
                }
            }

            $entries[] = [
                'id'        => (int) $form['id'],
                'name'      => $form['name'],
                'meta_name' => $meta->getName(),
                'date'      => Html::convDateTime($form['date']),
                'url'       => $url,
            ];
        }

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }

        return TemplateRenderer::getInstance()->render('@metademands/forms/user_forms_list.html.twig', [
            'entries'           => $entries,
            'meta_id'           => (int) $plugin_metademands_metademands_id,
            'step'              => Metademand::STEP_SHOW,
            'itilcategories_id' => (int) $itilcategories_id,
            'webdir'            => PLUGIN_METADEMANDS_WEBDIR,
        ]);
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

        if (isset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id'])) {
            $form_id = $_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id'];
        } else {
            $form_id = 0;
        }

        $entries = [];
        foreach ($forms_private as $form_private) {
            $meta = new Metademand();
            $meta->getFromDB($form_private['plugin_metademands_metademands_id']);

            $url = null;
            $itemtype = $form_private['itemtype'];
            if ($itemtype != null && getItemForItemtype($itemtype)) {
                $item = new $itemtype();
                if ($item->getFromDB($form_private['items_id'])) {
                    $url = $item->getLink();
                }
            }

            $entries[] = [
                'id'         => (int) $form_private['id'],
                'name'       => $form_private['name'],
                'meta_name'  => $meta->getName(),
                'date'       => Html::convDateTime($form_private['date']),
                'url'        => $url,
                'is_private' => (int) $form_private['is_private'],
            ];
        }

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }

        return TemplateRenderer::getInstance()->render('@metademands/forms/private_models_list.html.twig', [
            'entries'           => $entries,
            'rand'              => mt_rand(),
            'can_public'        => Session::haveRight("plugin_metademands_publicforms", READ),
            'form_id'           => (int) $form_id,
            'users_id'          => (int) $users_id,
            'meta_id'           => (int) $plugin_metademands_metademands_id,
            'step'              => Metademand::STEP_SHOW,
            'itilcategories_id' => (int) $itilcategories_id,
            'webdir'            => PLUGIN_METADEMANDS_WEBDIR,
        ]);
    }



    /**
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

        $entries = [];
        foreach ($forms as $form) {
            $meta = new Metademand();
            $meta->getFromDB($form['plugin_metademands_metademands_id']);

            $url = null;
            $itemtype = $form['itemtype'];
            if ($itemtype != null && getItemForItemtype($itemtype)) {
                $item = new $itemtype();
                if ($item->getFromDB($form['items_id'])) {
                    $url = $item->getLink();
                }
            }

            $entries[] = [
                'id'         => (int) $form['id'],
                'name'       => $form['name'],
                'meta_name'  => $meta->getName(),
                'date'       => Html::convDateTime($form['date']),
                'created_by' => getUserName($form['users_id']),
                'url'        => $url,
            ];
        }

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }

        return TemplateRenderer::getInstance()->render('@metademands/forms/public_models_list.html.twig', [
            'entries'           => $entries,
            'meta_id'           => (int) $plugin_metademands_metademands_id,
            'step'              => Metademand::STEP_SHOW,
            'itilcategories_id' => (int) $itilcategories_id,
            'webdir'            => PLUGIN_METADEMANDS_WEBDIR,
        ]);
    }

    /**
     * @param $plugin_metademands_metademands_id
     *
     * @return string
     */
    public static function showPublicFormsForMetademand($plugin_metademands_metademands_id)
    {
        $self = new self();
        $condition = [
            'is_model' => 1,
            'is_private' => 0,
            'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
        ];
        $forms = $self->find($condition, ['date DESC'], 20);

        $entries = [];
        foreach ($forms as $form) {
            $entries[] = [
                'id'         => (int) $form['id'],
                'name'       => $form['name'],
                'date'       => Html::convDateTime($form['date']),
                'created_by' => getUserName($form['users_id']),
            ];
        }

        $itilcategories_id = 0 ;
        if (isset($_SESSION['servicecatalog']['sc_itilcategories_id'])) {
            $cats = json_decode($_SESSION['servicecatalog']['sc_itilcategories_id'], true);
            if (is_array($cats) && count($cats) == 1) {
                $itilcategories_id = $cats[0];
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/forms/public_models_tab.html.twig', [
            'entries'           => $entries,
            'meta_id'           => (int) $plugin_metademands_metademands_id,
            'step'              => Metademand::STEP_SHOW,
            'itilcategories_id' => (int) $itilcategories_id,
            'webdir'            => PLUGIN_METADEMANDS_WEBDIR,
        ]);

        return true;
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
        if (($item->getType() == \Ticket::class && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk')
            || $item->getType() == Problem::class
            || $item->getType() == Change::class) {
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
        } elseif ($item->getType() == User::class) {
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
        } elseif ($item->getType() == Metademand::class) {
            if ($this->canView()
                && !$withtemplate
                && $total = countElementsInTable("glpi_plugin_metademands_forms", ["plugin_metademands_metademands_id" => $item->fields['id'],
                    "is_model" => 1,
                    "is_private" => 0])) {
                $name = _n('Public model form', 'Public model forms', $total, 'metademands');

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
            case \Ticket::class:
            case Problem::class:
            case Change::class:
                $form->showFormsForItilObject($item);
                break;
            case User::class:
                $form->showFormsForUser($item);
                break;
            case Metademand::class:
                $form->showPublicFormsForMetademand($item->getID());
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
                echo "<i class='ti ti-cloud-download pointer' style='font-size:2em' title='" . _sx(
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
                                         itemtype: '$itemtype'}
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
                       }
                     </script>";
            }
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            //         echo "<div class='alert alert-info center'>" . __s('No results found') . "</div>";
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
                echo "<i class='ti ti-cloud-download pointer' style='font-size:2em' title='" . _sx(
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
                                         itemtype: '$itemtype'}
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
                       }
                     </script>";
            }
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            echo "<div class='alert alert-info center'>" . __s('No results found') . "</div>";
        }
    }
}
