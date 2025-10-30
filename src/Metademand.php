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

use Ajax;
use ChangeTask;
use CommonDBTM;
use CommonGLPI;
use CommonITILActor;
use CommonITILObject;
use DBConnection;
use DbUtils;
use Dropdown;
use Glpi\DBAL\QueryExpression;
use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ServiceCatalog;
use Glpi\Form\ServiceCatalog\ServiceCatalogLeafInterface;
use Glpi\RichText\RichText;
use Glpi\UI\IllustrationManager;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use GlpiPlugin\Orderfollowup\Metademand as OrderMetademand;
use GlpiPlugin\Resources\Resource;
use GlpiPlugin\Servicecatalog\Category as ServicecatalogCategory;
use Group_Ticket;
use Group_User;
use Html;
use ITILCategory;
use Log;
use MassiveAction;
use Migration;
use Override;
use Plugin;
use PluginFieldsContainer;
use PluginFieldsField;
use ProblemTask;
use Search;
use Session;
use Ticket_Ticket;
use Ticket_User;
use TicketTemplate;
use Toolbox;
use User;
use UserEmail;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


//include_once('metademandpdf.class.php');

/**
 * Class Metademand
 */
class Metademand extends CommonDBTM implements ServiceCatalogLeafInterface
{
    public const LOG_ADD = 1;
    public const LOG_UPDATE = 2;
    public const LOG_DELETE = 3;
    public const SLA_TODO = 1;
    public const SLA_LATE = 2;
    public const SLA_FINISHED = 3;
    public const SLA_PLANNED = 4;
    public const SLA_NOTCREATED = 5;

    public static $PARENT_PREFIX = '';
    public static $SON_PREFIX = '';
    public static $rightname = 'plugin_metademands';

    public const STEP_INIT = 0;
    public const STEP_LIST = 1;
    public const STEP_SHOW = 2;

    public const STEP_CREATE = "create_metademands";
    public static $types = ['Ticket', 'Problem', 'Change'];
    public const TODO = 1; // todo
    public const DONE = 2; // done
    public const FAIL = 3; // Failed


    public $dohistory = true;
    private $config;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->config = $config;
        self::$PARENT_PREFIX = $config['parent_ticket_tag'] . ' ';
        self::$SON_PREFIX = $config['son_ticket_tag'] . ' ';
    }

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
        return _n('Meta-Demand', 'Meta-Demands', $nb, 'metademands');
    }

    public static function getIcon()
    {
        return "ti ti-share";
    }

    /**
     * @return bool|int
     */
    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
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
                        `name`                             varchar(255)                             DEFAULT NULL,
                        `entities_id`                      int {$default_key_sign}                    NOT NULL DEFAULT '0',
                        `is_recursive`                     int                             NOT NULL DEFAULT '0',
                        `is_template`                      tinyint                         NOT NULL DEFAULT '0',
                        `template_name`                    varchar(255)                             DEFAULT NULL,
                        `is_active`                        tinyint                         NOT NULL DEFAULT '1',
                        `maintenance_mode`                 tinyint                         NOT NULL DEFAULT '0',
                        `can_update`                       tinyint                         NOT NULL DEFAULT '0',
                        `can_clone`                        tinyint                         NOT NULL DEFAULT '0',
                        `comment`                          text COLLATE utf8mb4_unicode_ci          DEFAULT NULL,
                        `object_to_create`                 varchar(255) collate utf8mb4_unicode_ci  DEFAULT NULL,
                        `type`                             int                             NOT NULL DEFAULT '0',
                        `itilcategories_id`                text COLLATE utf8mb4_unicode_ci NOT NULL,
                        `forms_categories_id`              int {$default_key_sign}         NOT NULL DEFAULT '0',
                        `icon`                             varchar(255)                             DEFAULT NULL,
                        `is_order`                         tinyint                                  DEFAULT 0,
                        `create_one_ticket`                tinyint                         NOT NULL DEFAULT '0',
                        `force_create_tasks`               tinyint                         NOT NULL DEFAULT '0',
                        `date_creation`                    timestamp                       NULL     DEFAULT NULL,
                        `date_mod`                         timestamp                       NULL     DEFAULT NULL,
                        `validation_subticket`             tinyint                         NOT NULL DEFAULT '0',
                        `is_deleted`                       tinyint                         NOT NULL DEFAULT '0',
                        `hide_no_field`                    tinyint                                  DEFAULT '0',
                        `hide_title`                       tinyint                                  DEFAULT '0',
                        `title_color`                      varchar(255)                             DEFAULT '#000000',
                        `background_color`                 varchar(255)                             DEFAULT '#FFFFFF',
                        `step_by_step_mode`                tinyint                         NOT NULL DEFAULT '0',
                        `show_rule`                        tinyint                         NOT NULL DEFAULT '1',
                        `initial_requester_childs_tickets` tinyint                         NOT NULL DEFAULT '1',
                        `is_basket`                        tinyint                                  DEFAULT 0,
                        `use_confirm`                      tinyint                         NOT NULL DEFAULT '0',
                        `illustration`                     varchar(255)                             DEFAULT NULL,
                        `is_pinned`                        tinyint                         NOT NULL DEFAULT '0',
                        `usage_count`                      int {$default_key_sign}         NOT NULL DEFAULT '0',
                        `description`                      longtext,
                        PRIMARY KEY (`id`),
                        KEY `name` (`name`),
                        KEY `entities_id` (`entities_id`),
                        KEY `is_recursive` (`is_recursive`),
                        KEY `is_template` (`is_template`),
                        KEY `is_deleted` (`is_deleted`),
                        KEY `forms_categories_id` (`forms_categories_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        if (!$DB->fieldExists($table, "is_active")) {
            $migration->addField($table, "is_active", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "icon")) {
            $migration->addField($table, "icon", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }

        //version 2.7.1
        if (($DB->tableExists("glpi_plugin_metademands_fields", false)
            && !$DB->fieldExists("glpi_plugin_metademands_fields", "is_basket", false)
            && !$DB->fieldExists($table, "is_order", false))
            || !$DB->fieldExists($table, "create_one_ticket", false)) {
            $metademands = new Metademand();
            $metademands = $metademands->find();
            $transient_metademands = [];

            foreach ($metademands as $metademand) {
                $itilcat = [$metademand['itilcategories_id']];
                $transient_metademands[$metademand['id']]['itil_categories'] = json_encode($itilcat);
                $transient_metademands[$metademand['id']]['metademands_id'] = $metademand['id'];
            }
            $migration->changeField($table, 'itilcategories_id', 'itilcategories_id', "VARCHAR(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);

            foreach ($transient_metademands as $transient_metademand) {
                $query = $DB->buildUpdate(
                    $table,
                    [
                        'itilcategories_id' => $transient_metademand['itil_categories'],
                    ],
                    [ 'id' => $transient_metademand['metademands_id']]
                );
                $DB->doQuery($query);
            }

            $field = new Field();
            $fields = $field->find();
            foreach ($fields as $f) {
                if (!empty($f["hidden_link"])) {
                    $array = [];
                    $array[] = $f["hidden_link"];
                    $update["id"] = $f["id"];
                    $update["hidden_link"] = json_encode($array);
                    $field->update($update);
                }
            }
        }

        if (!$DB->fieldExists($table, "is_order")) {
            $migration->addField($table, "is_order", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "date_creation")) {
            $migration->addField($table, "date_creation", "timestamp NULL DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "date_mod")) {
            $migration->addField($table, "date_mod", "timestamp NULL DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        //version 2.7.2
        if (!$DB->fieldExists($table, "create_one_ticket")) {
            $migration->addField($table, "create_one_ticket", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }
        //version 2.7.5
        if (!$DB->fieldExists($table, "validation_subticket")) {
            $migration->addField($table, "validation_subticket", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 2.7.6
        if (!$DB->fieldExists($table, "is_deleted")) {
            $migration->addField($table, "is_deleted", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "object_to_create")) {
            $migration->addField($table, "object_to_create", "varchar(255) COLLATE utf8mb4_unicode_ci default NULL");
            $migration->migrationOneTable($table);
            $query = $DB->buildUpdate(
                $table,
                [
                    'object_to_create' => 'Ticket',
                ],
                [1]
            );
            $DB->doQuery($query);
        }
        if (!$DB->fieldExists($table, "hide_no_field")) {
            $migration->addField($table, "hide_no_field", "tinyint DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "background_color")) {
            $migration->addField($table, "background_color", "varchar(255) COLLATE utf8mb4_unicode_ci default '#FFFFFF'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "title_color")) {
            $migration->addField($table, "title_color", "varchar(255) COLLATE utf8mb4_unicode_ci default '#000000'");
            $migration->migrationOneTable($table);
        }
        //version 2.7.8
        if (!$DB->fieldExists($table, "maintenance_mode")) {
            $migration->addField($table, "maintenance_mode", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.1.0
        if (!$DB->fieldExists($table, "can_update")) {
            $migration->addField($table, "can_update", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "can_clone")) {
            $migration->addField($table, "can_clone", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.2.0
        if (!$DB->fieldExists($table, "force_create_tasks")) {
            $migration->addField($table, "force_create_tasks", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.2.8
        if (!$DB->fieldExists($table, "step_by_step_mode")) {
            $migration->addField($table, "step_by_step_mode", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.2.19
        $migration->changeField($table, 'create_one_ticket', 'create_one_ticket', "tinyint NOT NULL DEFAULT '0'");

        //version 3.3.0
        if (!$DB->fieldExists($table, "is_template")) {
            $migration->addField($table, "is_template", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "template_name")) {
            $migration->addField($table, "template_name", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!isIndex($table, "name")) {
            $migration->addKey($table, "name");
        }
        if (!isIndex($table, "entities_id")) {
            $migration->addKey($table, "entities_id");
        }
        if (!isIndex($table, "is_recursive")) {
            $migration->addKey($table, "is_recursive");
        }
        if (!isIndex($table, "is_template")) {
            $migration->addKey($table, "is_template");
        }
        if (!isIndex($table, "is_deleted")) {
            $migration->addKey($table, "is_deleted");
        }
        //version 3.3.3
        if (!$DB->fieldExists($table, "show_rule")) {
            $migration->addField($table, "show_rule", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }
        //version 3.3.7
        if (!$DB->fieldExists($table, "initial_requester_childs_tickets")) {
            $migration->addField($table, "initial_requester_childs_tickets", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }
        //version 3.3.8
        if (!$DB->fieldExists($table, "is_basket")) {
            $migration->addField($table, "is_basket", "tinyint DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        //version 3.3.9
        $migration->changeField($table, 'itilcategories_id', 'itilcategories_id', "text COLLATE utf8mb4_unicode_ci NOT NULL");
        $migration->dropKey($table, "itilcategories_id");
        //version 3.3.23
        if (!$DB->fieldExists($table, "hide_title")) {
            $migration->addField($table, "hide_title", "tinyint DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.4.0
        if (!$DB->fieldExists($table, "use_confirm")) {
            $migration->addField($table, "use_confirm", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.5.0
        if (!$DB->fieldExists($table, "forms_categories_id")) {
            $migration->addField($table, "forms_categories_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!isIndex($table, "forms_categories_id")) {
            $migration->addKey($table, "forms_categories_id");
        }
        if (!$DB->fieldExists($table, "illustration")) {
            $migration->addField($table, "illustration", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "is_pinned")) {
            $migration->addField($table, "is_pinned", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "usage_count")) {
            $migration->addField($table, "usage_count", "int unsigned NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "description")) {
            $migration->addField($table, "description", "longtext");
            $migration->migrationOneTable($table);
        }

        $query = $DB->buildUpdate(
            'glpi_displaypreferences',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsMetademand'
            ]
        );
        $DB->doQuery($query);

        $query = $DB->buildUpdate(
            'glpi_savedsearches',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsMetademand'
            ]
        );
        $DB->doQuery($query);

        $query = $DB->buildUpdate(
            'glpi_savedsearches_users',
            [
                'itemtype' => self::class,
            ],
            [
                'itemtype' => 'PluginMetademandsMetademand'
            ]
        );
        $DB->doQuery($query);

        $migration->migrationOneTable($table);
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

    /**
     * Display tab for each tickets
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $dbu = new DbUtils();
        if ($dbu->countElementsInTable(
            "glpi_plugin_metademands_tickets_metademands",
            ["tickets_id" => $item->fields['id']]
        )
            || $dbu->countElementsInTable(
                "glpi_plugin_metademands_tickets_tasks",
                ["tickets_id" => $item->fields['id']]
            )) {
            if (!$withtemplate
                && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
                if ($item->getType() == 'Ticket' && $this->canView()) {
                    $ticket_metademand = new Ticket_Metademand();
                    $ticket_metademand_data = $ticket_metademand->find(['tickets_id' => $item->fields['id']]);
                    $tickets_found = [];
                    // If ticket is Parent : Check if all sons ticket are closed
                    if (count($ticket_metademand_data)) {
                        $ticket_metademand_data = reset($ticket_metademand_data);
                        $tickets_found = Ticket::getSonTickets(
                            $item->fields['id'],
                            $ticket_metademand_data['plugin_metademands_metademands_id']
                        );
                        $total = 0;
                        foreach ($tickets_found as $ticket_found) {
                            if (isset($ticket_found['parent_tickets_id'])
                                && $ticket_found['tickets_id'] == 0) {
                                continue;
                            }
                            $total++;
                        }
                        $name = _n('Child ticket', 'Child tickets', 2, 'metademands');
                    } else {
                        $ticket_task = new Ticket_Task();
                        $ticket_task_data = $ticket_task->find(['tickets_id' => $item->fields['id']]);

                        if (count($ticket_task_data)) {
                            $tickets_found = Ticket::getAncestorTickets(
                                $item->fields['id'],
                                true
                            );
                        }
                        $total = (is_array($tickets_found)) ? count($tickets_found) : 0;
                        $name = self::getTypeName($total);
                    }

                    return self::createTabEntry(
                        $name,
                        $total
                    );
                }
            } else {
                $ticket_metademand = new Ticket_Metademand();
                $ticket_metademand_datas = $ticket_metademand->find(['tickets_id' => $item->fields['id']]);

                // If ticket is Parent : Check if all sons ticket are closed
                $tickets_found = [];
                if (count($ticket_metademand_datas)) {
                    $ticket_metademand_datas = reset($ticket_metademand_datas);
                    $tickets_found = Ticket::getSonTickets(
                        $item->fields['id'],
                        $ticket_metademand_datas['plugin_metademands_metademands_id'],
                        [],
                        true,
                        true
                    );
                }
                if (count($tickets_found) > 0
                    && $item->getType() == 'Ticket'
                    && $this->canView()
                ) {
                    $name = __('Demand Progression', 'metademands');
                    return self::createTabEntry(
                        $name,
                        1
                    );
                }
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
        $metademands = new self();

        switch ($item->getType()) {
            case 'Ticket':
                if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
                    $form = new Form();
                    $form->showFormsForItilObject($item);
                    $metademands->showPluginForTicket($item);
                    $tovalidate = 0;
                    $metaValidation = new MetademandValidation();
                    if ($metaValidation->getFromDBByCrit(['tickets_id' => $item->fields['id']])
                        && ($metaValidation->fields['validate'] == MetademandValidation::TO_VALIDATE
                            || $metaValidation->fields['validate'] == MetademandValidation::TO_VALIDATE_WITHOUTTASK)) {
                        $tovalidate = 1;
                    }
                    if ($tovalidate == 0) {
                        $metademands->showProgressionForm($item);
                    }
                } else {
                    $metademands->showProgressionForm($item);
                }
                break;
        }

        return true;
    }

    /**
     * Display tab for each metademands
     *
     * @param array $options
     *
     * @return array
     */
    public function defineTabs($options = [])
    {
        $ong = [];

        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Field::class, $ong, $options);
        $this->addStandardTab(Wizard::class, $ong, $options);
        if ($this->getField('step_by_step_mode') == 1) {
            $this->addStandardTab(Configstep::class, $ong, $options);
            $this->addStandardTab(Step::class, $ong, $options);
        }
        $this->addStandardTab(TicketField::class, $ong, $options);
        $this->addStandardTab(MetademandTranslation::class, $ong, $options);
        $this->addStandardTab(Task::class, $ong, $options);
        $this->addStandardTab(Group::class, $ong, $options);
        $this->addStandardTab(ServiceCatalog::class, $ong, $options);
        if (Session::getCurrentInterface() == 'central') {
            $this->addStandardTab('Log', $ong, $options);
        }
        //TODO Change / problem ?
        if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            if ($this->getField('object_to_create') == 'Ticket') {
                $this->addStandardTab(Ticket_Metademand::class, $ong, $options);
                if ($this->getField('step_by_step_mode') == 1) {
                    $this->addStandardTab(Stepform::class, $ong, $options);
                }
            }
        }
        $this->addStandardTab(Form::class, $ong, $options);
        $this->addStandardTab(Condition::class, $ong, $options);
        $this->addStandardTab(Export::class, $ong, $options);
        return $ong;
    }


    /**
     * @param        $object
     * @param string $type
     *
     * @return bool|string
     */
    public static function redirectForm($object, $type = 'show')
    {
        global $CFG_GLPI;

        $conf = new Config();
        $config = $conf->getInstance();
        if ($config['simpleticket_to_metademand']) {
            if (($type == 'show' && $object->fields["id"] == 0)
                || ($type == 'update' && $object->fields["id"] > 0)) {
                if (!empty($object->input["itilcategories_id"])) {
                    $dbu = new DbUtils();
                    $metademand = new self();
                    $metas = $metademand->find([
                        'is_active' => 1,
                        'is_deleted' => 0,
                        'is_template' => 0,
                        'type' => $object->input["type"],
                    ]);
                    $cats = [];

                    foreach ($metas as $meta) {
                        $categories = [];
                        if (isset($meta['itilcategories_id'])) {
                            if (is_array(json_decode($meta['itilcategories_id'], true))) {
                                $categories = $meta['itilcategories_id'];
                            } else {
                                $array = [$meta['itilcategories_id']];
                                $categories = json_encode($array);
                            }
                        }
                        $cats[$meta['id']] = json_decode($categories);
                    }

                    $meta_concerned = 0;
                    foreach ($cats as $meta => $meta_cats) {
                        if (in_array($object->input['itilcategories_id'], $meta_cats)) {
                            $meta_concerned = $meta;
                        }
                    }

                    if ($meta_concerned) {
                        //$meta = reset($metas);
                        // Redirect if not linked to a resource contract type
                        if (!$dbu->countElementsInTable(
                            "glpi_plugin_metademands_metademands_resources",
                            ["plugin_metademands_metademands_id" => $meta_concerned]
                        )) {
                            unset($_SESSION['plugin_metademands']);
                            return PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?itilcategories_id="
                                . $object->input['itilcategories_id'] . "&metademands_id=" . $meta_concerned
                                . "&tickets_id=" . $object->fields["id"] . "&step=" . self::STEP_SHOW;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function post_getEmpty()
    {
        $this->fields["background_color"] = '#ffffff';
        $this->fields["hide_no_field"] = 1;
        $this->fields["is_active"] = 1;
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        global $DB;
        $cat_already_store = false;
        if (isset($input['itilcategories_id']) && !empty($input['itilcategories_id'])) {
            //retrieve all multiple cats from all metademands
            $iterator_cats = $DB->request([
                'SELECT' => ['id', 'itilcategories_id'],
                'FROM' => $this->getTable(),
                'WHERE' => ['is_deleted' => 0, 'is_template' => 0, 'type' => $input['type']],
            ]);

            $cats = $input['itilcategories_id'];
            foreach ($iterator_cats as $data) {
                if (is_array(json_decode($data['itilcategories_id'])) && is_array($cats)) {
                    $cat_already_store = !empty(array_intersect($cats, json_decode($data['itilcategories_id'])));
                }
                if ($cat_already_store) {
                    $error = __('The category is related to a demand. Thank you to select another', 'metademands');
                    Session::addMessageAfterRedirect($error, false, ERROR);
                    return false;
                }
                $iterator_cats->next();
            }
        }

        if (!$cat_already_store) {
            if (isset($input['itilcategories_id'])) {
                if ($input['itilcategories_id'] != null) {
                    $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
                } else {
                    $input['itilcategories_id'] = '';
                }
            } else {
                $input['itilcategories_id'] = '';
            }
        }

        if (empty($input['object_to_create'])) {
            Session::addMessageAfterRedirect(__('Object to create is mandatory', 'metademands'), false, ERROR);
            return false;
        }

        if (isset($input['object_to_create'])
            && ($input['object_to_create'] == 'Problem' || $input['object_to_create'] == 'Change')) {
            $input['type'] = 0;
            $input['force_create_tasks'] = 1;
        }

        $template = new self();
        if (isset($this->input['id_template'])) {
            if ($template->getFromDBByCrit([
                'id' => $this->input['id_template'],
                'is_template' => 1,
            ])) {
                $input["metademands_oldID"] = $this->input['id_template'];
                unset($input['id']);
                unset($input['withtemplate']);
            }
        }

        return $input;
    }


    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForUpdate($input)
    {
        global $DB;
        $cat_already_store = false;

        if (isset($input['itilcategories_id'])) {
            if (is_array($input['itilcategories_id']) && count($input['itilcategories_id']) > 0) {
                //retrieve all multiple cats from all metademands

                if ($input['object_to_create'] != 'Ticket') {
                    $input['type'] = 0;
                }

                $iterator_cats = $DB->request([
                    'SELECT' => ['id', 'itilcategories_id'],
                    'FROM' => $this->getTable(),
                    'WHERE' => ['is_deleted' => 0, 'is_template' => 0, 'type' => $input['type']],
                ]);
                $iterator_meta_existing_cats = $DB->request([
                    'SELECT' => 'itilcategories_id',
                    'FROM' => $this->getTable(),
                    'WHERE' => ['id' => $input['id'], 'is_deleted' => 0, 'is_template' => 0, 'type' => $input['type']],
                ]);
                $cats = [];
                $number_cats_meta = count($iterator_meta_existing_cats);
                if ($number_cats_meta) {
                    foreach ($iterator_meta_existing_cats as $data) {
                        $cats = json_decode($data['itilcategories_id']);
                        $iterator_meta_existing_cats->next();
                    }
                    if (!isset($cats) || $cats == null) {
                        $cats = [];
                    }
                }

                if (count($input['itilcategories_id']) >= count($cats)) {
                    foreach ($input['itilcategories_id'] as $post_cats) {
                        if (in_array($post_cats, $cats)) {
                            unset($cats[array_search($post_cats, $cats)]);
                        } else {
                            $cats[] = $post_cats;
                        }
                    }
                    foreach ($iterator_cats as $data) {
                        if (is_array(json_decode($data['itilcategories_id'])) && $input['id'] != $data['id']) {
                            $cat_already_store = !empty(
                                array_intersect(
                                    $cats,
                                    json_decode($data['itilcategories_id'])
                                )
                            );
                        }
                        if ($cat_already_store) {
                            $error = __(
                                'The category is related to a demand. Thank you to select another',
                                'metademands'
                            );
                            Session::addMessageAfterRedirect($error, false, ERROR);
                            return false;
                        }
                        $iterator_cats->next();
                    }
                    if (!$cat_already_store) {
                        $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
                    }
                } else {
                    $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
                }
            } else {
                $input['itilcategories_id'] = json_encode($input['itilcategories_id']);
            }
        }
        if (isset($input['is_pinned'])) {
            unset($input['itilcategories_id']);
        }

        if (isset($input['is_order']) && $input['is_order'] == 1) {
            $metademands_data = self::constructMetademands($this->getID());
            $metademands_data = array_values($metademands_data);
            if (isset($metademands_data['tasks'])
                && is_array($metademands_data['tasks'])
                && count($metademands_data['tasks']) > 0) {
                $error = __(
                    'There are sub-metademands or this is a sub-metademand. This metademand cannot be in basket mode',
                    'metademands'
                );
                Session::addMessageAfterRedirect($error, false, ERROR);
                return false;
            }
        }

        if (empty($input['object_to_create']) && empty($this->fields['object_to_create'])) {
            Session::addMessageAfterRedirect(__('Object to create is mandatory', 'metademands'), false, ERROR);
            return false;
        }

        if (isset($input['object_to_create'])
            && $input['object_to_create'] != 'Ticket') {
            $input['type'] = 0;
            $input['force_create_tasks'] = 1;
        }
        if (isset($input["_blank_picture"])) {
            $input['icon'] = 'NULL';
        }

        return $input;
    }

    public function post_addItem()
    {
        parent::post_addItem();

        if (!isset($this->input['id']) || empty($this->input['id'])) {
            $this->input['id'] = $this->fields['id'];
        }
        if (!isset($this->input["metademands_oldID"])) {
            TicketField::updateMandatoryTicketFields($this->input);
        }

        $confStep = new Configstep();

        $confStep->add(
            [
                'plugin_metademands_metademands_id' => $this->fields['id'],
                'step_by_step_interface' => Configstep::BOTH_INTERFACE,
            ]
        );

        if (isset($this->input["metademands_oldID"])) {
            // ADD fields
            $fields = Field::getItemsAssociatedTo(
                Metademand::class,
                $this->input["metademands_oldID"]
            );
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $override_input['name'] = $field->fields["name"];
                    $override_input['link_to_user'] = 0;
                    $override_input['plugin_metademands_fields_id'] = 0;
                    $override_input['plugin_metademands_tasks_id'] = 0;
                    $idfield = $field->clone($override_input);

                    //                    $fields_parameters = FieldParameter::getItemsAssociatedTo(
                    //                        Field::class,,
                    //                        $field->fields["id"]
                    //                    );
                    //                    if (!empty($fields_parameters)) {
                    //                        $override_input['plugin_metademands_fields_id'] = $idfield;
                    //                        Toolbox::logInfo($override_input);
                    //                        $fields_parameters[0]->clone($override_input);
                    //                    }
                    //
                    //                    $fields_options = FieldOption::getItemsAssociatedTo(
                    //                        Field::class,,
                    //                        $field->fields["id"]
                    //                    );
                    //                    if (!empty($fields_options)) {
                    //                        foreach ($fields_options as $k => $fields_option) {
                    //                            $override_input['plugin_metademands_fields_id'] = $idfield;
                    //                            $fields_options[$k]->clone($override_input);
                    //                        }
                    //
                    //                    }
                    //                    $fields_customvalues = FieldCustomvalue::getItemsAssociatedTo(
                    //                        Field::class,,
                    //                        $field->fields["id"]
                    //                    );
                    //                    if (!empty($fields_customvalues)) {
                    //                        foreach ($fields_customvalues as $k => $fields_customvalue) {
                    //                            $override_input['plugin_metademands_fields_id'] = $idfield;
                    //                            $override_input['name'] = $fields_customvalue->fields["name"];
                    //                            $fields_customvalues[$k]->clone($override_input);
                    //                        }
                    //                    }
                }
            }

            // ADD tasks
            $tasks = Task::getItemsAssociatedTo(
                Metademand::class,
                $this->input["metademands_oldID"]
            );
            if (!empty($tasks)) {
                foreach ($tasks as $task) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $override_input['name'] = $task->fields["name"];
                    $override_input['plugin_metademands_tasks_id'] = 0;
                    $idtask = $task->clone($override_input);

                    $fields_task = TicketTask::getItemsAssociatedTo(
                        Task::class,
                        $task->fields["id"]
                    );
                    if (!empty($fields_task)) {
                        $override_input['plugin_metademands_tasks_id'] = $idtask;
                        $fields_task[0]->clone($override_input);
                    }
                }
            }
            if ($this->input['object_to_create'] == 'Ticket') {
                // ADD ticket fields
                $ticketfields = TicketField::getItemsAssociatedTo(
                    Metademand::class,
                    $this->input["metademands_oldID"]
                );
                if (!empty($ticketfields)) {
                    foreach ($ticketfields as $ticketfield) {
                        $override_input['plugin_metademands_metademands_id'] = $this->getID();
                        $ticketfield->clone($override_input);
                    }
                }
            }

            // ADD groups
            $groups = Group::getItemsAssociatedTo(
                Metademand::class,
                $this->input["metademands_oldID"]
            );
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $group->clone($override_input);
                }
            }
            // ADD steps
            $steps = Step::getItemsAssociatedTo(
                Metademand::class,
                $this->input["metademands_oldID"]
            );
            if (!empty($steps)) {
                foreach ($steps as $step) {
                    $override_input['plugin_metademands_metademands_id'] = $this->getID();
                    $step->clone($override_input);
                }
            }
        }
    }

    /**
     * @param int $history
     */
    public function post_updateItem($history = 1)
    {
        parent::post_updateItem($history);

        if (isset($this->updates['is_order']) && $this->input['is_order'] == 1) {
            $fields = new Field();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $this->getID()]);
            if (count($fields_data) > 0) {
                foreach ($fields_data as $field) {
                    $fields->update(['is_basket' => 1, 'id' => $field['id']]);
                }
            }
        }
        $confStep = new Configstep();

        if (!$confStep->getFromDBByCrit(['plugin_metademands_metademands_id' => $this->fields['id']])) {
            $confStep->add([
                'plugin_metademands_metademands_id' => $this->fields['id'],
                'step_by_step_interface' => Configstep::BOTH_INTERFACE,
            ]);
        }
        TicketField::updateMandatoryTicketFields($this->input);
    }

    /**
     * @param $metademands_id
     *
     * @return string
     */
    public function getURL($metademands_id)
    {
        global $CFG_GLPI;
        if (!empty($metademands_id)) {
            return urldecode($CFG_GLPI["url_base"] . "/index.php?redirect=GlpiPlugin\Metademands\Wizard_" . $metademands_id);
        }
        return "";
    }

    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'comment',
            'name' => __('Comments'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'is_active',
            'name' => __('Active'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '4',
            'table'    => $this->getTable(),
            'field'    => 'hide_no_field',
            'name'     => __('Hide the "No" and empty values of fields in the tickets', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '5',
            'table' => $this->getTable(),
            'field' => 'is_order',
            'name' => __('Permit multiple form', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '6',
            'table' => $this->getTable(),
            'field' => 'create_one_ticket',
            'name' => __('Create one ticket for all lines of the basket', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'type',
            'name' => __('Type'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'object_to_create',
            'name' => __('Object to create', 'metademands'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '9',
            'table' => $this->getTable(),
            'field' => 'maintenance_mode',
            'name' => __('Maintenance mode'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '10',
            'table' => $this->getTable(),
            'field' => 'title_color',
            'name' => __('Title color', 'metademands'),
            'searchtype' => 'equals',
            'datatype' => 'color',
        ];

        $tab[] = [
            'id' => '11',
            'table' => $this->getTable(),
            'field' => 'background_color',
            'name' => __('Background color', 'metademands'),
            'searchtype' => 'equals',
            'datatype' => 'color',
        ];

        $tab[] = [
            'id' => '12',
            'table' => $this->getTable(),
            'field' => 'can_update',
            'name' => __('Allow form modification before validation', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '13',
            'table' => $this->getTable(),
            'field' => 'can_clone',
            'name' => __('Allow form modification after validation', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '14',
            'table' => $this->getTable(),
            'field' => 'is_basket',
            'name' => __('Use as basket', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '15',
            'table'         => Category::getTable(),
            'field'         => 'completename',
            'name'          => Category::getTypeName(1),
            'datatype'      => 'dropdown',
            'massiveaction' => true,
        ];

        $tab[] = [
            'id' => '121',
            'table' => $this->getTable(),
            'field' => 'date_creation',
            'name' => __('Creation date'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '19',
            'table' => $this->getTable(),
            'field' => 'date_mod',
            'name' => __('Last update'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '20',
            'table' => $this->getTable(),
            'field' => 'step_by_step_mode',
            'name' => __('Step-by-step mode', 'metademands'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id' => '92',
            'table' => $this->getTable(),
            'field' => 'itilcategories_id',
            'name' => __('Category'),
            'searchtype' => ['equals', 'notequals'],
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id' => '80',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '86',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __('Child entities'),
            'datatype' => 'bool',
        ];

        return $tab;
    }


    /**
     * @param string $field
     * @param array|string $values
     * @param array $options
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'itilcategories_id':
                if (is_array(json_decode($values[$field], true))) {
                    $categories = json_decode($values[$field], true);
                } else {
                    $categories = [$values[$field]];
                }
                $display = "";
                if (count($categories) > 0) {
                    foreach ($categories as $category) {
                        $pass = false;
                        if (isset($PLUGIN_HOOKS['metademands'])) {
                            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                                $new_drop = Dropdownmeta::getPluginDropdownItilcategoryName(
                                    $plug,
                                    $category
                                );
                                if (Plugin::isPluginActive($plug) && $new_drop > 0) {
                                    $display .= $new_drop . "<br>";
                                    $pass = true;
                                }
                            }
                        }

                        if (!$pass) {
                            $display .= Dropdown::getDropdownName("glpi_itilcategories", $category) . "<br>";
                        }
                    }
                }
                return $display;

            case 'type':
                return \Ticket::getTicketTypeName($values[$field]);

            case 'object_to_create':
                return self::getObjectTypeName($values[$field]);

        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            //            case 'itilcategories_id':
            //                $opt = ['name' => $name,
            //                    'value' => $values[$field],
            //                    'display' => false];
            //                return ITILCategory::dropdown($opt);
            case 'type':
                $options['value'] = $values[$field];
                return \Ticket::dropdownType($name, $options);
            case 'object_to_create':
                return Dropdown::showFromArray($name, self::getObjectTypes(), $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public static function registerType($type)
    {
        if (!in_array($type, self::$types)) {
            self::$types[] = $type;
        }
    }


    /**
     * Type than could be linked to a Rack
     *
     * @param $all boolean, all type, or only allowed ones
     *
     * @return array of types
     **/
    public static function getTypes($all = false)
    {
        if ($all) {
            return self::$types;
        }

        // Only allowed types
        $types = self::$types;

        foreach ($types as $key => $type) {
            if (!class_exists($type)) {
                continue;
            }

            $item = new $type();
            if (!$item->canView()) {
                unset($types[$key]);
            }
        }
        return $types;
    }

    /**
     * @param $value
     *
     * @return string
     */
    private static function getObjectTypeName($value)
    {
        if (class_exists($value)) {
            $item = new $value();
            return $item->getTypeName(1);
        }
        return "";
    }

    /**
     * @return array
     */
    private static function getObjectTypes()
    {
        $types = [Dropdown::EMPTY_VALUE];
        foreach (self::getTypes(true) as $type) {
            $item = new $type();
            $types[$type] = $item->getTypeName(1);
        }
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                if (Plugin::isPluginActive($plug)) {
                    $datas = Metademand::getPluginObjectType($plug);
                    if (is_array($datas)) {
                        $type = $datas['type'];
                        $name = $datas['name'];

                        $types[$type] = $name;
                    }
                }
            }
        }
        return $types;
    }

    public function showForm($ID, $options = [])
    {
        $options['formoptions'] = "data-track-changes=false";
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $is_template = isset($options['withtemplate']) && (int) $options['withtemplate'] === 1;
        $from_template = isset($options['withtemplate']) && (int) $options['withtemplate'] === 2;

        if ($is_template & !$this->isNewItem()) {
            // Show template name after creation (creation is already handled by
            // showFormHeader which add the template name in a special header
            // only displayed on creation)
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Template name') . "</td>";
            echo "<td>";
            echo Html::input('template_name', [
                'value' => $this->fields['template_name'],
            ]);
            echo "</td>";
            echo "<td colspan='2'>&nbsp;</td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo Html::hidden('withtemplate', ['value' => $options['withtemplate']]);
        echo Html::hidden('id_template', ['value' => $ID]);
        if ($this->fields['maintenance_mode'] == 1) {
            echo "<h3>";
            echo "<div class='alert alert-warning center'>";
            echo "<i class='ti ti-alert-triangle' style='font-size:2em;color:orange'></i>&nbsp;";
            echo __('This form is in maintenance mode', 'metademands') . "</div></h3>";
        }

        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name'], 'size' => 40]);
        echo "</td>";

        echo "<td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("is_active", $this->fields['is_active']);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Allow form modification before validation', 'metademands') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("can_update", $this->fields['can_update']);
        echo "</td>";
        echo "<td>" . __('Allow form modification after validation', 'metademands') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("can_clone", $this->fields['can_clone']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>";

        echo __('Step-by-step mode', 'metademands');
        echo "</td>";
        echo "<td>";

        Dropdown::showYesNo("step_by_step_mode", $this->fields['step_by_step_mode']);
        echo "</td>";

        echo "<td>" . __('Maintenance mode') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("maintenance_mode", $this->fields['maintenance_mode']);
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Object to create', 'metademands') . "&nbsp;<span style='color:red;'>*</span></td>";
        echo "<td>";
        if ($ID == 0 || empty($ID)) {
            $objects = self::getObjectTypes();
            $idDropdown = Dropdown::showFromArray(
                'object_to_create',
                $objects,
                ['value' => $this->fields['object_to_create']]
            );
            Ajax::updateItemOnEvent(
                "dropdown_object_to_create" . $idDropdown,
                "define_object",
                PLUGIN_METADEMANDS_WEBDIR . "/ajax/type_object.php",
                ['object_to_create' => '__VALUE__']
            );
        } else {
            echo self::getObjectTypeName($this->fields['object_to_create']);
            echo Html::hidden('object_to_create', ['value' => $this->fields['object_to_create']]);
        }
        echo "</td>";
        echo "<td colspan='2'>";

        echo "<span id='define_object'>";
        echo "</span>";

        echo "</td>";
        echo "</tr>";

        if ($ID > 0) {
            echo "<tr class='tab_bg_1'>";

            if ($this->fields['object_to_create'] == 'Ticket') {
                echo "<td>" . _n('Type', 'Types', 1) . "</td>";
                echo "<td>";
                $opt = [
                    'value' => $this->fields['type'],
                ];
                $rand = \Ticket::dropdownType('type', $opt);

                $params = [
                    'type' => '__VALUE__',
                    'entity_restrict' => $this->fields['entities_id'],
                    'value' => $this->fields['itilcategories_id'],
                    'currenttype' => $this->fields['type'],
                ];

                Ajax::updateItemOnSelectEvent(
                    "dropdown_type$rand",
                    "show_category_by_type",
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/dropdownITILCategories.php",
                    $params
                );
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
            }

            echo "<td>" . __('Category') . "</td>";
            echo "<td>";

            $availableCategories = self::getAvailableItilCategories($ID);

            $categories = [];
            if (isset($this->fields['itilcategories_id'])) {
                if (is_array($this->fields['itilcategories_id'])) {
                    $categories = json_encode($this->fields['itilcategories_id']);
                } elseif (is_array(json_decode($this->fields['itilcategories_id'], true))) {
                    $categories = $this->fields['itilcategories_id'];
                } else {
                    $array = [$this->fields['itilcategories_id']];
                    $categories = json_encode($array);
                }
            }
            $values = $this->fields['itilcategories_id'] ? json_decode($categories) : [];

            // if all available itil categories are selected checkbox is checked
            if (count($availableCategories)) {
                $diff1 = array_diff($values, array_keys($availableCategories));
                $diff2 = array_diff(array_keys($availableCategories), $values);
                $checked = empty($diff1) && empty($diff2) ? 'checked' : '';
                echo "<div class='custom-control custom-checkbox custom-control-inline'>
                    <label>" . __('All available categories', 'metademands') . "</label>
                    <input id='itilcategories_id_all' class='form-check-input' type='checkbox' name='itilcategories_id_all' value='1' $checked>
                </div>";
                $jsDefaultVisibility = $checked ? 'categoriesSelect.hidden = true' : '';
                echo "<script type='text/javascript'>
                        $(function() {
                            let categoriesSelect = document.getElementById('show_category_by_type');
                            $jsDefaultVisibility
                            document.getElementById('itilcategories_id_all').addEventListener('change', (e) => categoriesSelect.hidden = e.target.checked)
                        })
                    </script>";
            }

            echo "<span id='show_category_by_type'>";
            Dropdown::showFromArray(
                'itilcategories_id',
                $availableCategories,
                [
                    'values' => $values,
                    'width' => '100%',
                    'multiple' => true,
                    'entity' => $_SESSION['glpiactiveentities'],
                ]
            );
            echo "</span>";
            echo "</td>";
            echo "</tr>";
        }


        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('URL') . "</td><td>";
        echo $this->getURL($ID);
        echo "</td>";

        echo "<td></td>";
        echo "<td>";
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Icon') . "</td><td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon',
            [$this->fields['icon'] => $this->fields['icon']],
            [
                'id' => $icon_selector_id,
                'selected' => $this->fields['icon'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");
        echo "&nbsp;<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Permit multiple form', 'metademands') . "</td><td>";
        Dropdown::showYesNo("is_order", $this->fields['is_order']);
        echo "</td>";

        if ($this->fields['is_order'] == 1) {
            echo "<td>" . __('Create one ticket for all lines of the basket', 'metademands') . "</td><td>";
            Dropdown::showYesNo("create_one_ticket", $this->fields['create_one_ticket']);
            echo "<br>";
            echo "<span class='alert alert-warning d-flex'>";
            echo __('You cannot use this parameter if there is more than one category', 'metademands');
            echo "</span>";
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";

        echo "<td>" . __('Use as basket', 'metademands') . "</td><td>";
        Dropdown::showYesNo("is_basket", $this->fields['is_basket']);
        echo "</td>";

        echo "<td>" . __('Hide title', 'metademands') . "</td><td>";
        Dropdown::showYesNo("hide_title", $this->fields['hide_title']);
        echo "</td>";
        echo "</tr>";

        if ($this->fields['object_to_create'] == 'Ticket') {
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Need validation to create subticket', 'metademands') . "</td><td>";
            Dropdown::showYesNo("validation_subticket", $this->fields['validation_subticket']);
            echo "</td>";
            echo "<td>";
            echo __('Hide the "No" and empty values of fields in the tickets', 'metademands');
            echo "</td><td>";
            Dropdown::showYesNo("hide_no_field", $this->fields['hide_no_field']);
            echo "</td>";
            echo "</tr>";
        }
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Background color', 'metademands') . "</td><td>";
        Html::showColorField('background_color', ['value' => $this->fields["background_color"]]);
        echo "</td>";
        echo "<td>" . __('Title color', 'metademands') . "</td><td>";
        Html::showColorField('title_color', ['value' => $this->fields["title_color"]]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        if ($ID > 0) {
            if ($this->fields['object_to_create'] == 'Ticket') {
                echo "<td>" . __('Create tasks (not child tickets)', 'metademands') . "</td>";
                echo "<td>";
                Dropdown::showYesNo("force_create_tasks", $this->fields['force_create_tasks']);
                echo "</td>";
            } else {
                echo "<td colspan='2'></td>";
                echo Html::hidden('force_create_tasks', ['value' => 1]);
            }
        }

        echo "<td>" . __('Define initial requester as requester for child tickets', 'metademands') . "</td>";
        echo "<td>";
        Dropdown::showYesNo("initial_requester_childs_tickets", $this->fields['initial_requester_childs_tickets']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Use a confirm popup check for empty values', 'metademands') . "</td><td>";
        Dropdown::showYesNo("use_confirm", $this->fields['use_confirm']);
        echo "</td>";

        echo "<td rowspan='2'>" . __('Comments') . "</td>";
        echo "<td rowspan='2'>";
        Html::textarea([
            'name' => 'comment',
            'value' => $this->fields["comment"],
            'cols' => 40,
            'rows' => 10,
            'enable_richtext' => false,
            'enable_fileupload' => false,
        ]);
        echo "</td>";

        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }


    /**
     * @param $metademands_id
     */
    public function showDuplication($metademands_id)
    {
        echo "<div class='alert alert-warning' role='alert'>";
        echo "<i class='ti ti-alert-triangle' style='font-size:2em;color:orange'></i>&nbsp;";
        echo __(
            'Tasks tree cannot be changed as unresolved related tickets exist or activate maintenance mode',
            'metademands'
        );

        echo "<br><br><form name='task_form' id='task_form' method='post'
               action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        echo Html::submit(_sx('button', 'Duplicate'), ['name' => 'execute', 'class' => 'btn btn-primary']);
        echo Html::hidden('_method', ['value' => 'Duplicate']);
        echo Html::hidden('metademands_id', ['value' => $metademands_id]);
        echo Html::hidden('redirect', ['value' => 1]);

        Html::closeForm();
        echo "</div>";
    }

    /**
     * @param       $ID
     * @param array $field
     */
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        $this->getFromDB($ID);

        switch ($field['name']) {
            case 'url':
                echo $this->getURL($this->fields['id']);
                break;
            case 'itilcategories_id':
                echo Html::hidden('type', ['value' => $this->fields['type']]);
                switch ($this->fields['type']) {
                    case \Ticket::INCIDENT_TYPE:
                        $criteria = ['is_incident' => 1];
                        break;
                    case \Ticket::DEMAND_TYPE:
                        $criteria = ['is_request' => 1];
                        break;
                    default:
                        $criteria = [];
                        break;
                }
                $criteria += getEntitiesRestrictCriteria(
                    ITILCategory::getTable(),
                    'entities_id',
                    $_SESSION['glpiactiveentities'],
                    true
                );

                $dbu = new DbUtils();

                $crit["is_deleted"] = 0;
                $crit["is_template"] = 0;
                $crit += [
                    'NOT' => [
                        'id' => $ID,
                    ],
                ];
                $cats = $dbu->getAllDataFromTable(self::getTable(), $crit);

                $used = [];
                foreach ($cats as $item) {
                    $tempcats = json_decode($item['itilcategories_id'], true);
                    if (is_array($tempcats)) {
                        foreach ($tempcats as $tempcat) {
                            $used [] = $tempcat;
                        }
                    }
                }

                $ticketcats = $dbu->getAllDataFromTable(TicketTask::getTable());
                foreach ($ticketcats as $item) {
                    if ($item['itilcategories_id'] > 0) {
                        $used [] = $item['itilcategories_id'];
                    }
                }
                if (count($used) > 0) {
                    $used = array_unique($used);
                    $criteria += [
                        'NOT' => [
                            'id' => $used,
                        ],
                    ];
                }
                $dbu = new DbUtils();
                $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $criteria);
                $temp = [];
                foreach ($result as $item) {
                    $temp[$item['id']] = $item['completename'];
                }
                $categories = [];
                if (isset($this->fields['itilcategories_id'])) {
                    if (is_array(json_decode($this->fields['itilcategories_id'], true))) {
                        $categories = $this->fields['itilcategories_id'];
                    } else {
                        $array = [$this->fields['itilcategories_id']];
                        $categories = json_encode($array);
                    }
                }
                $values = $this->fields['itilcategories_id'] ? json_decode($categories) : [];

                Dropdown::showFromArray(
                    'itilcategories_id',
                    $temp,
                    [
                        'values' => $values,
                        'width' => '100%',
                        'multiple' => true,
                        'entity' => $_SESSION['glpiactiveentities'],
                    ]
                );
                break;
            case 'tickettemplates_id':
                $opt['condition'] = [];
                $opt['value'] = $this->fields['tickettemplates_id'];
                $opt['entity'] = $_SESSION['glpiactiveentities'];
                TicketTemplate::dropdown($opt);
                break;
            case 'icon':
                $icon_selector_id = 'icon_' . mt_rand();
                echo Html::select(
                    'icon',
                    [$this->fields['icon'] => $this->fields['icon']],
                    [
                        'id' => $icon_selector_id,
                        'selected' => $this->fields['icon'],
                        'style' => 'width:175px;',
                    ]
                );

                echo Html::script('js/modules/Form/WebIconSelector.js');
                echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

                break;
        }
    }

    /**
     * Add Logs
     *
     * @param $input
     * @param $logtype
     *
     * @return void
     */
    public static function addLog($input, $logtype)
    {
        $new_value = $_SESSION["glpiname"] . " ";
        if ($logtype == self::LOG_ADD) {
            $new_value .= __('field add on demand', 'metademands') . " : ";
        } elseif ($logtype == self::LOG_UPDATE) {
            $new_value .= __('field update on demand', 'metademands') . " : ";
        } elseif ($logtype == self::LOG_DELETE) {
            $new_value .= __('field delete on demand', 'metademands') . " : ";
        }

        $metademand = new self();
        $metademand->getFromDB($input['plugin_metademands_metademands_id']);

        $field = new Field();
        $field->getFromDB($input['id']);

        $new_value .= $metademand->getName() . " - " . $field->getName();

        self::addHistory($input['plugin_metademands_metademands_id'], __CLASS__, "", $new_value);
        self::addHistory($input['id'], Field::class, "", $new_value);
    }

    /**
     * Add an history
     *
     * @param        $ID
     * @param        $type
     * @param string $old_value
     * @param string $new_value
     *
     * @return void
     */
    public static function addHistory($ID, $type, $old_value = '', $new_value = '')
    {
        $changes[0] = 0;
        $changes[1] = $old_value;
        $changes[2] = $new_value;
        Log::history($ID, $type, $changes, 0, Log::HISTORY_LOG_SIMPLE_MESSAGE);
    }

    /**
     * methodAddMetademands : Add metademand from WEBSERVICE plugin
     *
     * @param  $params
     * @param  $protocol
     *
     * @return
     * @throws \GlpitestSQLError
     *
     */
    //   static function methodAddMetademands($params, $protocol) {
    //
    //      if (isset($params['help'])) {
    //         return ['help'           => 'bool,optional',
    //                 'metademands_id' => 'int,mandatory',
    //                 'values'         => 'array,optional'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      if (!isset($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
    //      }
    //
    //      if (isset($params['metademands_id']) && !is_numeric($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', 'metademands_id');
    //      }
    //
    //      $metademands = new self();
    //
    //      if (!$metademands->can(-1, UPDATE) && !Group::isUserHaveRight($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
    //      }
    //
    //      $meta_data = [];
    //
    //      if (isset($params['values']['fields']) && count($params['values']['fields'])) {
    //         foreach ($params['values']['fields'] as $data) {
    //            $meta_data['fields'][$data['id']] = $data['values'];
    //         }
    //      }
    //      return $metademands->addObjects($params['metademands_id'], $meta_data);
    //   }

    //   /**
    //    * methodGetIntervention : Get intervention from WEBSERVICE plugin
    //    *
    //    * @param type  $params
    //    * @param type  $protocol
    //    *
    //    * @return type
    //    * @throws \GlpitestSQLError
    //    * @global type $DB
    //    *
    //    */
    //   static function methodShowMetademands($params, $protocol) {
    //
    //      if (isset($params['help'])) {
    //         return ['help'           => 'bool,optional',
    //                 'metademands_id' => 'int'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      if (!isset($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
    //      }
    //
    //      $metademands = new self();
    //
    //      if (!$metademands->canCreate() && !Group::isUserHaveRight($params['metademands_id'])) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
    //      }
    //
    //      $result = Metademand::constructMetademands($params['metademands_id']);
    //
    //      $response = [];
    //      foreach ($result as $step => $values) {
    //         foreach ($values as $metademands_id => $form) {
    //            $response[] = ['metademands_id'   => $metademands_id,
    //                           'metademands_name' => Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metademands_id),
    //                           'form'             => $form['form'],
    //                           'tasks'            => $form['tasks']];
    //         }
    //      }
    //
    //      return $response;
    //   }

    //   /**
    //    * @param $params
    //    * @param $protocol
    //    *
    //    * @return array
    //    * @throws \GlpitestSQLError
    //    */
    //   static function methodListMetademands($params, $protocol) {
    //
    //      if (isset($params['help'])) {
    //         return ['help' => 'bool,optional'];
    //      }
    //
    //      if (!Session::getLoginUserID()) {
    //         return PluginWebservicesMethodCommon::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
    //      }
    //
    //      $metademands = new self();
    //      $result      = $metademands->listMetademands();
    //
    //      $response = [];
    //
    //      foreach ($result as $key => $val) {
    //         $response[] = ['id' => $key, 'value' => $val];
    //      }
    //
    //      return $response;
    //   }


    /**
     * @param bool $forceview
     * @param array $options
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public function listMetademands($forceview = false, $options = [])
    {
        global $DB;

        $dbu = new DbUtils();
        $params['condition'] = '';

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $meta_data = [];
        if (isset($options['empty_value'])) {
            $meta_data[0] = Dropdown::EMPTY_VALUE;
        }

        if (!empty($type) || $forceview) {

            $query
                = [
                'SELECT' => [$this->getTable().".name",
                    $this->getTable().".id",
                    "glpi_entities.completename AS entities_name"],
                'FROM' => $this->getTable(),
                'INNER JOIN'       => [
                    'glpi_entities' => [
                        'ON' => [
                            $this->getTable() => 'entities_id',
                            'glpi_entities'          => 'id'
                        ]
                    ]
                ],
                'WHERE' => [$this->getTable().".is_active" => 1,
                    $this->getTable().".is_deleted" => 0,
                    $this->getTable().".is_template" => 0],
                'ORDERBY' => $this->getTable().".name"
            ];

            $type = \Ticket::DEMAND_TYPE;
            if (isset($options['type'])) {
                $type = $options['type'];
            }
            if ($type == \Ticket::INCIDENT_TYPE || $type == \Ticket::DEMAND_TYPE) {
                $query['WHERE'] = $query['WHERE'] + [$this->getTable() . ".type" => $type];
            } else {
                $query['WHERE'] = $query['WHERE'] + [$this->getTable() . ".object_to_create" => $type];
            }

            $query['WHERE'] = $query['WHERE'] + getEntitiesRestrictCriteria(
                    $this->getTable()
                );

            $iterator = $DB->request($query);

            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
                    if ($this->canCreate() || Group::isUserHaveRight($data['id'])) {
                        if (!$dbu->countElementsInTable(
                            "glpi_plugin_metademands_metademands_resources",
                            ["plugin_metademands_metademands_id" => $data['id']]
                        )) {
                            if (empty($name = self::displayField($data['id'], 'name'))) {
                                $name = $data['name'];
                            }
                            $meta_data[$data['id']] = $name . ' (' . $data['entities_name'] . ')';
                        }
                    }
                }
            }
        }

        return $meta_data;
    }

    public function listMetademandsForDraft($options)
    {
        global $DB;

        $meta_data = [];

        if (isset($options['empty_value'])) {
            $meta_data[0] = Dropdown::EMPTY_VALUE;
        }

        $itil_cat = [];

        $query_cat = [
            'SELECT' => ["name", "id"],
            'FROM' => "glpi_itilcategories"
        ];

        $iterator_cat = $DB->request($query_cat);

        if (count($iterator_cat) > 0) {
            foreach ($iterator_cat as $data) {
                $itil_cat[$data['id']] = [
                    "id" => $data['id'],
                    "name" => $data['name'],
                ];
            }
        }

        $query
            = [
            'SELECT' => [$this->getTable().".name",
                $this->getTable().".id",
                $this->getTable().".itilcategories_id",
                "glpi_entities.completename AS entities_name"],
            'FROM' => $this->getTable(),
            'INNER JOIN'       => [
                'glpi_entities' => [
                    'ON' => [
                        $this->getTable() => 'entities_id',
                        'glpi_entities'          => 'id'
                    ]
                ]
            ],
            'WHERE' => [$this->getTable().".is_active" => 1,
                $this->getTable().".is_deleted" => 0,
                $this->getTable().".is_template" => 0],
            'ORDERBY' => $this->getTable().".name"
        ];


        $query['WHERE'] = $query['WHERE'] + getEntitiesRestrictCriteria(
                $this->getTable()
            );

        $iterator = $DB->request($query);

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                if ($this->canCreate() || Group::isUserHaveRight($data['id'])) {
                    $name = $data['name'];

                    //clean string
                    $data['itilcategories_id'] = json_decode($data['itilcategories_id']);

                    if (is_array($data['itilcategories_id']) && count($data['itilcategories_id']) > 1) {
                        foreach ($data['itilcategories_id'] as $datum) {
                            $meta_data[$data['id']][] = [
                                'name' => $name . ' (' . $data['entities_name'] . ')' . ' - ' . $itil_cat[$datum]['name'],
                                'itilcategory' => $itil_cat[$datum]['id'],
                            ];
                        }
                    } else {
                        $meta_data[$data['id']]['name'] = $name . ' (' . $data['entities_name'] . ')';
                        if (isset($data['itilcategories_id'][0])) {
                            $meta_data[$data['id']]['itilcategory'] = $data['itilcategories_id'][0];
                        } else {
                            $meta_data[$data['id']]['itilcategory'] = 0;
                        }
                    }
                }
            }
        }

        return $meta_data;
    }

    /**
     * Get all datas for a metademand
     * @param       $metademands_id
     * @param array $forms
     * @param int $step
     *
     * @return array $form[$step][$metademands_id] then 2 keys :
     * 'form' => Field->find(), pour chacun ajout des options associé dans une clé 'options' (avec clé = check_value et valeur = le reste)
     * 'tasks' => array,Task->getTasks()
     * @throws \GlpitestSQLError
     */
    public static function constructMetademands($metademands_id, $forms = [], $step = self::STEP_SHOW)
    {
        $metademands = new self();
        $metademands->getFromDB($metademands_id);

        $hidden = false;
        if (isset($_SESSION['metademands_hide'])) {
            $hidden = in_array($metademands_id, $_SESSION['metademands_hide']);
        }

        if (!empty($metademands_id) && !$hidden) {
            // get normal form data
            $field = new Field();
            $fields_data = $field->find(
                ['plugin_metademands_metademands_id' => $metademands_id],
                ['rank', 'order']
            );

            // Construct array
            $forms[$step][$metademands_id]['form'] = [];
            $forms[$step][$metademands_id]['tasks'] = [];

            if (count($fields_data)) {
                //TODO add array options
                foreach ($fields_data as $id => $field_data) {
                    $fieldparameter = new FieldParameter();
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field_data["id"]])) {
                        $fields_data[$id]['informations_to_display'] = $fieldparameter->fields['informations_to_display'];
                        if ($fieldparameter->fields['link_to_user']) {
                            continue;
                        }
                    }

                    $fieldopt = new FieldOption();
                    if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $id])) {
                        foreach ($opts as $opt) {
                            $check_value = $opt["check_value"];
                            if ($id > 0) {
                                $fields_data[$id]["options"][$check_value]['plugin_metademands_tasks_id'][] = $opt['plugin_metademands_tasks_id'] ?? 0;
                                $fields_data[$id]["options"][$check_value]['fields_link'][] = $opt['fields_link'] ?? 0;
                                $fields_data[$id]["options"][$check_value]['hidden_link'][] = $opt['hidden_link'] ?? 0;
                                $fields_data[$id]["options"][$check_value]['hidden_block'][] = $opt['hidden_block'] ?? 0;
                                $fields_data[$id]["options"][$check_value]['users_id_validate'] = isset($opt['users_id_validate']) && $opt['users_id_validate'] > 0 ? $opt['users_id_validate'] : ($fields_data[$id]["options"][$check_value]['users_id_validate'] ?? 0);
                                $fields_data[$id]["options"][$check_value]['childs_blocks'] = isset($opt['childs_blocks']) && $opt['childs_blocks'] != '[]' ? $opt['childs_blocks'] : (isset($fields_data[$id]["options"][$check_value]['childs_blocks']) && $fields_data[$id]["options"][$check_value]['childs_blocks'] != '[]' ? $fields_data[$id]["options"][$check_value]['childs_blocks'] : $opt['childs_blocks']);
                                $fields_data[$id]["options"][$check_value]['checkbox_value'] = isset($opt['checkbox_value']) && $opt['checkbox_value'] > 0 ? $opt['checkbox_value'] : ($fields_data[$id]["options"][$check_value]['checkbox_value'] ?? 0);
                                $fields_data[$id]["options"][$check_value]['checkbox_id'] = isset($opt['checkbox_id']) && $opt['checkbox_id'] > 0 ? $opt['checkbox_id'] : ($fields_data[$id]["options"][$check_value]['checkbox_id'] ?? 0);
                                $fields_data[$id]["options"][$check_value]['parent_field_id'] = isset($opt['parent_field_id']) && $opt['parent_field_id'] > 0 ? $opt['parent_field_id'] : ($fields_data[$id]["options"][$check_value]['parent_field_id'] ?? 0);
                            }
                            //                            $fields_data[$id]["options"][$opt["check_value"]][] = $opt;
                        }
                    }
                }

                $forms[$step][$metademands_id]['form'] = $fields_data;
            }

            // Task only for demands
            if (isset($metademands->fields['type'])) {
                if (isset($metademands->fields['force_create_tasks'])
                    && $metademands->fields['force_create_tasks'] > 0) {
                    $tasks = new Task();
                    $tasks_data = $tasks->getTasks(
                        $metademands_id,
                        [
                            'condition' => [
                                'glpi_plugin_metademands_tasks.type' => [
                                    Task::TASK_TYPE,
                                    Task::MAIL_TYPE,
                                ],
                            ],
                        ]
                    );

                    $forms[$step][$metademands_id]['tasks'] = $tasks_data;
                } else {
                    $tasks = new Task();
                    $tasks_data = $tasks->getTasks(
                        $metademands_id,
                        [
                            'condition' => [
                                'glpi_plugin_metademands_tasks.type' => [
                                    Task::TICKET_TYPE,
                                    Task::MAIL_TYPE,
                                ],
                            ],
                        ]
                    );

                    $forms[$step][$metademands_id]['tasks'] = $tasks_data;
                }
            }

            // Check if task are metademands, if some found : recursive call
            //            if (isset($metademands->fields['type'])) {
            //                $query = "SELECT `glpi_plugin_metademands_metademandtasks`.`plugin_metademands_metademands_id` AS link_metademands_id
            //                        FROM `glpi_plugin_metademands_tasks`
            //                        RIGHT JOIN `glpi_plugin_metademands_metademandtasks`
            //                          ON (`glpi_plugin_metademands_metademandtasks`.`plugin_metademands_tasks_id` = `glpi_plugin_metademands_tasks`.`id`)
            //                        WHERE `glpi_plugin_metademands_tasks`.`plugin_metademands_metademands_id` = " . $metademands_id;
            //                $result = $DB->query($query);
            //                if ($DB->numrows($result)) {
            //                    while ($data = $DB->fetchAssoc($result)) {
            //                        $step++;
            //                        $forms = self::constructMetademands($data['link_metademands_id'], $forms, $step);
            //                    }
            //                }
            //            }
        }
        return $forms;
    }

    /**
     * @param $ticket
     * @param $metademands_id
     *
     * @throws \GlpitestSQLError
     */
    //    public function convertMetademandToTicket($ticket, $metademands_id)
    //    {
    //        $tickets_id = $ticket->input["id"];
    //        $oldlanguage = $_SESSION['glpilanguage'];
    //        $ticket_task = new Ticket_Task();
    //        $ticket_metademand = new Ticket_Metademand();
    //        $ticket_field = new Ticket_Field();
    //        $ticket_ticket = new Ticket_Ticket();
    //
    //
    //        // Try to convert name
    //        $ticket->input["name"] = addslashes(
    //            str_replace(
    //                self::$PARENT_PREFIX .
    //                Dropdown::getDropdownName($this->getTable(), $metademands_id) . '&nbsp;:&nbsp;',
    //                '',
    //                $ticket->fields["name"]
    //            )
    //        );
    //        if ($ticket->input["name"] == $ticket->fields["name"]) {
    //            $ticket->input["name"] = addslashes(str_replace(self::$PARENT_PREFIX, '', $ticket->fields["name"]));
    //        }
    //
    //        // Delete metademand linked to the ticket
    //        $ticket_metademand->deleteByCriteria(['tickets_id' => $tickets_id]);
    //        $ticket_field->deleteByCriteria(['tickets_id' => $tickets_id]);
    //        $ticket_ticket->deleteByCriteria(['tickets_id_1' => $tickets_id]);
    //
    //        // For each sons tickets linked to metademand
    //        $tickets_found = Ticket::getSonTickets($tickets_id, $metademands_id, [], true);
    //        foreach ($tickets_found as $value) {
    //            // If son is a metademand : recursive call
    //            if (isset($value['metademands_id'])) {
    //                $son_metademands_ticket = new Ticket();
    //                $son_metademands_ticket->getFromDB($value['tickets_id']);
    //                //TODO To translate ?
    //                $son_metademands_ticket->input = $son_metademands_ticket->fields;
    //                $this->convertMetademandToTicket($son_metademands_ticket, $value['metademands_id']);
    //                $son_metademands_ticket->fields["name"] = addslashes(
    //                    str_replace(self::$PARENT_PREFIX, '', $ticket->input["name"])
    //                );
    //                $son_metademands_ticket->updateInDB(['name']);
    //            } elseif (!empty($value['tickets_id'])) {
    //                // Try to convert name
    //                $son_ticket = new Ticket();
    //                $son_ticket->getFromDB($value['tickets_id']);
    //                //TODO To translate ?
    //                $son_ticket->fields["name"] = addslashes(
    //                    str_replace(self::$SON_PREFIX, '', $son_ticket->fields["name"])
    //                );
    //                $son_ticket->updateInDB(['name']);
    //
    //                // Delete links
    //                $ticket_task->deleteByCriteria(['tickets_id' => $value['tickets_id']]);
    //                $ticket_metademand->deleteByCriteria(['tickets_id' => $value['tickets_id']]);
    //                $ticket_field->deleteByCriteria(['tickets_id' => $value['tickets_id']]);
    //                $ticket_ticket->deleteByCriteria(['tickets_id_1' => $value['tickets_id']]);
    //            }
    //        }
    //    }

    /**
     * @param       $metademands_id
     * @param       $values
     * @param array $options
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public static function addObjects($metademands_id, $values, $options = [])
    {
        global $PLUGIN_HOOKS;
        $tasklevel = 1;

        $metademands_data = self::constructMetademands($metademands_id);
        // filter out hidden fields/blocs
        if (count($metademands_data)) {
            $hiddenBlocs = [];
            $hiddenFields = [];
            $blocs = [];
            // order things to be able to implement the logic which will determine wether the options of a field are taken into account or not
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    if (count($line['form'])) {
                        // order fields
                        foreach ($line['form'] as $field) {
                            $blocs[$field['rank']][$field['order']] = $field;
                        }
                    }
                }
            }
            // use options to determine which blocs and fields are hidden
            //            foreach($blocs as $bloc => $fields) {
            //                if (!in_array($bloc, $hiddenBlocs)) {
            //                    foreach($fields as $field) {
            //                        if (!in_array($field, $hiddenFields)) {
            //                            if (isset($field['options']) && count($field['options'])) {
            //                                foreach($field['options'] as $value => $option) {
            //                                    // get value for the field
            //                                    $formValue = $values['fields'][$field['id']] ?? null;
            //                                    if ($formValue === null) {
            //                                        // itilcategory case
            //                                        if ($field['type'] == 'dropdown_meta' && $field['item'] == 'ITILCategory_Metademands') {
            //                                            $formValue = $values['field_plugin_servicecatalog_itilcategories_id'];
            //                                        }
            //                                    }
            //                                    // if condition of the option isn't met, add is related field and bloc to the hidden lists
            //                                    if (!self::compareValueToOption(
            //                                        $value,
            //                                        $field,
            //                                        $option,
            //                                        $formValue
            //                                    )) {
            //                                        if ($option['hidden_link']) {
            //                                            if (!in_array($option['hidden_link'], $hiddenFields)) {
            //                                                $hiddenFields[] = $option['hidden_link'];
            //                                            }
            //                                        }
            //                                        if ($option['hidden_block']) {
            //                                            if (!in_array($option['hidden_block'], $hiddenBlocs)) {
            //                                                $hiddenBlocs[] = $option['hidden_block'];
            //                                            }
            //                                        }
            //                                    }
            //                                }
            //                            }
            //                        }
            //                    }
            //                }
            //            }
            // unset value of all hidden fields
            foreach ($metademands_data as $form_step => $data) {
                foreach ($data as $form_metademands_id => $line) {
                    if (count($line['form'])) {
                        foreach ($line['form'] as $field) {
                            if (in_array($field['id'], $hiddenFields) || in_array($field['rank'], $hiddenBlocs)) {
                                unset($values['fields'][$field['id']]);
                            }
                        }
                    }
                }
            }
        }

        $metademand_initial = new self();
        $metademand_initial->getFromDB($metademands_id);

        if (!$metademand_initial->fields['object_to_create']
            || !getItemForItemtype($metademand_initial->fields['object_to_create'])) {
            return false;
        }
        $object_class = $metademand_initial->fields['object_to_create'];
        $object = new $object_class();

        $ticket_metademand = new Ticket_Metademand();
        $ticket_field = new Ticket_Field();
        $ticket_ticket = new Ticket_Ticket();
        $KO = [];
        $ancestor_tickets_id = 0;
        $ticket_exists_array = [];
        $config = Config::getInstance();

        unset($values['freetables']);

        $itilcategory = 0;
        if (isset($values['field_plugin_servicecatalog_itilcategories_id'])) {
            $itilcategory = $values['field_plugin_servicecatalog_itilcategories_id'];
        }

        if (count($metademands_data)) {
            foreach ($metademands_data as $form_step => $data) {
                $docitem = null;
                foreach ($data as $form_metademands_id => $line) {
                    //                    if ($object_class == 'Ticket') {
                    //                        $noChild = false;
                    //                        if ($ancestor_tickets_id > 0) {
                    //                            // Skip ticket creation if not allowed by metademand form
                    //                            $metademandtasks_tasks_ids = MetademandTask::getMetademandTask_TaskId($form_metademands_id);
                    //                            //                  foreach ($metademandtasks_tasks_ids as $metademandtasks_tasks_id) {
                    //                            if (!Ticket_Field::checkTicketCreation($metademandtasks_tasks_ids, $ancestor_tickets_id)) {
                    //                                $noChild = true;
                    //                            }
                    //                            //                  }
                    //                        } else {
                    //                            $values['fields']['tickets_id'] = 0;
                    //                        }
                    //                        if ($noChild) {
                    //                            continue;
                    //                        }
                    //                    }
                    $metademand = new self();
                    $metademand->getFromDB($form_metademands_id);

                    // Create parent ticket
                    // Get form fields
                    //                    $parent_fields['content'] = '';

                    if ($metademand->fields['is_order'] == 0) {
                        if (count($line['form'])
                            && isset($values['fields'])) {
                            $forms_id = 0;
                            if (isset($_SESSION['plugin_metademands'][$form_metademands_id]['form_to_compare'])) {
                                $forms_id = $_SESSION['plugin_metademands'][$form_metademands_id]['form_to_compare'];
                            } elseif (isset($values['plugin_metademands_forms_id'])) {
                                $forms_id = $values['plugin_metademands_forms_id'];
                            }
                            //                            if ($config['show_form_changes'] && $forms_id > 0) {
                            //                                foreach ($values['fields'] as $idField => $valueField) {
                            //                                    $diffRemove = "";
                            //                                    $oldFormValues = new Form_Value();
                            //                                    if ($oldFormValues->getFromDBByCrit([
                            //                                        'plugin_metademands_forms_id' => $forms_id,
                            //                                        'plugin_metademands_fields_id' => $idField,
                            //                                    ])) {
                            //                                        $jsonDecode = json_decode($oldFormValues->getField('value'), true);
                            //                                        if (is_array($jsonDecode)) {
                            //                                            if (empty($valueField)) {
                            //                                                $valueField = [];
                            //                                            }
                            //                                            $diffAdd = array_diff($valueField, $jsonDecode);
                            //                                            $diffRemove = array_diff($jsonDecode, $valueField);
                            //                                        } elseif (is_array($oldFormValues->getField('value'))) {
                            //                                            if (empty($valueField)) {
                            //                                                $valueField = [];
                            //                                            }
                            //                                            $diffRemove = array_diff($oldFormValues->getField('value'), $valueField);
                            //                                            $diffAdd = array_diff($valueField, $oldFormValues->getField('value'));
                            //                                        } elseif ($oldFormValues->getField('value') != $valueField) {
                            //                                            $values['fields'][$idField . '#orange'] = $valueField;
                            //                                        }
                            //                                        if ($oldFormValues->getField('value') == $valueField ||
                            //                                            (isset($diffRemove) && empty($diffRemove) && empty($diffAdd))) {
                            //                                            unset($values['fields'][$idField]);
                            //                                        } else {
                            //                                            if (isset($diffRemove) && !empty($diffRemove)) {
                            //                                                if (!empty($diffAdd)) {
                            //                                                    $values['fields'][$idField . '#green'] = $diffAdd;
                            //                                                }
                            //                                                $values['fields'][$idField . '#red'] = $diffRemove;
                            //                                            } elseif (!isset($values['fields'][$idField . '#orange'])) {
                            //                                                $values['fields'][$idField . '#green'] = $valueField;
                            //                                            }
                            //                                        }
                            //                                    }
                            //                                }
                            //                            }
                            unset($_SESSION['plugin_metademands'][$form_metademands_id]['form_to_compare']);
                            $values_form[0] = $values['fields'];
                            $parent_fields = self::formatFields($line['form'], $metademands_id, $values_form, $options);

                        }
                    } elseif ($metademand->fields['is_order'] == 1) {
                        $options['is_order'] = true;
                        if ($metademand->fields['create_one_ticket'] == 0) {
                            //create one ticket for each basket
                            $values_form[0] = $values['basket'] ?? [];
                            foreach ($values_form[0] as $id => $value) {
                                if (isset($line['form'][$id]['item'])
                                    && $line['form'][$id]['item'] == "ITILCategory_Metademands") {
                                    $itilcategory = $value;
                                }
                            }
                        } else {
                            //create one ticket for all basket
                            $values_form = $values['basket'] ?? [];
                            foreach ($values_form as $id => $value) {
                                if (isset($line['form'][$id]['item'])
                                    && $line['form'][$id]['item'] == "ITILCategory_Metademands") {
                                    $itilcategory = $value;
                                }
                            }
                        }

                        $parent_fields = self::formatFields($line['form'], $metademands_id, $values_form, $options);

                    }


                    foreach ($values['fields'] as $id => $datav) {
                        $metademands_fields = new Field();
                        if (strpos($id, '-2')) {
                            $id = str_replace("-2", "", $id);
                        }
                        if ($metademands_fields->getFromDB($id)) {
                            switch ($metademands_fields->fields['item']) {
                                case 'ITILCategory_Metademands':
                                    $parent_fields['itilcategories_id'] = $datav;
                                    if ($itilcategory > 0) {
                                        $parent_fields['itilcategories_id'] = $itilcategory;
                                    }
                                    break;
                            }

                            $fieldopt = new FieldOption();
                            if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $id])) {
                                foreach ($opts as $opt) {
                                    if (isset($opt['users_id_validate'])
                                        && !empty($opt['users_id_validate'])) {
                                        if (!is_array($datav)) {
                                            $datav = [$datav];
                                        }

                                        if (isset($opt['check_value'])
                                            && is_array($datav)
                                            && $opt['users_id_validate'] > 0) {
                                            $checkValue = $opt['check_value'];
                                            $usersValidate = $opt['users_id_validate'];
                                            if (in_array($checkValue, $datav)) {
                                                $add_validation = '0';
                                                $validatortype = 'user';
                                                $users_id_validate[] = $usersValidate;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (empty($n = self::displayField($form_metademands_id, 'name'))) {
                        $n =  $metademand->getName();
                    }

                    $parent_fields['name'] = self::$PARENT_PREFIX
                        . $n;

                    if ($object_class == 'Ticket') {
                        $parent_fields['type'] = $metademand->fields['type'];
                        // Existing tickets id field
                        if (isset($values['fields']['tickets_id'])) {
                            $parent_fields['id'] = $values['fields']['tickets_id'];
                        }
                    }
                    $parent_fields['entities_id'] = $_SESSION['glpiactive_entity'];

                    $parent_fields['status'] = CommonITILObject::INCOMING;

                    // Resources id
                    if (!empty($options['resources_id'])) {
                        $parent_fields['items_id'] = [Resource::class => [$options['resources_id']]];
                    }

                    // Requester user field

                    $parent_fields['_users_id_requester'] = [];
                    $parent_fields['_users_id_observer'] = [];

                    //Add all form contributors as ticket requester is using step by step
                    $configstep = new Configstep();
                    if ($configstep->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademand->getID()])) {
                        if ($configstep->fields['add_user_as_requester']) {
                            $stepformActor = new Stepform_Actor();
                            if (isset($values['plugin_metademands_stepforms_id'])) {
                                $stepformActors = $stepformActor->find(
                                    ['plugin_metademands_stepforms_id' => $values['plugin_metademands_stepforms_id']]
                                );
                                foreach ($stepformActors as $actor) {
                                    $parent_fields['_users_id_requester'][] = $actor['users_id'];
                                }
                            }
                        }
                    }
                    if (count($parent_fields['_users_id_requester']) == 0) {
                        if (isset($values['fields']['_users_id_requester'])) {
                            $parent_fields['_users_id_requester'][] = $values['fields']['_users_id_requester'];
                            if ($values['fields']['_users_id_requester'] != Session::getLoginUserID()) {
                                $parent_fields['_users_id_observer'][] = Session::getLoginUserID();
                            }
                        }
                    }

                    // Get predefined ticket fields
                    //TODO Add check if metademand fields linked to a ticket field with used_by_ticket ?
                    $parent_ticketfields = [];
                    $parent_ticketfields = self::formatTicketFields(
                        $form_metademands_id,
                        $itilcategory,
                        $values,
                        $parent_fields['_users_id_requester'],
                        $parent_fields['entities_id']
                    );
                    $list_fields = $line['form'];


                    $searchOption = Search::getOptions($object_class);
                    foreach ($list_fields as $id => $fields_values) {
                        $metafield = new Field();
                        if ($metafield->getFromDB($id)) {
                            $params = Field::getAllParamsFromField($metafield);
                        }
                        $fields_values = array_merge($fields_values, $params);

                        // ignore used_by_ticket when used to autofill text field
                        if ($fields_values['used_by_ticket'] > 0
                            && !(($fields_values['type'] == 'text'
                                    || $fields_values['type'] == 'tel'
                                    || $fields_values['type'] == 'email')
                                && $fields_values['link_to_user'] > 0)) {
                            foreach ($values_form as $k => $v) {
                                if (isset($v[$id])) {
                                    $name = $searchOption[$fields_values['used_by_ticket']]['linkfield'] ?? "";

                                    if ($v[$id] > 0 && $fields_values['used_by_ticket'] == 4) {
                                        $name = "_users_id_requester";
                                        unset($parent_fields[$name]);
                                        if (is_array($v[$id])) {
                                            foreach ($v[$id] as $usr) {
                                                $parent_fields[$name][] = $usr;
                                            }
                                        } else {
                                            $parent_fields[$name][] = $v[$id];
                                        }
                                    }
                                    if ($fields_values['used_by_ticket'] == 71) {
                                        $name = "_groups_id_requester";
                                        if (is_array($v[$id])) {
                                            foreach ($v[$id] as $usr) {
                                                $parent_fields[$name][] = $usr;
                                            }
                                        } else {
                                            $parent_fields[$name][] = $v[$id];
                                        }
                                    }
                                    if ($fields_values['used_by_ticket'] == 66) {
                                        $name = "_users_id_observer";
                                        if (is_array($v[$id])) {
                                            foreach ($v[$id] as $usr) {
                                                $parent_fields[$name][] = $usr;
                                            }
                                        } else {
                                            $parent_fields[$name][] = $v[$id];
                                        }
                                    }
                                    if ($fields_values['used_by_ticket'] == 65) {
                                        $name = "_groups_id_observer";
                                        if (is_array($v[$id])) {
                                            foreach ($v[$id] as $usr) {
                                                $parent_fields[$name][] = $usr;
                                            }
                                        } else {
                                            $parent_fields[$name][] = $v[$id];
                                        }
                                    }
                                    if ($fields_values['used_by_ticket'] != 4
                                        && $fields_values['used_by_ticket'] != 71
                                        && $fields_values['used_by_ticket'] != 66
                                        && $fields_values['used_by_ticket'] != 65) {
                                        $parent_fields[$name] = $v[$id];
                                        $parent_ticketfields[$name] = $v[$id];
                                    }

                                    if ($fields_values['used_by_ticket'] == 59) {
                                        $parent_fields["_add_validation"] = '0';
                                        $parent_ticketfields["_add_validation"] = '0';
                                        $parent_fields["validatortype"] = 'user';
                                        $parent_ticketfields["validatortype"] = 'user';
                                        $parent_fields["users_id_validate"] = [$v[$id]];
                                        $parent_ticketfields["users_id_validate"] = [$v[$id]];
                                    }
                                    if ($fields_values['used_by_ticket'] == 13) {
                                        if ($fields_values['type'] == "dropdown_meta"
                                            && $fields_values["item"] == "mydevices") {
                                            $item = explode('_', $v[$id]);
                                            if (isset($item[0]) && isset($item[1])) {
                                                $parent_fields["items_id"] = [$item[0] => [$item[1]]];
                                            }

                                        }
                                        if ($fields_values['type'] == "dropdown_object"
                                            && \Ticket::isPossibleToAssignType($fields_values["item"])) {
                                            $parent_fields["items_id"] = [$fields_values["item"] => [$v[$id]]];
                                        }
                                        if ($fields_values['type'] == "dropdown_multiple"
//                                            && \Ticket::isPossibleToAssignType("Appliance")
                                            && $fields_values["item"] == "Appliance") {
                                            foreach ($v[$id] as $key => $items_id) {
                                                $parent_fields["items_id"] = ['Appliance' => [$items_id]];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (count($parent_fields['_users_id_requester']) == 0) {
                        if (isset($values['fields']['_users_id_requester'])) {
                            $parent_fields['_users_id_requester'][] = $values['fields']['_users_id_requester'];
                            if ($values['fields']['_users_id_requester'] != Session::getLoginUserID()) {
                                $parent_fields['_users_id_observer'][] = Session::getLoginUserID();
                            }
                        }
                    }

                    // If requester is different of connected user : Force his requester group on ticket
                    //TODO Add options ?
                    //               if (isset($parent_fields['_users_id_requester'])
                    //                   && $parent_fields['_users_id_requester'] != Session::getLoginUserID()) {
                    //                  $query  = "SELECT `glpi_groups`.`id` AS _groups_id_requester
                    //                           FROM `glpi_groups_users`
                    //                           LEFT JOIN `glpi_groups`
                    //                             ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                    //                           WHERE `glpi_groups_users`.`users_id` = " . $parent_fields['_users_id_requester'] . "
                    //                           AND `glpi_groups`.`is_requester` = 1
                    //                           LIMIT 1";
                    //                  $result = $DB->query($query);
                    //                  if ($DB->numrows($result)) {
                    //                     $groups_id_requester                   = $DB->result($result, 0, '_groups_id_requester');
                    //                     $parent_fields['_groups_id_requester'] = $groups_id_requester;
                    //                  }
                    //               }
                    // Affect requester group to son metademand
                    //               if ($form_metademands_id != $metademands_id) {
                    //                  $groups_id_assign = Ticket::getUsedActors($ancestor_tickets_id,
                    //                                                                             CommonITILActor::ASSIGN,
                    //                                                                             'groups_id');
                    //                  if (count($groups_id_assign)) {
                    //                     $parent_fields['_groups_id_requester'] = $groups_id_assign[0];
                    //                  }
                    //               }
                    //END TODO Add options

                    if (isset($users_id_validate)) {
                        $parent_fields["_add_validation"] = $add_validation;
                        $parent_ticketfields["_add_validation"] = $add_validation;
                        $parent_fields["validatortype"] = $validatortype;
                        $parent_ticketfields["validatortype"] = $validatortype;
                        if (isset($parent_fields["users_id_validate"])) {
                            $parent_fields["users_id_validate"] = array_merge(
                                $parent_fields["users_id_validate"],
                                $users_id_validate
                            );
                            $parent_ticketfields["users_id_validate"] = array_merge(
                                $parent_ticketfields["users_id_validate"],
                                $users_id_validate
                            );
                        } else {
                            $parent_fields["users_id_validate"] = $users_id_validate;
                            $parent_ticketfields["users_id_validate"] = $users_id_validate;
                        }
                    }

                    // Case of update existing ticket with form
                    // Ticket does not exist : ADD
                    $ticket_exists = false;

                    if (empty($parent_fields['id'])) {
                        unset($parent_fields['id']);

                        $input = self::mergeFields($parent_fields, $parent_ticketfields);

                        $input['_filename'] = [];
                        $input['_tag_filename'] = [];

                        if ($metademand->fields['is_order'] == 0) {
                            if (isset($values['fields']['uploaded_files']['_filename'])) {
                                $input['_filename'] = $values['fields']['uploaded_files']['_filename'];
                            }
                            if (isset($values['fields']['uploaded_files']['_prefix_filename'])) {
                                $input['_prefix_filename'] = $values['fields']['uploaded_files']['_prefix_filename'];
                            }
                            if (isset($values['fields']['uploaded_files']['_tag_filename'])) {
                                $input['_tag_filename'] = $values['fields']['uploaded_files']['_tag_filename'];
                            }
                        } else {
                            if (isset($values['fields']['_filename'])) {
                                $input['_filename'] = $values['fields']['_filename'];
                            }
                            if (isset($values['fields']['_prefix_filename'])) {
                                $input['_prefix_filename'] = $values['fields']['_prefix_filename'];
                            }
                            if (isset($values['fields']['_tag_filename'])) {
                                $input['_tag_filename'] = $values['fields']['_tag_filename'];
                            }
                        }

                        if ($itilcategory > 0) {
                            $input['itilcategories_id'] = $itilcategory;
                        } else {
                            $cats = json_decode($metademand->fields['itilcategories_id'], true);
                            if (is_array($cats) && count($cats) == 1) {
                                foreach ($cats as $cat) {
                                    $input['itilcategories_id'] = $cat;
                                }
                            }
                        }
                        $inputFieldMain = [];
                        if (Plugin::isPluginActive('fields')) {
                            $pluginfield = new Pluginfields();
                            $pluginfields = $pluginfield->find(
                                ['plugin_metademands_metademands_id' => $form_metademands_id]
                            );
                            foreach ($pluginfields as $plfield) {
                                $fields_field = new PluginFieldsField();
                                $fields_container = new PluginFieldsContainer();
                                if ($fields_field->getFromDB($plfield['plugin_fields_fields_id'])) {
                                    if ($fields_container->getFromDB(
                                        $fields_field->fields['plugin_fields_containers_id']
                                    )) {
                                        if (isset($values['fields'][$plfield['plugin_metademands_fields_id']])) {
                                            if ($fields_field->fields['type'] === 'dropdown') {
                                                $val_f = 0;
                                                if ($values['fields'][$plfield['plugin_metademands_fields_id']] == "") {
                                                    $values['fields'][$plfield['plugin_metademands_fields_id']] = 0;
                                                }
                                                $className = 'PluginFields' . ucfirst($fields_field->fields['name']) . 'Dropdown';
                                                if (getItemForItemtype($className)) {
                                                    $classf = new $className();
                                                    $valuesf = $classf->find();

                                                    $field_custom = new FieldCustomvalue();
                                                    if ($customs = $field_custom->find(
                                                        ["plugin_metademands_fields_id" => $plfield['plugin_metademands_fields_id']]
                                                    )) {
                                                        if (count($customs) > 0) {
                                                            foreach ($customs as $custom) {
                                                                foreach ($valuesf as $valuef) {
                                                                    if ($custom['name'] == $valuef['name']) {
                                                                        $val_f = $valuef['id'];
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        $val_f = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                    }
                                                }
                                                if ($values['fields'][$plfield['plugin_metademands_fields_id']] > 0) {
                                                    $input["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $val_f;
                                                    $inputFieldMain["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $val_f;
                                                }
                                            } elseif ($fields_field->fields['type'] == 'yesno') {
                                                $input[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                $inputFieldMain[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                            } else {
                                                $input[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                $inputFieldMain[$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($input['items_id'][Resource::class])) {
                            $resource = new Resource();
                            foreach ($input['items_id'][Resource::class] as $resource_id) {
                                if ($resource->getFromDB($resource_id)) {
                                    $input['name'] .= " " . $resource->fields['name'] . " " . $resource->fields['firstname'];
                                }
                            }
                        }

                        if ($input['name'] == 0 || $input['name'] == "0" || empty($input['name'])) {
                            $input['name'] = $metademand->getName();
                        }

                        //                        $input['name'] = Glpi\RichText\RichText::getTextFromHtml($input['name']);

                        if (Plugin::isPluginActive('collectmetademand')) {
                            if (isset($_SESSION['plugin_collectmetademands']['textOrigin'])) {
                                $input['content'] = $_SESSION['plugin_collectmetademands']['textOrigin'] . "<br>" . $input['content'];
                                unset($_SESSION['plugin_collectmetademands']['textOrigin']);
                            }

                            if (isset($options['text_ticket_collectmetademand'])) {
                                $input['content'] = $options['text_ticket_collectmetademand'] . "<br>" . $input['content'];
                            }

                            if (isset($options['collectmetademand_entity'])) {
                                $input['entities_id'] = $options['collectmetademand_entity'];
                            }
                        }

                        // Case of update existing ticket with form
                        if (isset($options['current_ticket_id'])
                            && $options['current_ticket_id'] > 0
                            && !$options['meta_validated']) {
                            $inputUpdate['id'] = $options['current_ticket_id'];
                            $inputUpdate['content'] = $input['content'];
                            $inputUpdate['name'] = $input['name'];
                            $parent_tickets_id = $inputUpdate['id'];
                            $object->update($inputUpdate);
                            $object->getFromDB($inputUpdate['id']);
                            $ticket_exists_array[] = 1;
                        } else {
                            //ADD TICKET / CHANGE / PROBLEM / OTHERS
                            if (empty($input['content'])) {
                                $message = __('There is a problem on object creation', 'metademands');
                                Session::addMessageAfterRedirect($message, false, ERROR);
                                return false;
                            }
                            //                            $input['_actors'] = [];

                            $parent_tickets_id = $object->add($input);
                            self::incrementFormUsageCount($metademand);
                        }

                        //delete drafts
                        if (isset($_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_drafts_id'])) {
                            $draft = new Draft();
                            $draft->deleteByCriteria(
                                ['id' => $_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_drafts_id']]
                            );
                        }
                        //Link object to forms_id
                        if (isset($_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_forms_id'])) {
                            $form = new Form();
                            $form->update([
                                'id' => $_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_forms_id'],
                                'items_id' => $parent_tickets_id,
                                'itemtype' => $object_class,
                            ]);
                            unset($_SESSION['plugin_metademands'][$form_metademands_id]['plugin_metademands_forms_id']);
                        }
                        $inputField = [];
                        if ($parent_tickets_id) {
                            if (Plugin::isPluginActive('fields')) {
                                $inputField = [];
                                $pluginfield = new Pluginfields();
                                $pluginfields = $pluginfield->find(
                                    ['plugin_metademands_metademands_id' => $form_metademands_id]
                                );

                                foreach ($pluginfields as $plfield) {
                                    $fields_field = new PluginFieldsField();
                                    $fields_container = new PluginFieldsContainer();
                                    if ($fields_field->getFromDB($plfield['plugin_fields_fields_id'])) {
                                        if ($fields_container->getFromDB(
                                            $fields_field->fields['plugin_fields_containers_id']
                                        )) {

                                            if (isset($values['fields'][$plfield['plugin_metademands_fields_id']])) {
                                                if ($fields_field->fields['type'] === 'dropdown') {
                                                    $val_f = 0;
                                                    if ($values['fields'][$plfield['plugin_metademands_fields_id']] == "") {
                                                        $values['fields'][$plfield['plugin_metademands_fields_id']] = 0;
                                                    }
                                                    if ($values['fields'][$plfield['plugin_metademands_fields_id']] > 0) {
                                                        $className = 'PluginFields' . ucfirst($fields_field->fields['name']) . 'Dropdown';
                                                        if (getItemForItemtype($className)) {
                                                            $classf = new $className();
                                                            $valuesf = $classf->find();

                                                            $field_custom = new FieldCustomvalue();
                                                            if ($customs = $field_custom->find(
                                                                ["plugin_metademands_fields_id" => $plfield['plugin_metademands_fields_id']]
                                                            )) {
                                                                if (count($customs) > 0) {
                                                                    foreach ($customs as $custom) {
                                                                        foreach ($valuesf as $valuef) {
                                                                            if ($custom['name'] == $valuef['name']) {
                                                                                $val_f = $valuef['id'];
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                $val_f = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                            }
                                                        }
                                                        $inputField[$fields_field->fields['plugin_fields_containers_id']]["plugin_fields_" . $fields_field->fields['name'] . "dropdowns_id"] = $val_f;
                                                    }
                                                } elseif ($fields_field->fields['type'] == 'yesno') {
                                                    $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                } else {
                                                    $inputField[$fields_field->fields['plugin_fields_containers_id']][$fields_field->fields['name']] = $values['fields'][$plfield['plugin_metademands_fields_id']];
                                                }
                                            }
                                        }
                                    }
                                }
                                $cleaninput = [];
                                foreach ($inputField as $c_id => $c_vals) {
                                    foreach ($c_vals as $c_name => $c_val) {
                                        if (!empty($c_val)) {
                                            $cleaninput[$c_id][$c_name] = $c_val;
                                        }
                                    }
                                }
                                foreach ($cleaninput as $containers_id => $vals) {
                                    $container = new PluginFieldsContainer();
                                    $vals['plugin_fields_containers_id'] = $containers_id;
                                    $vals['itemtype'] = $object_class;
                                    $vals['items_id'] = $parent_tickets_id;
                                    $container->updateFieldsValues($vals, $object_class, false);
                                }
                            }
                            //Hook to do action after ticket creation with metademands
                            if (isset($PLUGIN_HOOKS['metademands'])) {
                                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                                    $p = [];
                                    $options["tickets_id"] = $parent_tickets_id;
                                    $p["options"] = $options;
                                    $p["values"] = $values;
                                    $p["line"] = $line;

                                    $new_res = self::getPluginAfterCreateTicket($plug, $p);
                                }
                            }

                            if ($docitem == null && $config['create_pdf']) {
                                //document PDF Generation
                                //TODO TO Tranlate
                                if (empty($n = self::displayField($metademand->getID(), 'name'))) {
                                    $n = $metademand->getName();
                                }

                                $comm = Dropdown::getDropdownName("glpi_entities", $_SESSION['glpiactive_entity']);
                                $docPdf = new MetademandPdf($n, $comm, $parent_tickets_id);
                                if ($metademand->fields['is_order'] == 0) {
                                    $values_form['0'] = $values ?? [];
                                    $docPdf->drawPdf(
                                        $line['form'],
                                        $values_form,
                                        $metademand->getID(),
                                        $parent_tickets_id,
                                        false
                                    );
                                } elseif ($metademand->fields['is_order'] == 1) {
                                    if ($metademand->fields['create_one_ticket'] == 0) {
                                        //create one ticket for each basket
                                        $values_form['0'] = $values ?? [];
                                    } else {
                                        //create one ticket for all basket
                                        $baskets = [];
                                        $values['basket'] ??= [];
                                        foreach ($values['basket'] as $k => $v) {
                                            $baskets[$k]['basket'] = $v;
                                        }

                                        $values_form = $baskets;
                                    }
                                    $docPdf->drawPdf(
                                        $line['form'],
                                        $values_form,
                                        $metademand->getID(),
                                        $parent_tickets_id,
                                        true
                                    );
                                }
                                $docPdf->Close();
                                //TODO TO Tranlate
                                $name = MetademandPdf::cleanTitle($comm . " " . $n);
                                $docitem = $docPdf->addDocument(
                                    $name,
                                    $object_class,
                                    $object->getID(),
                                    $_SESSION['glpiactive_entity']
                                );
                            }
                        }
                        // Ticket already exists
                    } else {
                        if ($object_class == 'Ticket') {
                            $parent_tickets_id = $parent_fields['id'];
                            $object->getFromDB($parent_tickets_id);
                            $parent_fields['content'] = $object->fields['content']
                                . "<br>" . $parent_fields['content'];
                            $parent_fields['name'] = $parent_fields['name']
                                . '&nbsp;:&nbsp;' . $object->fields['name'];
                            $ticket_exists_array[] = 1;
                            $ticket_exists = true;
                            $values['fields']['tickets_id'] = 0;
                        }
                    }

                    //Prevent create subtickets
                    //                    $tasks = [];
                    //                    foreach ($values['fields'] as $key => $field) {
                    //                        $fieldDbtm = new Field();
                    //                        if ($fieldDbtm->getFromDB($key)) {
                    //
                    //                            $check_value = $fieldDbtm->fields['check_value'];
                    //                            $type = $fieldDbtm->fields['type'];
                    //                            $test = Ticket_Field::isCheckValueOK($field, $check_value, $type);
                    //                            $check[] = ($test == false) ? 0 : 1;
                    //                            if (in_array(0, $check)) {
                    //                                $tasks[] .= $fieldDbtm->fields['plugin_metademands_tasks_id'];
                    //                            }
                    //                        }
                    //                    }

                    //                    foreach ($tasks as $k => $task) {
                    //                        unset($line['tasks'][$task]);
                    //                    }

                    if ($parent_tickets_id) {
                        // Create link for metademand task with ancestor metademand
                        if ($form_metademands_id == $metademands_id) {
                            $ancestor_tickets_id = $parent_tickets_id;
                        }

                        if ($object_class == 'Ticket') {
                            // Metademands - ticket relation
                            //TODO Change / problem ?
                            if (!$ticket_metademand->getFromDBByCrit([
                                'tickets_id' => $parent_tickets_id,
                                'parent_tickets_id' => $ancestor_tickets_id,
                                'plugin_metademands_metademands_id' => $form_metademands_id,
                            ])) {
                                $ticket_metademand->add([
                                    'tickets_id' => $parent_tickets_id,
                                    'parent_tickets_id' => $ancestor_tickets_id,
                                    'plugin_metademands_metademands_id' => $form_metademands_id,
                                    'status' => Ticket_Metademand::RUNNING,
                                ]);
                            }

                            // Save all form values of the ticket
                            if (count($line['form']) && isset($values['fields'])) {
                                //TODO Change / problem ?
                                $ticket_field->deleteByCriteria(['tickets_id' => $parent_tickets_id]);
                                $input['_filename'] = [];
                                $input['_tag_filename'] = [];

                                if ($metademand->fields['is_order'] == 0) {
                                    if (isset($values['fields']['uploaded_files']['_filename'])) {
                                        $input['_filename'] = $values['fields']['uploaded_files']['_filename'];
                                    }
                                    if (isset($values['fields']['uploaded_files']['_prefix_filename'])) {
                                        $input['_prefix_filename'] = $values['fields']['uploaded_files']['_prefix_filename'];
                                    }
                                    if (isset($values['fields']['uploaded_files']['_tag_filename'])) {
                                        $input['_tag_filename'] = $values['fields']['uploaded_files']['_tag_filename'];
                                    }
                                } else {
                                    if (isset($values['fields']['_filename'])) {
                                        $input['_filename'] = $values['fields']['_filename'];
                                    }
                                    if (isset($values['fields']['_prefix_filename'])) {
                                        $input['_prefix_filename'] = $values['fields']['_prefix_filename'];
                                    }
                                    if (isset($values['fields']['_tag_filename'])) {
                                        $input['_tag_filename'] = $values['fields']['_tag_filename'];
                                    }
                                }
                                $ticket_field->setTicketFieldsValues(
                                    $line['form'],
                                    $values['fields'],
                                    $parent_tickets_id,
                                    $input
                                );
                            }
                            if (isset($_SESSION['plugin_metademands'][$metademand->getID()]['ancestor_tickets_id'])) {
                                $options['ancestor_tickets_id'] = $_SESSION['plugin_metademands'][$metademand->getID()]['ancestor_tickets_id'];
                            }

                            //case of child metademands for link it
                            if (!empty($options['ancestor_tickets_id'])) {
                                // Add son link to parent
                                $ticket_ticket->add([
                                    'tickets_id_1' => $parent_tickets_id,
                                    'tickets_id_2' => $options['ancestor_tickets_id'],
                                    'link' => Ticket_Ticket::SON_OF,
                                ]);
                                $ancestor_tickets_id = $parent_tickets_id;
                            }
                            if (!empty($ancestor_tickets_id) && $object_class == 'Ticket') {
                                // Add son link to parent
                                $ticket_ticket->add([
                                    'tickets_id_1' => $parent_tickets_id,
                                    'tickets_id_2' => $ancestor_tickets_id,
                                    'link' => Ticket_Ticket::SON_OF,
                                ]);
                                $ancestor_tickets_id = $parent_tickets_id;
                            }
                        }
                        //create tasks (for problem / change)
                        if ($object_class == 'Problem' || $object_class == 'Change') {
                            $meta_tasks = $line['tasks'];
                            if (is_array($meta_tasks)) {
                                foreach ($meta_tasks as $meta_task) {
                                    if (Ticket_Field::checkTicketCreation(
                                        $meta_task['tasks_id'],
                                        $parent_tickets_id
                                    )) {
                                        $input = [];
                                        if ($object_class == 'Problem') {
                                            $task = new ProblemTask();
                                            $input['problems_id'] = $parent_tickets_id;
                                        } else {
                                            $task = new ChangeTask();
                                            $input['changes_id'] = $parent_tickets_id;
                                        }
                                        $input['content'] = $meta_task['tickettasks_name']
                                         . " " . $meta_task['content'];
                                        $input['groups_id_tech'] = $meta_task["groups_id_assign"];
                                        $input['users_id_tech'] = $meta_task["users_id_assign"];
                                        $task->add($input);
                                    }
                                }
                            }
                        }

                        // Create sons tickets
                        if ($object_class == 'Ticket') {
                            if (isset($line['tasks'])
                                && is_array($line['tasks'])
                                && count($line['tasks'])) {
                                //                     $line['tasks'] = $this->checkTaskAllowed($metademands_id, $values, $line['tasks']);

                                if ($metademand->fields["validation_subticket"] == 0) {
                                    $ticket2 = new \Ticket();
                                    $ticket2->getFromDB($parent_tickets_id);
                                    $parent_fields["requesttypes_id"] = $ticket2->fields['requesttypes_id'];
                                    foreach ($line['tasks'] as $key => $l) {
                                        if ($l['type'] != Task::MAIL_TYPE) {
                                            //replace #id# in title with the value
                                            do {
                                                $match = self::getBetween($l['tickettasks_name'], '[', ']');
                                                if (empty($match)) {
                                                    $explodeTitle = [];
                                                    $explodeTitle = explode("#", $l['tickettasks_name']);
                                                    foreach ($explodeTitle as $title) {
                                                        if (isset($values['fields'][$title])) {
                                                            $field = new Field();
                                                            $field->getFromDB($title);
                                                            $fields = $field->fields;


                                                            $fields['value'] = $values['fields'][$title];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            if ($value != null) {
                                                                $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                                    "#" . $title . "#",
                                                                    $value,
                                                                    $line['tasks'][$key]['tickettasks_name']
                                                                );
                                                            }
                                                        } else {
                                                            $explodeTitle2 = explode(".", $title);

                                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                    )) {
                                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                                        $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                                            $explodeTitle2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $title,
                                                                            $line['tasks'][$key]['tickettasks_name']
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                                $title,
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $title,
                                                                $line['tasks'][$key]['tickettasks_name'],
                                                                true
                                                            );
                                                        }
                                                    }
                                                } else {
                                                    $explodeVal = [];
                                                    $explodeVal = explode("|", $match);
                                                    $find = false;
                                                    $val_to_replace = "";
                                                    foreach ($explodeVal as $str) {
                                                        $explodeTitle = explode("#", $str);
                                                        foreach ($explodeTitle as $title) {
                                                            if (isset($values['fields'][$title])) {
                                                                $field = new Field();
                                                                $field->getFromDB($title);
                                                                $fields = $field->fields;


                                                                $fields['value'] = $values['fields'][$title];

                                                                $fields['value2'] = '';
                                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                                }
                                                                $result = [];
                                                                $result['content'] = "";
                                                                $result[$fields['rank']]['content'] = "";
                                                                $result[$fields['rank']]['display'] = false;
                                                                $parent_fields_id = 0;
                                                                $value = self::getContentWithField(
                                                                    [],
                                                                    0,
                                                                    $fields,
                                                                    $result,
                                                                    $parent_fields_id,
                                                                    true
                                                                );
                                                                $str = str_replace("#" . $title . "#", $value, $str);
                                                                if (!is_null($value) && !empty($value)) {
                                                                    $find = true;
                                                                }
                                                            } else {
                                                                $explodeTitle2 = explode(".", $title);

                                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                    $field_object = new Field();
                                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                        )) {
                                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                                            $str = self::getContentForUser(
                                                                                $explodeTitle2[1],
                                                                                $users_id,
                                                                                $_SESSION['glpiactive_entity'],
                                                                                $title,
                                                                                $str
                                                                            );
                                                                        }
                                                                    }
                                                                }
                                                                $users_id = $parent_fields['_users_id_requester'];
                                                                $str = self::getContentForUser(
                                                                    $title,
                                                                    $users_id,
                                                                    $_SESSION['glpiactive_entity'],
                                                                    $title,
                                                                    $str,
                                                                    true
                                                                );
                                                            }
                                                        }
                                                        if ($find == true) {
                                                            break;
                                                        }
                                                    }

                                                    if (str_contains($match, "#")) {
                                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                            "[" . $match . "]",
                                                            $str,
                                                            $line['tasks'][$key]['tickettasks_name']
                                                        );
                                                        $l['tickettasks_name'] = str_replace(
                                                            "[" . $match . "]",
                                                            $str,
                                                            $l['tickettasks_name']
                                                        );
                                                    } else {
                                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                            "[" . $match . "]",
                                                            "<@" . $str . "@>",
                                                            $line['tasks'][$key]['tickettasks_name']
                                                        );
                                                        $l['tickettasks_name'] = str_replace(
                                                            "[" . $match . "]",
                                                            "<@" . $str . "@>",
                                                            $l['tickettasks_name']
                                                        );
                                                    }
                                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                                }
                                            } while (!empty($match));

                                            $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                "<@",
                                                "[",
                                                $line['tasks'][$key]['tickettasks_name']
                                            );
                                            $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                "@>",
                                                "]",
                                                $line['tasks'][$key]['tickettasks_name']
                                            );
                                            $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                                            $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                                            $explodeTitle = explode("#", $l['tickettasks_name']);
                                            foreach ($explodeTitle as $title) {
                                                if (isset($values['fields'][$title])) {
                                                    $field = new Field();
                                                    $field->getFromDB($title);
                                                    $fields = $field->fields;


                                                    $fields['value'] = $values['fields'][$title];

                                                    $fields['value2'] = '';
                                                    if (($fields['type'] == 'date_interval'
                                                            || $fields['type'] == 'datetime_interval')
                                                        && isset($values['fields'][$title . '-2'])) {
                                                        $fields['value2'] = $values['fields'][$title . '-2'];
                                                    }
                                                    $result = [];
                                                    $result['content'] = "";
                                                    $result[$fields['rank']]['content'] = "";
                                                    $result[$fields['rank']]['display'] = false;
                                                    $parent_fields_id = 0;
                                                    $value = self::getContentWithField(
                                                        [],
                                                        0,
                                                        $fields,
                                                        $result,
                                                        $parent_fields_id,
                                                        true
                                                    );
                                                    if ($value != null) {
                                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                            "#" . $title . "#",
                                                            $value,
                                                            $line['tasks'][$key]['tickettasks_name']
                                                        );
                                                    }
                                                } else {
                                                    $explodeTitle2 = explode(".", $title);

                                                    if (isset($values['fields'][$explodeTitle2[0]])) {
                                                        $field_object = new Field();
                                                        if ($field_object->getFromDB($explodeTitle2[0])) {
                                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                            )) {
                                                                $users_id = $values['fields'][$explodeTitle2[0]];
                                                                $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                                    $explodeTitle2[1],
                                                                    $users_id,
                                                                    $_SESSION['glpiactive_entity'],
                                                                    $title,
                                                                    $line['tasks'][$key]['tickettasks_name']
                                                                );
                                                            }
                                                        }
                                                    }
                                                    $users_id = $parent_fields['_users_id_requester'];
                                                    $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                        $title,
                                                        $users_id,
                                                        $_SESSION['glpiactive_entity'],
                                                        $title,
                                                        $line['tasks'][$key]['tickettasks_name'],
                                                        true
                                                    );
                                                }
                                            }


                                            //replace #id# in content with the value
                                            do {
                                                $match = self::getBetween($l['content'], '[', ']');
                                                if (empty($match) && $l['content'] != null) {
                                                    //TODO all $l['content'];
                                                    $l['content'] = RichText::getTextFromHtml(
                                                        $l['content']
                                                    );

                                                    $explodeContent = explode("#", $l['content']);
                                                    foreach ($explodeContent as $content) {
                                                        //                                                        $field_object = new Field();
                                                        //                                                        if ($field_object->getFromDB($content)) {
                                                        //                                                            if ($field_object->fields['type'] == "informations") {
                                                        //                                                                $values['fields'][$content] = $field_object->fields['label2'];
                                                        //                                                            }
                                                        //                                                        }

                                                        if (isset($values['fields'][$content])) {
                                                            $field = new Field();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;


                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                                                && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;

                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }
                                                            if ($value != null) {
                                                                $line['tasks'][$key]['content'] = str_replace(
                                                                    "#" . $content . "#",
                                                                    $value,
                                                                    $line['tasks'][$key]['content']
                                                                );
                                                            }
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                    )) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                            $explodeContent2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $content,
                                                                            $line['tasks'][$key]['content']
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                $content,
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $content,
                                                                $line['tasks'][$key]['content'],
                                                                true
                                                            );
                                                        }
                                                    }
                                                } else {
                                                    $explodeVal = [];
                                                    $explodeVal = explode("|", $match);
                                                    $find = false;
                                                    $val_to_replace = "";
                                                    foreach ($explodeVal as $str) {
                                                        $explodeContent = explode("#", $str);
                                                        foreach ($explodeContent as $content) {
                                                            if (isset($values['fields'][$content])) {
                                                                $field = new Field();
                                                                $field->getFromDB($content);
                                                                $fields = $field->fields;


                                                                $fields['value'] = $values['fields'][$content];

                                                                $fields['value2'] = '';
                                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                    $fields['value2'] = $values['fields'][$content . '-2'];
                                                                }
                                                                $result = [];
                                                                $result['content'] = "";
                                                                $result[$fields['rank']]['content'] = "";
                                                                $result[$fields['rank']]['display'] = false;
                                                                $parent_fields_id = 0;
                                                                $value = self::getContentWithField(
                                                                    [],
                                                                    0,
                                                                    $fields,
                                                                    $result,
                                                                    $parent_fields_id,
                                                                    true
                                                                );
                                                                if ($fields['type'] == "textarea") {
                                                                    if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                        $value = str_replace("\\n", '","', $value);
                                                                    }
                                                                }
                                                                $str = str_replace("#" . $content . "#", $value, $str);
                                                                if (!is_null($value) && !empty($value)) {
                                                                    $find = true;
                                                                }
                                                            } else {
                                                                $explodeContent2 = explode(".", $content);

                                                                if (isset($values['fields'][$explodeContent2[0]])) {
                                                                    $field_object = new Field();
                                                                    if ($field_object->getFromDB($explodeContent2[0])) {
                                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                        )) {
                                                                            $users_id = $values['fields'][$explodeContent2[0]];
                                                                            $str = self::getContentForUser(
                                                                                $explodeContent2[1],
                                                                                $users_id,
                                                                                $_SESSION['glpiactive_entity'],
                                                                                $content,
                                                                                $str
                                                                            );
                                                                        }
                                                                    }
                                                                }
                                                                $users_id = $parent_fields['_users_id_requester'];
                                                                $str = self::getContentForUser(
                                                                    $content,
                                                                    $users_id,
                                                                    $_SESSION['glpiactive_entity'],
                                                                    $content,
                                                                    $str,
                                                                    true
                                                                );
                                                            }
                                                        }
                                                        if ($find == true) {
                                                            break;
                                                        }
                                                    }
                                                    //                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                                    if (str_contains($match, "#")) {
                                                        $line['tasks'][$key]['content'] = str_replace(
                                                            "[" . $match . "]",
                                                            $str,
                                                            $line['tasks'][$key]['content']
                                                        );
                                                        $l['content'] = str_replace(
                                                            "[" . $match . "]",
                                                            $str,
                                                            $l['content']
                                                        );
                                                    } else {
                                                        if ($line['tasks'][$key]['content'] != null) {
                                                            $line['tasks'][$key]['content'] = str_replace(
                                                                "[" . $match . "]",
                                                                "<@" . $str . "@>",
                                                                $line['tasks'][$key]['content']
                                                            );
                                                        }
                                                        if ($l['content'] != null) {
                                                            $l['content'] = str_replace(
                                                                "[" . $match . "]",
                                                                "<@" . $str . "@>",
                                                                $l['content']
                                                            );
                                                        }
                                                    }
                                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                                }
                                            } while (!empty($match));

                                            if ($line['tasks'][$key]['content'] != null) {
                                                $line['tasks'][$key]['content'] = str_replace(
                                                    "<@",
                                                    "[",
                                                    $line['tasks'][$key]['content']
                                                );
                                                $line['tasks'][$key]['content'] = str_replace(
                                                    "@>",
                                                    "]",
                                                    $line['tasks'][$key]['content']
                                                );
                                            }
                                            if ($l['content'] != null) {
                                                $l['content'] = str_replace("<@", "[", $l['content']);
                                                $l['content'] = str_replace("@>", "]", $l['content']);
                                            }
                                            if ($l['content'] != null) {
                                                $l['content'] = RichText::getTextFromHtml($l['content']);
                                                $explodeContent = explode("#", $l['content']);
                                                foreach ($explodeContent as $content) {
                                                    //                                                    $field_object = new Field();
                                                    //                                                    if ($field_object->getFromDB($content)) {
                                                    //                                                        if ($field_object->fields['type'] == "informations") {
                                                    //                                                            $values['fields'][$content] = $field_object->fields['label2'];
                                                    //                                                        }
                                                    //                                                    }
                                                    if (isset($values['fields'][$content])) {
                                                        $field = new Field();
                                                        $field->getFromDB($content);
                                                        $fields = $field->fields;


                                                        $fields['value'] = $values['fields'][$content];

                                                        $fields['value2'] = '';
                                                        if (($fields['type'] == 'date_interval'
                                                                || $fields['type'] == 'datetime_interval')
                                                            && isset($values['fields'][$content . '-2'])) {
                                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                                        }
                                                        $result = [];
                                                        $result['content'] = "";
                                                        $result[$fields['rank']]['content'] = "";
                                                        $result[$fields['rank']]['display'] = false;
                                                        $parent_fields_id = 0;
                                                        $value = self::getContentWithField(
                                                            [],
                                                            0,
                                                            $fields,
                                                            $result,
                                                            $parent_fields_id,
                                                            true
                                                        );
                                                        if ($fields['type'] == "textarea") {
                                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                $value = str_replace("\\n", '","', $value);
                                                            }
                                                        }
                                                        if ($value != null) {
                                                            $line['tasks'][$key]['content'] = str_replace(
                                                                "#" . $content . "#",
                                                                $value,
                                                                $line['tasks'][$key]['content']
                                                            );
                                                        }
                                                    } else {
                                                        $explodeContent2 = explode(".", $content);

                                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                                            $field_object = new Field();
                                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                )) {
                                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                                    $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                        $explodeContent2[1],
                                                                        $users_id,
                                                                        $_SESSION['glpiactive_entity'],
                                                                        $content,
                                                                        $line['tasks'][$key]['content']
                                                                    );
                                                                }
                                                            }
                                                        }
                                                        $users_id = $parent_fields['_users_id_requester'];
                                                        $line['tasks'][$key]['content'] = self::getContentForUser(
                                                            $content,
                                                            $users_id,
                                                            $_SESSION['glpiactive_entity'],
                                                            $content,
                                                            $line['tasks'][$key]['content'],
                                                            true
                                                        );
                                                    }
                                                }
                                            }
                                        } else {

                                            //Content of mail

                                            $mail = new MailTask();
                                            $mail->getFromDBByCrit(["plugin_metademands_tasks_id" => $l['tasks_id']]);

                                            if ($l['useBlock']) {
                                                $blocks_use = json_decode($l['block_use']);
                                                if (!empty($blocks_use)) {
                                                    foreach ($line['form'] as $i => $f) {
                                                        if (!in_array($f['rank'], $blocks_use)) {
                                                            unset($line['form'][$i]);
                                                            unset($values_form[$i]);
                                                        }
                                                    }

                                                    $values[$metademand->getID()] = $values_form[0]['fields'];
                                                    $parent_fields_content = self::formatFields(
                                                        $line['form'],
                                                        $metademand->getID(),
                                                        $values,
                                                        ['formatastable' => $l['formatastable']]
                                                    );

                                                } else {
                                                    $parent_fields_content['content'] = $parent_fields['content'];
                                                }
                                            }
                                            $content = "";
                                            $son_ticket_data['content'] = $mail->fields['content'] ?? "";
                                            if (!empty($son_ticket_data['content'])) {
                                                if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                                                    $content = "<table class='tab_cadre' style='width: 100%;border:0;background:none;word-break: unset;'>";
                                                    $content .= "<tr><th colspan='2'>" . __(
                                                        'Child Ticket',
                                                        'metademands'
                                                    )
                                                        . "</th></tr><tr><td colspan='2'>";
                                                }

                                                $content .= RichText::getSafeHtml(
                                                    $son_ticket_data['content']
                                                );

                                                if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                                                    $content .= "</td></tr></table><br>";
                                                }
                                            }


                                            if (!empty($parent_fields_content['content'])) {
                                                $content .= "<table class='tab_cadre' style='width: 100%;border:0;background:none;word-break: unset;'><tr><th colspan='2'>";
                                                $content .= _n('Parent tickets', 'Parent tickets', 1, 'metademands')
                                                    . "</th></tr><tr><td colspan='2'>" . RichText::getSafeHtml(
                                                        $parent_fields_content['content']
                                                    );
                                                $content .= "</td></tr></table><br>";
                                            }

                                            $metatask = new Task();
                                            $metatask->getFromDB($l['tasks_id']);

                                            $line['tasks'][$key]['tickettasks_name'] = $metatask->fields['name'];
                                            $line['tasks'][$key]['content'] = $content;

                                            //replace #id# in title with the value
                                            do {
                                                $match = self::getBetween($l['tickettasks_name'], '[', ']');
                                                if (empty($match)) {
                                                    $explodeTitle = [];
                                                    $explodeTitle = explode("#", $l['tickettasks_name']);
                                                    foreach ($explodeTitle as $title) {

                                                        if (isset($values['fields'][$title])) {

                                                            $field = new Field();
                                                            $field->getFromDB($title);
                                                            $fields = $field->fields;


                                                            $fields['value'] = $values['fields'][$title];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                                "#" . $title . "#",
                                                                $value,
                                                                $line['tasks'][$key]['tickettasks_name']
                                                            );
                                                        } else {

                                                            $explodeTitle2 = explode(".", $title);

                                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object"
                                                                        && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                                        $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                                            $explodeTitle2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $title,
                                                                            $line['tasks'][$key]['tickettasks_name']
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    $explodeVal = [];
                                                    $explodeVal = explode("|", $match);
                                                    $find = false;
                                                    $val_to_replace = "";
                                                    foreach ($explodeVal as $str) {
                                                        $explodeTitle = explode("#", $str);
                                                        foreach ($explodeTitle as $title) {
                                                            if (isset($values['fields'][$title])) {
                                                                $field = new Field();
                                                                $field->getFromDB($title);
                                                                $fields = $field->fields;


                                                                $fields['value'] = $values['fields'][$title];

                                                                $fields['value2'] = '';
                                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                                }
                                                                $result = [];
                                                                $result['content'] = "";
                                                                $result[$fields['rank']]['content'] = "";
                                                                $result[$fields['rank']]['display'] = false;
                                                                $parent_fields_id = 0;
                                                                $value = self::getContentWithField(
                                                                    [],
                                                                    0,
                                                                    $fields,
                                                                    $result,
                                                                    $parent_fields_id,
                                                                    true
                                                                );
                                                                $str = str_replace("#" . $title . "#", $value, $str);
                                                                if (!is_null($value) && !empty($value)) {
                                                                    $find = true;
                                                                }
                                                            } else {
                                                                $explodeTitle2 = explode(".", $title);

                                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                    $field_object = new Field();
                                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                        )) {
                                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                                            $str = self::getContentForUser(
                                                                                $explodeTitle2[1],
                                                                                $_SESSION['glpiactive_entity'],
                                                                                $users_id,
                                                                                $title,
                                                                                $str
                                                                            );
                                                                        }
                                                                    }
                                                                }
                                                                $users_id = $parent_fields['_users_id_requester'];
                                                                $str = self::getContentForUser(
                                                                    $title,
                                                                    $users_id,
                                                                    $_SESSION['glpiactive_entity'],
                                                                    $title,
                                                                    $str,
                                                                    true
                                                                );
                                                            }
                                                        }
                                                        if ($find == true) {
                                                            break;
                                                        }
                                                    }

                                                    //                                                    if (str_contains($match, "#")) {
                                                    //                                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                    //                                                            "[" . $match . "]",
                                                    //                                                            $str,
                                                    //                                                            $line['tasks'][$key]['tickettasks_name']
                                                    //                                                        );
                                                    //                                                        $l['tickettasks_name'] = str_replace(
                                                    //                                                            "[" . $match . "]",
                                                    //                                                            $str,
                                                    //                                                            $l['tickettasks_name']
                                                    //                                                        );
                                                    //                                                    } else {
                                                    //                                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                    //                                                            "[" . $match . "]",
                                                    //                                                            "<@" . $str . "@>",
                                                    //                                                            $line['tasks'][$key]['tickettasks_name']
                                                    //                                                        );
                                                    //                                                        $l['tickettasks_name'] = str_replace(
                                                    //                                                            "[" . $match . "]",
                                                    //                                                            "<@" . $str . "@>",
                                                    //                                                            $l['tickettasks_name']
                                                    //                                                        );
                                                    //                                                    }
                                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                                }
                                            } while (!empty($match));
                                            //replace #id# for content
                                            do {
                                                $match = self::getBetween($son_ticket_data['content'], '[', ']');
                                                if (empty($match) && $son_ticket_data['content'] != null) {
                                                    $explodeContent = explode("#", $son_ticket_data['content']);
                                                    foreach ($explodeContent as $content) {
                                                        if (isset($values['fields'][$content])) {
                                                            $field = new Field();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;


                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }
                                                            $line['tasks'][$key]['content'] = str_replace(
                                                                "#" . $content . "#",
                                                                $value,
                                                                $son_ticket_data['content']
                                                            );
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object"
                                                                        && $field_object->fields['item'] == User::getType()) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                            $explodeContent2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $content,
                                                                            $son_ticket_data['content']
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    $explodeVal = [];
                                                    $explodeVal = explode("|", $match);
                                                    $find = false;
                                                    $val_to_replace = "";
                                                    foreach ($explodeVal as $str) {
                                                        $explodeContent = explode("#", $str);
                                                        foreach ($explodeContent as $content) {
                                                            if (isset($values['fields'][$content])) {
                                                                $field = new Field();
                                                                $field->getFromDB($content);
                                                                $fields = $field->fields;


                                                                $fields['value'] = $values['fields'][$content];

                                                                $fields['value2'] = '';
                                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                    $fields['value2'] = $values['fields'][$content . '-2'];
                                                                }
                                                                $result = [];
                                                                $result['content'] = "";
                                                                $result[$fields['rank']]['content'] = "";
                                                                $result[$fields['rank']]['display'] = false;
                                                                $parent_fields_id = 0;
                                                                $value = self::getContentWithField(
                                                                    [],
                                                                    0,
                                                                    $fields,
                                                                    $result,
                                                                    $parent_fields_id,
                                                                    true
                                                                );
                                                                if ($fields['type'] == "textarea") {
                                                                    if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                        $value = str_replace("\\n", '","', $value);
                                                                    }
                                                                }
                                                                $str = str_replace("#" . $content . "#", $value, $str);
                                                                if (!is_null($value) && !empty($value)) {
                                                                    $find = true;
                                                                }
                                                            } else {
                                                                $explodeContent2 = explode(".", $content);

                                                                if (isset($values['fields'][$explodeContent2[0]])) {
                                                                    $field_object = new Field();
                                                                    if ($field_object->getFromDB($explodeContent2[0])) {
                                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                        )) {
                                                                            $users_id = $values['fields'][$explodeContent2[0]];
                                                                            $str = self::getContentForUser(
                                                                                $explodeContent2[1],
                                                                                $users_id,
                                                                                $_SESSION['glpiactive_entity'],
                                                                                $content,
                                                                                $str
                                                                            );
                                                                        }
                                                                    }
                                                                }
                                                                $users_id = $parent_fields['_users_id_requester'];
                                                                $str = self::getContentForUser(
                                                                    $content,
                                                                    $users_id,
                                                                    $_SESSION['glpiactive_entity'],
                                                                    $content,
                                                                    $str,
                                                                    true
                                                                );
                                                            }
                                                        }
                                                        if ($find == true) {
                                                            break;
                                                        }
                                                    }
                                                    //                                    $line['tasks'][$key]['content'] = str_replace("[" . $match . "]", $str, $line['tasks'][$key]['content']);
                                                    if (str_contains($match, "#")) {
                                                        $line['tasks'][$key]['content'] = str_replace(
                                                            "[" . $match . "]",
                                                            $str,
                                                            $son_ticket_data['content']
                                                        );
                                                        $l['content'] = str_replace(
                                                            "[" . $match . "]",
                                                            $str,
                                                            $l['content']
                                                        );
                                                    } else {
                                                        if ($line['tasks'][$key]['content'] != null) {
                                                            $line['tasks'][$key]['content'] = str_replace(
                                                                "[" . $match . "]",
                                                                "<@" . $str . "@>",
                                                                $son_ticket_data['content']
                                                            );
                                                        }
                                                        if ($l['content'] != null) {
                                                            $l['content'] = str_replace(
                                                                "[" . $match . "]",
                                                                "<@" . $str . "@>",
                                                                $son_ticket_data['content']
                                                            );
                                                        }
                                                    }
                                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                                }
                                            } while (!empty($match));

                                            if ($line['tasks'][$key]['content'] != null) {
                                                $line['tasks'][$key]['content'] = str_replace(
                                                    "<@",
                                                    "[",
                                                    $line['tasks'][$key]['content']
                                                );
                                                $line['tasks'][$key]['content'] = str_replace(
                                                    "@>",
                                                    "]",
                                                    $line['tasks'][$key]['content']
                                                );
                                            }
                                            if ($l['content'] != null) {
                                                $l['content'] = str_replace("<@", "[", $l['content']);
                                                $l['content'] = str_replace("@>", "]", $l['content']);
                                            }
                                            if ($l['content'] != null) {
                                                $l['content'] = RichText::getTextFromHtml($l['content']);
                                                $explodeContent = explode("#", $l['content']);
                                                foreach ($explodeContent as $content) {
                                                    //                                                    $field_object = new Field();
                                                    //                                                    if ($field_object->getFromDB($content)) {
                                                    //                                                        if ($field_object->fields['type'] == "informations") {
                                                    //                                                            $values['fields'][$content] = $field_object->fields['label2'];
                                                    //                                                        }
                                                    //                                                    }

                                                    if (isset($values['fields'][$content])) {
                                                        $field = new Field();
                                                        $field->getFromDB($content);
                                                        $fields = $field->fields;


                                                        $fields['value'] = $values['fields'][$content];

                                                        $fields['value2'] = '';
                                                        if (($fields['type'] == 'date_interval'
                                                                || $fields['type'] == 'datetime_interval')
                                                            && isset($values['fields'][$content . '-2'])) {
                                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                                        }
                                                        $result = [];
                                                        $result['content'] = "";
                                                        $result[$fields['rank']]['content'] = "";
                                                        $result[$fields['rank']]['display'] = false;
                                                        $parent_fields_id = 0;
                                                        $value = self::getContentWithField(
                                                            [],
                                                            0,
                                                            $fields,
                                                            $result,
                                                            $parent_fields_id,
                                                            true
                                                        );
                                                        if ($fields['type'] == "textarea") {
                                                            if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                $value = str_replace("\\n", '","', $value);
                                                            }
                                                        }
                                                        $line['tasks'][$key]['content'] = str_replace(
                                                            "#" . $content . "#",
                                                            $value,
                                                            $line['tasks'][$key]['content']
                                                        );
                                                    } else {
                                                        $explodeContent2 = explode(".", $content);

                                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                                            $field_object = new Field();
                                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                )) {
                                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                                    $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                        $explodeContent2[1],
                                                                        $users_id,
                                                                        $_SESSION['glpiactive_entity'],
                                                                        $content,
                                                                        $line['tasks'][$key]['content']
                                                                    );
                                                                }
                                                            }
                                                        }
                                                        $users_id = $parent_fields['_users_id_requester'];
                                                        $line['tasks'][$key]['content'] = self::getContentForUser(
                                                            $content,
                                                            $users_id,
                                                            $_SESSION['glpiactive_entity'],
                                                            $content,
                                                            $line['tasks'][$key]['content'],
                                                            true
                                                        );
                                                    }
                                                }
                                            }
                                            $recipients = [];
                                            $email = new UserEmail();
                                            $user = new User();
                                            if (isset($mail->fields['groups_id_recipient']) && $mail->fields['groups_id_recipient'] > 0) {
                                                $users = Group_User::getGroupUsers(
                                                    $mail->fields['groups_id_recipient']
                                                );
                                                foreach ($users as $usr) {
                                                    $address = $email->find(['users_id' => $usr['id']], [], 1);
                                                    if (count($address) > 0) {
                                                        foreach ($address as $id => $adr) {
                                                            $recipients[$usr['id']]['email'] = $adr['email'];
                                                        }
                                                        $recipients[$usr['id']]['name'] = $usr['realname'] . " " . $usr['firstname'];
                                                    }
                                                }
                                            }
                                            if (isset($mail->fields['users_id_recipient']) && $mail->fields['users_id_recipient'] > 0) {
                                                $address = $email->find(
                                                    ['users_id' => $mail->fields['users_id_recipient']],
                                                    [],
                                                    1
                                                );
                                                $user->getFromDB($mail->fields['users_id_recipient']);
                                                if (count($address) > 0) {
                                                    foreach ($address as $id => $adr) {
                                                        $recipients[$user->fields['id']]['email'] = $adr['email'];
                                                        $recipients[$user->fields['id']]['name'] = $user->fields['realname'] . " " . $user->fields['firstname'];
                                                    }
                                                }
                                            }
                                            if (count($recipients) > 0) {
                                                MailTask::sendMail(
                                                    $line['tasks'][$key]['tickettasks_name'],
                                                    $recipients,
                                                    $line['tasks'][$key]['content']
                                                );
                                            }

                                            unset($line['tasks'][$key]);
                                        }
                                    }
                                    if ($metademand->fields['force_create_tasks'] == 0) {
                                        //first sons
                                        if (!self::createSonsTickets(
                                            $metademands_id,
                                            $parent_tickets_id,
                                            self::mergeFields(
                                                $parent_fields,
                                                $parent_ticketfields
                                            ),
                                            $parent_tickets_id,
                                            $line['tasks'],
                                            $tasklevel,
                                            $inputField,
                                            $inputFieldMain
                                        )) {
                                            $KO[] = 1;
                                        }
                                    } else {
                                        $meta_tasks = $line['tasks'];
                                        if (is_array($meta_tasks)) {
                                            foreach ($meta_tasks as $meta_task) {
                                                if (Ticket_Field::checkTicketCreation(
                                                    $meta_task['tasks_id'],
                                                    $parent_tickets_id
                                                )) {
                                                    $ticket_task = new TicketTask();
                                                    $input = [];
                                                    $input['content'] = $meta_task['tickettasks_name']
                                                     . " " . $meta_task['content'];
                                                    $input['tickets_id'] = $parent_tickets_id;
                                                    $input['groups_id_tech'] = $meta_task["groups_id_assign"];
                                                    $input['users_id_tech'] = $meta_task["users_id_assign"];
                                                    if (!$ticket_task->add($input)) {
                                                        $KO[] = 1;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $metaValid = new MetademandValidation();
                                    $paramIn["tickets_id"] = $parent_tickets_id;
                                    $paramIn["plugin_metademands_metademands_id"] = $metademands_id;
                                    $paramIn["users_id"] = 0;
                                    $paramIn["validate"] = MetademandValidation::TO_VALIDATE;
                                    $paramIn["date"] = date("Y-m-d H:i:s");

                                    foreach ($line['tasks'] as $key => $l) {
                                        //replace #id# in title with the value
                                        do {
                                            if (isset($resource_id)) {
                                                $resource = new Resource();
                                                if ($resource->getFromDB($resource_id)) {
                                                    $line['tasks'][$key]['tickettasks_name'] .= " - " . $resource->getField(
                                                        'name'
                                                    ) . " " . $resource->getField('firstname');
                                                }
                                                $line['tasks'][$key]['items_id'] = [Resource::class => [$resource_id]];
                                            }
                                            $match = self::getBetween($l['tickettasks_name'], '[', ']');
                                            if (empty($match)) {
                                                $explodeTitle = [];
                                                $explodeTitle = explode("#", $l['tickettasks_name']);
                                                foreach ($explodeTitle as $title) {
                                                    if (isset($values['fields'][$title])) {
                                                        $field = new Field();
                                                        $field->getFromDB($title);
                                                        $fields = $field->fields;
                                                        $fields['value'] = $values['fields'][$title];

                                                        $fields['value2'] = '';
                                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                                        }
                                                        $result = [];
                                                        $result['content'] = "";
                                                        $result[$fields['rank']]['content'] = "";
                                                        $result[$fields['rank']]['display'] = false;
                                                        $parent_fields_id = 0;
                                                        $value = self::getContentWithField(
                                                            [],
                                                            0,
                                                            $fields,
                                                            $result,
                                                            $parent_fields_id,
                                                            true
                                                        );
                                                        if ($value != null) {
                                                            $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                                "#" . $title . "#",
                                                                $value,
                                                                $line['tasks'][$key]['tickettasks_name']
                                                            );
                                                        }
                                                    } else {
                                                        $explodeTitle2 = explode(".", $title);

                                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                                            $field_object = new Field();
                                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                )) {
                                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                                    $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                                        $explodeTitle2[1],
                                                                        $users_id,
                                                                        $_SESSION['glpiactive_entity'],
                                                                        $title,
                                                                        $line['tasks'][$key]['tickettasks_name']
                                                                    );
                                                                }
                                                            }
                                                        }
                                                        $users_id = $parent_fields['_users_id_requester'];
                                                        $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                            $title,
                                                            $users_id,
                                                            $_SESSION['glpiactive_entity'],
                                                            $title,
                                                            $line['tasks'][$key]['tickettasks_name'],
                                                            true
                                                        );
                                                    }
                                                }
                                            } else {
                                                $explodeVal = [];
                                                $explodeVal = explode("|", $match);
                                                $find = false;
                                                $val_to_replace = "";
                                                foreach ($explodeVal as $str) {
                                                    $explodeTitle = explode("#", $str);
                                                    foreach ($explodeTitle as $title) {
                                                        if (isset($values['fields'][$title])) {
                                                            $field = new Field();
                                                            $field->getFromDB($title);
                                                            $fields = $field->fields;
                                                            $fields['value'] = $values['fields'][$title];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            $str = str_replace("#" . $title . "#", $value, $str);
                                                            if (!is_null($value) && !empty($value)) {
                                                                $find = true;
                                                            }
                                                        } else {
                                                            $explodeTitle2 = explode(".", $title);

                                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                    )) {
                                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                                        $str = self::getContentForUser(
                                                                            $explodeTitle2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $title,
                                                                            $str
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $str = self::getContentForUser(
                                                                $title,
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $title,
                                                                $str,
                                                                true
                                                            );
                                                        }
                                                    }
                                                    if ($find == true) {
                                                        break;
                                                    }
                                                }

                                                if (str_contains($match, "#")) {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                        "[" . $match . "]",
                                                        $str,
                                                        $line['tasks'][$key]['tickettasks_name']
                                                    );
                                                    $l['tickettasks_name'] = str_replace(
                                                        "[" . $match . "]",
                                                        $str,
                                                        $l['tickettasks_name']
                                                    );
                                                } else {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                        "[" . $match . "]",
                                                        "<@" . $str . "@>",
                                                        $line['tasks'][$key]['tickettasks_name']
                                                    );
                                                    $l['tickettasks_name'] = str_replace(
                                                        "[" . $match . "]",
                                                        "<@" . $str . "@>",
                                                        $l['tickettasks_name']
                                                    );
                                                }
                                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                            }
                                        } while (!empty($match));

                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                            "<@",
                                            "[",
                                            $line['tasks'][$key]['tickettasks_name']
                                        );
                                        $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                            "@>",
                                            "]",
                                            $line['tasks'][$key]['tickettasks_name']
                                        );
                                        $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                                        $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                                        $explodeTitle = explode("#", $l['tickettasks_name']);
                                        foreach ($explodeTitle as $title) {
                                            if (isset($values['fields'][$title])) {
                                                $field = new Field();
                                                $field->getFromDB($title);
                                                $fields = $field->fields;

                                                $fields['value'] = $values['fields'][$title];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval'
                                                        || $fields['type'] == 'datetime_interval')
                                                    && isset($values['fields'][$title . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                }
                                                $result = [];
                                                $result['content'] = "";
                                                $result[$fields['rank']]['content'] = "";
                                                $result[$fields['rank']]['display'] = false;

                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField(
                                                    [],
                                                    0,
                                                    $fields,
                                                    $result,
                                                    $parent_fields_id,
                                                    true
                                                );
                                                if ($value != null) {
                                                    $line['tasks'][$key]['tickettasks_name'] = str_replace(
                                                        "#" . $title . "#",
                                                        $value,
                                                        $line['tasks'][$key]['tickettasks_name']
                                                    );
                                                }
                                            } else {
                                                $explodeTitle2 = explode(".", $title);

                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                    $field_object = new Field();
                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                        )) {
                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                            $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                                $explodeTitle2[1],
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $title,
                                                                $line['tasks'][$key]['tickettasks_name']
                                                            );
                                                        }
                                                    }
                                                }

                                                $users_id = $parent_fields['_users_id_requester'];
                                                $line['tasks'][$key]['tickettasks_name'] = self::getContentForUser(
                                                    $title,
                                                    $users_id,
                                                    $_SESSION['glpiactive_entity'],
                                                    $title,
                                                    $line['tasks'][$key]['tickettasks_name'],
                                                    true
                                                );
                                            }
                                        }

                                        //replace #id# in content with the value
                                        do {
                                            $match = self::getBetween($l['content'], '[', ']');
                                            if (empty($match)) {
                                                if ($l['content'] != null) {
                                                    $l['content'] = RichText::getTextFromHtml(
                                                        $l['content']
                                                    );
                                                    $explodeContent = explode("#", $l['content']);
                                                    foreach ($explodeContent as $content) {
                                                        //                                                        $field_object = new Field();
                                                        //                                                        if ($field_object->getFromDB($content)) {
                                                        //                                                            if ($field_object->fields['type'] == "informations") {
                                                        //                                                                $values['fields'][$content] = $field_object->fields['label2'];
                                                        //                                                            }
                                                        //                                                        }
                                                        if (isset($values['fields'][$content])) {
                                                            $field = new Field();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;

                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }
                                                            $line['tasks'][$key]['content'] = str_replace(
                                                                "#" . $content . "#",
                                                                $value,
                                                                $line['tasks'][$key]['content']
                                                            );
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                    )) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                            $explodeContent2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $content,
                                                                            $line['tasks'][$key]['content']
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                $content,
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $content,
                                                                $line['tasks'][$key]['content'],
                                                                true
                                                            );
                                                        }
                                                    }
                                                }
                                            } else {
                                                $explodeVal = [];
                                                $explodeVal = explode("|", $match);
                                                $find = false;
                                                $val_to_replace = "";
                                                foreach ($explodeVal as $str) {
                                                    $explodeContent = explode("#", $str);
                                                    foreach ($explodeContent as $content) {
                                                        if (isset($values['fields'][$content])) {
                                                            $field = new Field();
                                                            $field->getFromDB($content);
                                                            $fields = $field->fields;

                                                            $fields['value'] = $values['fields'][$content];

                                                            $fields['value2'] = '';
                                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                                $fields['value2'] = $values['fields'][$content . '-2'];
                                                            }
                                                            $result = [];
                                                            $result['content'] = "";
                                                            $result[$fields['rank']]['content'] = "";
                                                            $result[$fields['rank']]['display'] = false;
                                                            $parent_fields_id = 0;
                                                            $value = self::getContentWithField(
                                                                [],
                                                                0,
                                                                $fields,
                                                                $result,
                                                                $parent_fields_id,
                                                                true
                                                            );
                                                            if ($fields['type'] == "textarea") {
                                                                if ($line['tasks'][$key]["formatastable"] == 0) {
                                                                    $value = str_replace("\\n", '","', $value);
                                                                }
                                                            }

                                                            $str = str_replace("#" . $content . "#", $value, $str);
                                                            if (!is_null($value) && !empty($value)) {
                                                                $find = true;
                                                            }
                                                        } else {
                                                            $explodeContent2 = explode(".", $content);

                                                            if (isset($values['fields'][$explodeContent2[0]])) {
                                                                $field_object = new Field();
                                                                if ($field_object->getFromDB($explodeContent2[0])) {
                                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                                    )) {
                                                                        $users_id = $values['fields'][$explodeContent2[0]];
                                                                        $str = self::getContentForUser(
                                                                            $explodeContent2[1],
                                                                            $users_id,
                                                                            $_SESSION['glpiactive_entity'],
                                                                            $content,
                                                                            $str
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                            $users_id = $parent_fields['_users_id_requester'];
                                                            $str = self::getContentForUser(
                                                                $content,
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $content,
                                                                $str,
                                                                true
                                                            );
                                                        }
                                                    }
                                                    if ($find == true) {
                                                        break;
                                                    }
                                                }

                                                if (str_contains($match, "#")) {
                                                    $line['tasks'][$key]['content'] = str_replace(
                                                        "[" . $match . "]",
                                                        $str,
                                                        $line['tasks'][$key]['content']
                                                    );
                                                    $l['content'] = str_replace(
                                                        "[" . $match . "]",
                                                        $str,
                                                        $l['content']
                                                    );
                                                } else {
                                                    $line['tasks'][$key]['content'] = str_replace(
                                                        "[" . $match . "]",
                                                        "<@" . $str . "@>",
                                                        $line['tasks'][$key]['content']
                                                    );
                                                    $l['content'] = str_replace(
                                                        "[" . $match . "]",
                                                        "<@" . $str . "@>",
                                                        $l['content']
                                                    );
                                                }
                                                //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                            }
                                        } while (!empty($match));

                                        if (!empty($line['tasks'][$key]['content'])) {
                                            $line['tasks'][$key]['content'] = str_replace(
                                                "<@",
                                                "[",
                                                $line['tasks'][$key]['content']
                                            );
                                            $line['tasks'][$key]['content'] = str_replace(
                                                "@>",
                                                "]",
                                                $line['tasks'][$key]['content']
                                            );
                                        }
                                        if (!empty($l['content'])) {
                                            $l['content'] = str_replace("<@", "[", $l['content']);
                                            $l['content'] = str_replace("@>", "]", $l['content']);

                                            $l['content'] = RichText::getTextFromHtml($l['content']);
                                            $explodeContent = explode("#", $l['content']);
                                            foreach ($explodeContent as $content) {
                                                //                                            $field_object = new Field();
                                                //                                            if ($field_object->getFromDB($content)) {
                                                //                                                if ($field_object->fields['type'] == "informations") {
                                                //                                                    $values['fields'][$content] = $field_object->fields['label2'];
                                                //                                                }
                                                //                                            }

                                                if (isset($values['fields'][$content])) {
                                                    $field = new Field();
                                                    $field->getFromDB($content);
                                                    $fields = $field->fields;

                                                    $fields['value'] = $values['fields'][$content];

                                                    $fields['value2'] = '';
                                                    if (($fields['type'] == 'date_interval'
                                                            || $fields['type'] == 'datetime_interval')
                                                        && isset($values['fields'][$content . '-2'])) {
                                                        $fields['value2'] = $values['fields'][$content . '-2'];
                                                    }
                                                    $result = [];
                                                    $result['content'] = "";
                                                    $result[$fields['rank']]['content'] = "";
                                                    $result[$fields['rank']]['display'] = false;
                                                    $parent_fields_id = 0;
                                                    $value = self::getContentWithField(
                                                        [],
                                                        0,
                                                        $fields,
                                                        $result,
                                                        $parent_fields_id,
                                                        true
                                                    );
                                                    if ($fields['type'] == "textarea") {
                                                        if ($line['tasks'][$key]["formatastable"] == 0) {
                                                            $value = str_replace("\\n", '","', $value);
                                                        }
                                                    }
                                                    $line['tasks'][$key]['content'] = str_replace(
                                                        "#" . $content . "#",
                                                        $value,
                                                        $line['tasks'][$key]['content']
                                                    );
                                                } else {
                                                    $explodeContent2 = explode(".", $content);

                                                    if (isset($values['fields'][$explodeContent2[0]])) {
                                                        $field_object = new Field();
                                                        if ($field_object->getFromDB($explodeContent2[0])) {
                                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                            )) {
                                                                $users_id = $values['fields'][$explodeContent2[0]];
                                                                $line['tasks'][$key]['content'] = self::getContentForUser(
                                                                    $explodeContent2[1],
                                                                    $users_id,
                                                                    $_SESSION['glpiactive_entity'],
                                                                    $content,
                                                                    $line['tasks'][$key]['content']
                                                                );
                                                            }
                                                        }
                                                    }
                                                    $users_id = $parent_fields['_users_id_requester'];
                                                    $line['tasks'][$key]['content'] = self::getContentForUser(
                                                        $content,
                                                        $users_id,
                                                        $_SESSION['glpiactive_entity'],
                                                        $content,
                                                        $line['tasks'][$key]['content'],
                                                        true
                                                    );
                                                }
                                            }
                                        }
                                    }

                                    $tasks = $line['tasks'];
                                    foreach ($tasks as $key => $val) {
                                        if (Ticket_Field::checkTicketCreation(
                                            $val['tasks_id'],
                                            $parent_tickets_id
                                        )) {
                                            $tasks[$key]['tickettasks_name'] = addslashes(
                                                urlencode($val['tickettasks_name'])
                                            );
                                            if (isset($input['items_id'][Resource::class])) {
                                                if ($resource->getFromDB($resource_id)) {
                                                    $tasks[$key]['tickettasks_name'] .= " " . $resource->fields['name'] . " " . $resource->fields['firstname'];
                                                    $tasks[$key]['items_id'] = [Resource::class => [$resource_id]];
                                                }
                                            }
                                            if ($val['tasks_completename'] != null) {
                                                $tasks[$key]['tasks_completename'] = addslashes(
                                                    urlencode($val['tasks_completename'])
                                                );
                                            }
                                            if (!empty($val['content'])) {
                                                $tasks[$key]['content'] = addslashes(urlencode($val['content']));
                                            }
                                            $tasks[$key]['block_use'] = json_decode($val["block_use"], true);
                                        } else {
                                            unset($tasks[$key]);
                                        }
                                    }

                                    $paramIn["tickets_to_create"] = json_encode($tasks);
                                    if ($metaValid->getFromDBByCrit(['tickets_id' => $paramIn["tickets_id"]])) {
                                        $paramIn['id'] = $metaValid->getID();
                                        $metaValid->update($paramIn);
                                    } else {
                                        $metaValid->add($paramIn);
                                    }
                                }
                            } else {
                                if ($metademand->fields["validation_subticket"] == 1) {
                                    $metaValid = new MetademandValidation();
                                    $paramIn["tickets_id"] = $parent_tickets_id;
                                    $paramIn["plugin_metademands_metademands_id"] = $metademands_id;
                                    $paramIn["users_id"] = 0;
                                    $paramIn["validate"] = MetademandValidation::TO_VALIDATE_WITHOUTTASK;
                                    $paramIn["date"] = date("Y-m-d H:i:s");

                                    $paramIn["tickets_to_create"] = "";
                                    if ($metaValid->getFromDBByCrit(['tickets_id' => $paramIn["tickets_id"]])) {
                                        $paramIn['id'] = $metaValid->getID();
                                        $metaValid->update($paramIn);
                                    } else {
                                        $metaValid->add($paramIn);
                                    }
                                }
                            }

                            // Case of simple ticket convertion
                            if ($ticket_exists) {
                                if (isset($parent_ticketfields['_users_id_observer'])
                                    && !empty($parent_ticketfields['_users_id_observer'])) {
                                    $parent_ticketfields['_itil_observer'] = [
                                        'users_id' => $parent_ticketfields['_users_id_observer'],
                                        '_type' => 'user',
                                    ];
                                }
                                if (isset($parent_ticketfields['_groups_id_observer'])
                                    && !empty($parent_ticketfields['_groups_id_observer'])) {
                                    $parent_ticketfields['_itil_observer'] = [
                                        'groups_id' => $parent_ticketfields['_groups_id_observer'],
                                        '_type' => 'group',
                                    ];
                                }
                                if (isset($parent_ticketfields['_users_id_assign'])
                                    && !empty($parent_ticketfields['_users_id_assign'])) {
                                    $parent_ticketfields['_itil_assign'] = [
                                        'users_id' => $parent_ticketfields['_users_id_assign'],
                                        '_type' => 'user',
                                    ];
                                }
                                if (isset($parent_ticketfields['_groups_id_assign'])
                                    && !empty($parent_ticketfields['_groups_id_assign'])) {
                                    $parent_ticketfields['_itil_assign'] = [
                                        'groups_id' => $parent_ticketfields['_groups_id_assign'],
                                        '_type' => 'group',
                                    ];
                                }

                                $object->update(self::mergeFields($parent_fields, $parent_ticketfields));
                            }
                        }
                    } else {
                        $KO[] = 1;
                    }
                }
            }
        }

        // Message return
        $parent_metademands_name = $object->fields['name'];
        if (count($KO)) {
            $message = __('Demand add failed', 'metademands');
        } else {
            if ($object_class == 'Ticket') {
                if (!in_array(1, $ticket_exists_array)) {
                    $message = sprintf(
                        __('Demand "%s" added with success', 'metademands'),
                        "<a href='" . $object_class::getFormURL(
                        ) . "?id=" . $parent_tickets_id . "'>" . $parent_metademands_name . "</a>"
                    );
                } else {
                    $message = sprintf(
                        __('Ticket "%s" successfully updated', 'metademands'),
                        "<a href='" . $object_class::getFormURL() . "?id=" . $object->getID() . "'>" . $object->getID(
                        ) . "</a>"
                    );
                }
            } else {
                $message = sprintf(
                    __('%1$s %2$s successfully created', 'metademands'),
                    $object_class::getTypeName(1),
                    "<a href='" . $object_class::getFormURL(
                    ) . "?id=" . $object->fields['id'] . "'>" . $object->fields['id'] . "</a>"
                );
            }
            //launch child meta if needed
            $childs_meta = MetademandTask::getChildMetademandsToCreate($metademands_id);
            if (count($childs_meta) > 0) {
                foreach ($childs_meta as $k => $child_meta) {
                    if (isset($_SESSION['childs_metademands_hide'])
                        && in_array($child_meta, $_SESSION['childs_metademands_hide'])) {
                        continue;
                    }
                    Html::redirect(
                        Wizard::getFormURL(
                        ) . "?ancestor_tickets_id=" . $parent_tickets_id . "&metademands_id=" . $child_meta . "&step=" . self::STEP_SHOW
                    );
                }
            }


            if (isset($_SESSION['plugin_metademands'])) {
                unset($_SESSION['plugin_metademands']);
            }
        }

        return ['message' => $message, 'id' => $ancestor_tickets_id];
    }


    /**
     * @param $parent_fields
     * @param $parent_ticketfields
     *
     * @return mixed
     */
    public static function mergeFields($parent_fields, $parent_ticketfields)
    {
        foreach ($parent_ticketfields as $key => $val) {
            switch ($key) {
                //            case 'name' :
                //               $parent_fields[$key] .= ' ' . $val;
                //               break;c
                //            case 'content' :
                //               $parent_fields[$key] .= '\r\n' . $val;
                //               break;
                default:
                    $parent_fields[$key] = $val;
                    break;
            }
        }

        return $parent_fields;
    }

    /**
     * @param array $parent_fields
     * @param       $metademands_id
     * @param       $values_form
     * @param array $options
     *
     * @return array
     */
    public static function formatFields(array $parent_fields, $metademands_id, $values_form, $options = [])
    {
        $config_data = Config::getInstance();
        $langTech = $config_data['languageTech'];
        $result = [];
        $result['content'] = "";
        $parent_fields_id = 0;
        $colors = [];

        $have_freetable = false;
        $fieldmeta = new Field();
        $allfields = $fieldmeta->find(["plugin_metademands_metademands_id" => $metademands_id]);
        foreach ($allfields as $allfield) {
            if ($allfield['type'] == 'freetable') {
                $have_freetable = true;
            }
        }
        $options['formatastable'] ??= true;

        foreach ($values_form as $k => $values) {
            if (is_array($values) && $config_data['show_form_changes']) {
                foreach ($values as $key => $val) {
                    if (strpos($key, '#') > 0) {
                        $newKey = substr($key, 0, strpos($key, '#'));
                        $colors[$key] = $val;//substr($key,strpos($key,'#')+1);
                        unset($values_form[$k][$newKey]);
                    }
                }
            }
            if (empty($name = self::displayField($metademands_id, 'name', $langTech))) {
                $name = Dropdown::getDropdownName(self::getTable(), $metademands_id);
            }
            if (!isset($options['formatastable'])
                || (isset($options['formatastable'])
                    && $options['formatastable'] == true)) {
                $result['content'] .= "<table class='tab_cadre' style='width: 100%;border:0;background:none;word-break: unset;'>";
            }

            if (!empty($options['resources_id'])) {
                $resourceMeta = new Metademand_Resource();
                $result['content'] .= $resourceMeta::getTableResource($options);
            }
            //      $result['content'] .= "</table>";
            $resultTemp = [];
            $nb = 0;

            foreach ($parent_fields as $fields_id => $field) {
                if (!isset($resultTemp[$field['rank']])) {
                    $resultTemp[$field['rank']]['content'] = "";
                    $resultTemp[$field['rank']]['display'] = false;
                    //boucle sur $resultTemp - si un champ est a afficher, display du bloc devient true et donc le titre du block aussi
                }
                $field['value'] = '';
                if (isset($values[$fields_id])) {
                    $field['value'] = $values[$fields_id];
                }
                $field['value2'] = '';
                if (($field['type'] == 'date_interval'
                        || $field['type'] == 'datetime_interval')
                    && isset($values[$fields_id . '-2'])) {
                    $field['value2'] = $values[$fields_id . '-2'];
                }

                $self = new self();
                $self->getFromDB($metademands_id);
                if ($self->getField('hide_no_field') == 1) {
                    if ($field['type'] == 'radio'
                        && ($field['value'] == ""
                            || (is_array($field['value'])
                                && count($field['value']) == 0))
                    ) {
                        continue;
                    }
                    if ($field['type'] == 'number'
                        && $field['value'] == "0") {
                        continue;
                    }
                    if ($field['type'] == 'range'
                        && $field['value'] == "0") {
                        continue;
                    }
                    if ($field['type'] == 'checkbox'
                        && ($field['value'] == "" || $field['value'] == "0"
                            || (is_array($field['value'])
                                && count($field['value']) == 0))
                    ) {
                        continue;
                    }
                    if ($field['type'] == 'yesno'
                        && $field['value'] != "2") {
                        continue;
                    }
                    if ($field['type'] == 'dropdown_meta'
                        && ($field['value'] == "" || $field['value'] == "0")) {
                        continue;
                    }
                    if ($field['type'] == 'informations') {
                        continue;
                    }
                    if ($field['type'] == 'link') {
                        continue;
                    }
                    if ($field['type'] == 'number'
                        && ($field['value'] == "" || $field['value'] == "0")) {
                        continue;
                    }
                    if ($field['type'] == 'text'
                        && $field['value'] == "") {
                        continue;
                    }
                    if ($field['type'] == 'textarea'
                        && $field['value'] == "") {
                        continue;
                    }
                    if ($field['type'] == 'dropdown'
                        && ($field['value'] == "" || $field['value'] == "0")) {
                        continue;
                    }
                    if ($field['type'] == 'dropdown_object'
                        && ($field['value'] == "" || $field['value'] == "0")) {
                        continue;
                    }
                    if ($field['type'] == 'dropdown_ldap'
                        && ($field['value'] == "" || $field['value'] == "0")) {
                        continue;
                    }
                    if ($field['type'] == 'dropdown_multiple'
                        && $field['value'] == "") {
                        continue;
                    }
                    if ($field['type'] == 'date'
                        && $field['value'] == "") {
                        continue;
                    }
                    if ($field['type'] == 'datetime'
                        && $field['value'] == "") {
                        continue;
                    }
                }

                if ($field['type'] == "dropdown_meta"
                    && $field['item'] == Resource::class) {
                    $result['items_id'] = [Resource::class => [$field['value']]];
                }

                if (!isset($options['formatastable'])
                    || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                    if ($nb % 2 == 0) {
                        $resultTemp[$field['rank']]['content'] .= "<tr class='even'>";
                    } else {
                        $resultTemp[$field['rank']]['content'] .= "<tr class='odd'>";
                    }
                }
                $nb++;

                if (isset($colors) && !empty($colors)) {
                    $i = 0;
                    foreach ($colors as $key => $val) {
                        $newKey = substr($key, 0, strpos($key, '#'));
                        if ($field['id'] == $newKey) {
                            if ($i > 0) {
                                $resultTemp[$field['rank']]['content'] .= "<tr>";
                            }
                            $i++;
                            $field['value'] = $val;
                            $color = substr($key, strpos($key, '#') + 1);
                            self::getContentWithField(
                                $parent_fields,
                                $newKey,
                                $field,
                                $resultTemp,
                                $parent_fields_id,
                                false,
                                $options['formatastable'],
                                $langTech,
                                $color,
                                $have_freetable
                            );
                            unset($colors[$key]);
                            if (!isset($options['formatastable'])
                                || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                                $resultTemp[$field['rank']]['content'] .= "</tr>";
                            }
                        }
                    }
                } else {
                    //all fields
                    self::getContentWithField(
                        $parent_fields,
                        $fields_id,
                        $field,
                        $resultTemp,
                        $parent_fields_id,
                        false,
                        $options['formatastable'],
                        $langTech,
                        '',
                        $have_freetable
                    );

                    if (!isset($options['formatastable'])
                        || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                        $resultTemp[$field['rank']]['content'] .= "</tr>";
                    }
                }
            }
            foreach ($resultTemp as $blockId => $tab) {
                if ($tab['display'] == true) {
                    $result['content'] .= $tab['content'];
                }
            }
            if (!isset($options['formatastable'])
                || (isset($options['formatastable']) && $options['formatastable'] == true)) {
                $result['content'] .= "</table>";
            }
        }
        return $result;
    }

    /**
     * Format fields to display on ticket content
     *
     * @param $parent_fields
     * @param $fields_id
     * @param $field
     * @param $result
     * @param $parent_fields_id
     * @param $return_value
     */
    public static function getContentWithField(
        $parent_fields,
        $fields_id,
        $field,
        &$result,
        &$parent_fields_id,
        $return_value = false,
        $formatAsTable = true,
        $lang = '',
        $color = '',
        $is_order = false
    ) {
        global $PLUGIN_HOOKS;

        $metafield = new Field();
        if ($metafield->getFromDB($field["id"])) {
            $params = Field::getAllParamsFromField($metafield);
        }
        $field = array_merge($field, $params);

        $style_title = "class='title'";
        if ($color != "") {
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new OrderMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $field['plugin_metademands_metademands_id']]
                )) {
                    $style_title .= " style='color:$color'";
                } else {
                    $style_title .= " style='color:$color;width: 40%;'";
                }
            } else {
                $style_title .= " style='color:$color;width: 40%;'";
            }
        } else {
            if (Plugin::isPluginActive('orderfollowup')) {
                $ordermaterialmeta = new OrderMetademand();
                if ($ordermaterialmeta->getFromDBByCrit(
                    ['plugin_metademands_metademands_id' => $field['plugin_metademands_metademands_id']]
                )) {
                    $style_title .= " ";
                } else {
                    $style_title .= " style='width: 40%;'";
                }
            } else {
                $style_title .= " style='width: 40%;'";
            }
        }
        //      $style_title = "style='background-color: #cccccc;'";

        if (empty($label = Field::displayField($field['id'], 'name', $lang))) {
            $label = $field['name'];
        }

        //use plugin fields types
        $types = [];
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = Field::getPluginFieldItemsType($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    if (in_array($field['type'], array_keys($new_fields))) {
                        $types[] = $new_fields[$field['type']];
                    }
                }
            }
        }

        if ((!empty($field['value']) || $field['value'] == "0")
            && $field['value'] != 'NULL'
            || $field['type'] == 'title'
            || $field['type'] == 'title-block'
            || $field['type'] == 'informations'
            || $field['type'] == 'radio'
            || $field['type'] == 'signature'
            || $field['type'] == 'basket'
            || $field['type'] == 'freetable'
            || in_array($field['type'], $types)) {

            $class = Field::getClassFromType($field['type']);

            switch ($field['type']) {
                case 'title':
                case 'title-block':
                    $class::displayFieldItems(
                        $result,
                        $formatAsTable,
                        $style_title,
                        $label,
                        $field,
                        $return_value,
                        $lang,
                        $is_order
                    );
                    break;
                case 'dropdown_object':
                case 'dropdown_ldap':
                case 'dropdown':
                    if ($field['value'] != 0) {
                        if ($return_value == true) {
                            return $class::getFieldValue($field);
                        } else {
                            $class::displayFieldItems(
                                $result,
                                $formatAsTable,
                                $style_title,
                                $label,
                                $field,
                                $return_value,
                                $lang,
                                $is_order
                            );
                        }
                    }
                    break;
                case 'dropdown_multiple':
                case 'checkbox':
                case 'dropdown_meta':
                    if ($return_value == true) {
                        return $class::getFieldValue($field, $lang);
                    } else {
                        $class::displayFieldItems(
                            $result,
                            $formatAsTable,
                            $style_title,
                            $label,
                            $field,
                            $return_value,
                            $lang,
                            $is_order
                        );
                    }
                    break;
                case 'radio':
                    if ($return_value == true) {
                        return $class::getFieldValue($field, $label, $lang);
                    } else {
                        $class::displayFieldItems(
                            $result,
                            $formatAsTable,
                            $style_title,
                            $label,
                            $field,
                            $return_value,
                            $lang,
                            $is_order
                        );
                    }
                    break;
                case 'textarea':
                case 'text':
                case 'tel':
                case 'email':
                case 'url':
                case 'date':
                case 'time':
                case 'datetime':
                case 'date_interval':
                case 'datetime_interval':
                case 'number':
                case 'range':
                case 'freetable':
                case 'yesno':
                case 'basket':
                case 'signature':
                case 'link':
                    if ($return_value == true) {
                        return $class::getFieldValue($field);
                    } else {
                        $class::displayFieldItems(
                            $result,
                            $formatAsTable,
                            $style_title,
                            $label,
                            $field,
                            $return_value,
                            $lang,
                            $is_order
                        );
                    }

                    break;
                case 'parent_field':
                    $metademand_field = new Field();
                    if (isset($field['parent_field_id']) && $metademand_field->getFromDB($field['parent_field_id'])) {
                        $parent_field = $field;
                        $custom_values = FieldParameter::_unserialize(
                            $metademand_field->fields['custom_values']
                        );
                        foreach ($custom_values as $k => $val) {
                            if (!empty(
                                $ret = Field::displayField(
                                    $field["parent_field_id"],
                                    "custom" . $k,
                                    $lang
                                )
                            )) {
                                $custom_values[$k] = $ret;
                            }
                        }
                        $parent_field['custom_values'] = $custom_values;
                        $parent_field['type'] = $metademand_field->fields['type'];
                        $parent_field['item'] = $metademand_field->fields['item'];

                        self::getContentWithField(
                            $parent_fields,
                            $fields_id,
                            $parent_field,
                            $result,
                            $parent_fields_id,
                            false,
                            false,
                            $lang,
                            $is_order
                        );
                    }

                    break;
                default:

                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            if ($return_value == true) {
                                return $field['value'];
                            } else {
                                $result[$field['rank']]['display'] = true;
                                $content = self::displayPluginFieldItems(
                                    $plug,
                                    $formatAsTable,
                                    $style_title,
                                    $label,
                                    $field
                                );
                                $result[$field['rank']]['content'] .= $content;
                            }
                        }
                    }

                    break;
            }
        }
        $parent_fields_id = $fields_id;
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function displayPluginFieldItems($plug, $formatAsTable, $style_title, $label, $field)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'displayFieldItems'])) {
                    return $item->displayFieldItems($formatAsTable, $style_title, $label, $field);
                }
            }
        }
        return false;
    }

    /**
     * @param $metademands_id
     * @param $itilcategory
     * @param $values
     * @param $users_id_requester
     *
     * @return array
     */
    public static function formatTicketFields(
        $metademands_id,
        $itilcategory,
        $values,
        $users_id_requester,
        $entities_id
    ) {
        $inputs = [];
        $ticket_field = new TicketField();
        $parent_ticketfields = $ticket_field->find(['plugin_metademands_metademands_id' => $metademands_id]);

        $meta = new Metademand();
        $meta->getFromDB($metademands_id);
        $object = $meta->fields['object_to_create'];

        $obj = new $object();

        $tt = $obj->getITILTemplateToUse(0, $meta->fields["type"], $itilcategory, $meta->fields['entities_id']);

        if (count($parent_ticketfields)) {
            $allowed_fields = $tt->getAllowedFields(true, true);
            foreach ($parent_ticketfields as $value) {
                if (isset($allowed_fields[$value['num']])
                    && (!in_array($allowed_fields[$value['num']], TicketField::$used_fields))) {
                    $value['item'] = $allowed_fields[$value['num']];

                    //Title of father ticket
                    if ($value['item'] == 'name'
                        || $value['item'] == 'impactcontent'
                        || $value['item'] == 'controlistcontent'
                        || $value['item'] == 'rolloutplancontent'
                        || $value['item'] == 'backoutplancontent'
                        || $value['item'] == 'checklistcontent') {
                        do {
                            $match = self::getBetween($value['value'], '[', ']');
                            if (empty($match)) {
                                $explodeTitle = [];
                                $explodeTitle = explode("#", $value['value']);
                                foreach ($explodeTitle as $title) {
                                    if (isset($values['fields'][$title])) {
                                        $field = new Field();
                                        $field->getFromDB($title);
                                        $fields = $field->fields;

                                        $fields['value'] = $values['fields'][$title];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                            && isset($values['fields'][$title . '-2'])) {
                                            $fields['value2'] = $values['fields'][$title . '-2'];
                                        }
                                        $result = [];
                                        $result[$fields['rank']]['content'] = "";
                                        $result[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $v = self::getContentWithField(
                                            [],
                                            0,
                                            $fields,
                                            $result,
                                            $parent_fields_id,
                                            true
                                        );
                                        if ($v != null) {
                                            $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        }
                                    } else {
                                        $explodeTitle2 = explode(".", $title);

                                        if (isset($values['fields'][$explodeTitle2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeTitle2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object"
                                                    && $field_object->fields['item'] == User::getType()) {
                                                    $users_id = $values['fields'][$explodeTitle2[0]];
                                                    $value['value'] = self::getContentForUser(
                                                        $explodeTitle2[1],
                                                        $users_id,
                                                        $_SESSION['glpiactive_entity'],
                                                        $title,
                                                        $value['value']
                                                    );
                                                }
                                            }
                                        }

                                        $users_id = $users_id_requester;

                                        switch ($title) {
                                            case "requester.login":
                                                foreach ($users_id as $usr) {
                                                    $user = new User();
                                                    $user->getFromDB($usr);
                                                    $v = $user->fields['name'];
                                                    $value['value'] = str_replace(
                                                        "#" . $title . "#",
                                                        $v,
                                                        $value['value']
                                                    );
                                                }
                                                break;
                                            case "requester.name":
                                                foreach ($users_id as $usr) {
                                                    $user = new User();
                                                    $user->getFromDB($usr);
                                                    $v = $user->fields['realname'];
                                                    $value['value'] = str_replace(
                                                        "#" . $title . "#",
                                                        $v,
                                                        $value['value']
                                                    );
                                                }
                                                break;
                                            case "requester.firstname":
                                                foreach ($users_id as $usr) {
                                                    $user = new User();
                                                    $user->getFromDB($usr);
                                                    $v = $user->fields['firstname'];
                                                    $value['value'] = str_replace(
                                                        "#" . $title . "#",
                                                        $v,
                                                        $value['value']
                                                    );
                                                }
                                                break;
                                            case "requester.email":
                                                foreach ($users_id as $usr) {
                                                    $user = new UserEmail();
                                                    $user->getFromDBByCrit(['users_id' => $usr, 'is_default' => 1]);
                                                    $v = $user->fields['email'];
                                                    $value['value'] = str_replace(
                                                        "#" . $title . "#",
                                                        $v,
                                                        $value['value']
                                                    );
                                                }
                                                break;
                                            case "entity":
                                                $v = Dropdown::getDropdownName("glpi_entities", $entities_id);
                                                $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                                break;
                                        }
                                    }
                                }
                            } else {
                                $explodeVal = [];
                                $explodeVal = explode("|", $match);
                                $find = false;
                                $val_to_replace = "";
                                foreach ($explodeVal as $str) {
                                    $explodeTitle = explode("#", $str);
                                    foreach ($explodeTitle as $title) {
                                        if (isset($values['fields'][$title])) {
                                            $field = new Field();
                                            $field->getFromDB($title);
                                            $fields = $field->fields;


                                            $fields['value'] = $values['fields'][$title];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                                && isset($values['fields'][$title . '-2'])) {
                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                            }
                                            $result = [];
                                            $result[$fields['rank']]['content'] = "";
                                            $result[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $v = self::getContentWithField(
                                                [],
                                                0,
                                                $fields,
                                                $result,
                                                $parent_fields_id,
                                                true
                                            );
                                            if ($v != null) {
                                                $str = str_replace("#" . $title . "#", $v, $str);
                                            }

                                            if (!is_null($v) && !empty($v)) {
                                                $find = true;
                                            }
                                        } else {
                                            $users_id = $users_id_requester;

                                            switch ($title) {
                                                case "requester.login":
                                                    foreach ($users_id as $usr) {
                                                        $user = new User();
                                                        $user->getFromDB($usr);
                                                        $v = $user->fields['name'];
                                                        $str = str_replace("#" . $title . "#", $v, $str);
                                                    }
                                                    break;
                                                case "requester.name":
                                                    foreach ($users_id as $usr) {
                                                        $user = new User();
                                                        $user->getFromDB($usr);
                                                        $v = $user->fields['realname'];
                                                        $str = str_replace("#" . $title . "#", $v, $str);
                                                    }
                                                    break;
                                                case "requester.firstname":
                                                    foreach ($users_id as $usr) {
                                                        $user = new User();
                                                        $user->getFromDB($usr);
                                                        $v = $user->fields['firstname'];
                                                        $str = str_replace("#" . $title . "#", $v, $str);
                                                    }
                                                    break;
                                                case "requester.email":
                                                    foreach ($users_id as $usr) {
                                                        $user = new UserEmail();
                                                        $user->getFromDBByCrit(['users_id' => $usr, 'is_default' => 1]);
                                                        $v = $user->fields['email'];
                                                        $str = str_replace("#" . $title . "#", $v, $str);
                                                    }
                                                    break;
                                                case "entity":
                                                    $v = Dropdown::getDropdownName("glpi_entities", $entities_id);
                                                    $value['value'] = str_replace(
                                                        "#" . $title . "#",
                                                        $v,
                                                        $value['value']
                                                    );
                                                    break;
                                            }
                                        }
                                    }
                                    if ($find == true) {
                                        break;
                                    }
                                }
                                if (str_contains($match, "#")) {
                                    $value['value'] = str_replace("[" . $match . "]", $str, $value['value']);
                                } else {
                                    $value['value'] = str_replace(
                                        "[" . $match . "]",
                                        "<@" . $str . "@>",
                                        $value['value']
                                    );
                                }
                            }
                        } while (!empty($match));

                        $value['value'] = str_replace("<@", "[", $value['value']);
                        $value['value'] = str_replace("@>", "]", $value['value']);
                        $explodeTitle = [];
                        $explodeTitle = explode("#", $value['value']);
                        foreach ($explodeTitle as $title) {
                            if (isset($values['fields'][$title])) {
                                $field = new Field();
                                $field->getFromDB($title);
                                $fields = $field->fields;


                                $fields['value'] = $values['fields'][$title];

                                $fields['value2'] = '';
                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval')
                                    && isset($values['fields'][$title . '-2'])) {
                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                }
                                $result = [];
                                $result[$fields['rank']]['content'] = "";
                                $result[$fields['rank']]['display'] = false;
                                $parent_fields_id = 0;
                                $v = self::getContentWithField([], 0, $fields, $result, $parent_fields_id, true);
                                if ($v != null) {
                                    $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                }
                            } else {
                                $users_id = $users_id_requester;

                                switch ($title) {
                                    case "requester.login":
                                        foreach ($users_id as $usr) {
                                            $user = new User();
                                            $user->getFromDB($usr);
                                            $v = $user->fields['name'];
                                            $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        }
                                        break;
                                    case "requester.name":
                                        foreach ($users_id as $usr) {
                                            $user = new User();
                                            $user->getFromDB($usr);
                                            $v = $user->fields['realname'];
                                            $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        }
                                        break;
                                    case "requester.firstname":
                                        foreach ($users_id as $usr) {
                                            $user = new User();
                                            $user->getFromDB($usr);
                                            $v = $user->fields['firstname'];
                                            $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        }
                                        break;
                                    case "requester.email":
                                        foreach ($users_id as $usr) {
                                            $user = new UserEmail();
                                            $user->getFromDBByCrit(['users_id' => $usr, 'is_default' => 1]);
                                            $v = $user->fields['email'];
                                            $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        }
                                        break;
                                    case "entity":
                                        $v = Dropdown::getDropdownName("glpi_entities", $entities_id);
                                        $value['value'] = str_replace("#" . $title . "#", $v, $value['value']);
                                        break;
                                }
                            }
                        }

                        $inputs[$value['item']] = self::$PARENT_PREFIX . $value['value'];
                    } else {
                        if ($value['item'] == '_tasktemplates_id') {
                            $inputs[$value['item']] = array_merge(
                                $inputs[$value['item']] ?? [],
                                [json_decode($value['value'], true)]
                            );
                        } else {
                            $inputs[$value['item']] = json_decode($value['value'], true);
                        }
                    }
                }
            }
        }
        if (isset($inputs['_tasktemplates_id']) && is_array($inputs['_tasktemplates_id'])) {
            foreach ($inputs['_tasktemplates_id'] as $id => $valueTT) {
                if (is_null($valueTT)) {
                    unset($inputs['_tasktemplates_id'][$id]);
                }
            }
        }
        return $inputs;
    }

    /**
     * @param array $tickettasks_data
     * @param       $parent_tickets_id
     * @param int $tasklevel
     * @param       $parent_fields
     * @param       $ancestor_tickets_id
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public static function createSonsTickets(
        $metademands_id,
        $parent_tickets_id,
        $parent_fields,
        $ancestor_tickets_id,
        $tickettasks_data = [],
        $tasklevel = 1,
        $inputField = [],
        $inputFieldMain = []
    ) {
        $meta = new Metademand();
        $meta->getFromDB($metademands_id);

        $ticket_ticket = new Ticket_Ticket();
        $ticket_task = new Ticket_Task();
        $task = new Task();
        $ticket = new \Ticket();
        $KO = [];
        $ticketParent = new \Ticket();
        if ($ticketParent->getFromDB($parent_tickets_id)) {
            if (isset($meta->fields['initial_requester_childs_tickets'])
                && $meta->fields['initial_requester_childs_tickets'] == 1) {
                $users = $ticketParent->getUsers(CommonITILActor::REQUESTER);
                if (count($users) > 0) {
                    $parent_fields['_users_id_requester'] = [];
                    foreach ($users as $user) {
                        $parent_fields['_users_id_requester'][] = $user['users_id'];
                        $parent_fields['_actors']['requester'][] = ['itemtype'          => 'User',
                            'items_id'          => $user['users_id'],
                            'use_notification'  => "1",
                            'alternative_email' => ""];
                    }
                }
                $users = $ticketParent->getUsers(CommonITILActor::OBSERVER);
                if (count($users) > 0) {
                    $parent_fields['_users_id_observer'] = [];
                    foreach ($users as $user) {
                        $parent_fields['_users_id_observer'][] = $user['users_id'];
                        $parent_fields['_actors']['observer'][] = ['itemtype'          => 'User',
                            'items_id'          => $user['users_id'],
                            'use_notification'  => "1",
                            'alternative_email' => ""];
                    }
                }
            }
            foreach ($tickettasks_data as $son_ticket_data) {
                if ($son_ticket_data['level'] == $tasklevel) {
                    if (isset($_SESSION['metademands_hide'])
                        && in_array($son_ticket_data['tickettasks_id'], $_SESSION['metademands_hide'])) {
                        continue;
                    }
                    // Skip ticket creation if not allowed by metademand form
                    if (!Ticket_Field::checkTicketCreation(
                        $son_ticket_data['tasks_id'],
                        $ancestor_tickets_id
                    )) {
                        continue;
                    }

                    $tt = $ticket->getITILTemplateToUse(
                        0,
                        $ticketParent->fields['type'],
                        $son_ticket_data['itilcategories_id'],
                        $ticketParent->fields['entities_id']
                    );
                    $predefined_fields = $tt->predefined;

                    unset($predefined_fields['content']);
                    $son_ticket_data = array_merge($son_ticket_data, $predefined_fields);

                    // Field format for ticket
                    foreach ($son_ticket_data as $field => $value) {
                        if (strstr($field, 'groups_id_')
                            || strstr($field, 'users_id_')) {
                            $son_ticket_data['_' . $field] = $son_ticket_data[$field];
                        }
                    }
                    foreach ($parent_fields as $field => $value) {
                        if (strstr($field, 'groups_id_')
                            || strstr($field, 'users_id_')) {
                            $parent_fields['_' . $field] = $parent_fields[$field];
                        }
                    }

                    if (!isset($meta->fields['id'])) {
                        $ticket_meta = new Ticket_Metademand();
                        $ticket_meta->getFromDBByCrit(['tickets_id' => $ancestor_tickets_id]);
                        $meta = new Metademand();
                        $meta->getFromDB($ticket_meta->fields['plugin_metademands_metademands_id']);
                    }

                    $values_form = [];
                    $ticket_field = new Ticket_Field();
                    $fields = $ticket_field->find(['tickets_id' => $ancestor_tickets_id]);

                    foreach ($fields as $f) {
                        $values_form[$f['plugin_metademands_fields_id']] = json_decode($f['value']);
                        if ($values_form[$f['plugin_metademands_fields_id']] === null) {
                            $values_form[$f['plugin_metademands_fields_id']] = $f['value'];
                        }
                        if (!empty($f['value2'])) {
                            $values_form[$f['plugin_metademands_fields_id'] . '-2'] = json_decode($f['value2']);
                            if ($values_form[$f['plugin_metademands_fields_id'] . '-2'] === null) {
                                $values_form[$f['plugin_metademands_fields_id'] . '-2'] = $f['value2'];
                            }
                        }
                    }
                    $metademands_data = self::constructMetademands($meta->getID());

                    $son_ticket_data['users_id_recipient'] = $parent_fields['users_id_recipient'] ?? 0;

                    if (!$son_ticket_data['_users_id_requester']
                        && isset($meta->fields['initial_requester_childs_tickets'])
                        && $meta->fields['initial_requester_childs_tickets'] == 1) {
                        $son_ticket_data['_users_id_requester'] = $parent_fields['_users_id_requester'] ?? 0;
                        $son_ticket_data['_users_id_observer'] = $parent_fields['_users_id_observer'] ?? 0;
                        //                        $son_ticket_data['_actors'] = isset($parent_fields['_actors']) ? $parent_fields['_actors'] : 0;
                    }

                    if (count($metademands_data)) {
                        //copy for use it after
                        $values = $values_form;
                        foreach ($metademands_data as $form_step => $data) {
                            foreach ($data as $form_metademands_id => $line) {
                                $list_fields = $line['form'];
                                $searchOption = Search::getOptions('Ticket');
                                if ($task->getFromDB($son_ticket_data['tasks_id'])) {
                                    if (isset($task->fields['useBlock']) && $task->fields['useBlock'] == 1) {
                                        $blocks = json_decode($task->fields["block_use"], true);
                                        if (!empty($blocks)) {
                                            foreach ($line['form'] as $i => $l) {
                                                if (!in_array($l['rank'], $blocks)) {
                                                    unset($line['form'][$i]);
                                                    unset($values_form[$i]);
                                                }
                                            }
                                            $parent_fields_content = self::formatFields(
                                                $line['form'],
                                                $meta->getID(),
                                                [$values_form],
                                                ['formatastable' => $task->fields['formatastable']]
                                            );
                                        } else {
                                            $parent_fields_content['content'] = $parent_fields['content'];
                                        }
                                    }

                                    foreach ($list_fields as $id => $fields_values) {
                                        $params = [];
                                        $field = new Field();
                                        if ($field->getFromDB($id)) {
                                            $params = Field::getAllParamsFromField($field);
                                        }

                                        if ($params['used_by_ticket'] > 0 && $params['used_by_child'] == 1) {
                                            if (isset($values[$id])) {
                                                $name = $searchOption[$params['used_by_ticket']]['linkfield'];

                                                if ($values[$id] > 0 && $params['used_by_ticket'] == 4) {
                                                    $name = "_users_id_requester";
                                                    if (is_array($values[$id])) {
                                                        foreach ($values[$id] as $usr) {
                                                            $son_ticket_data[$name][] = $usr;
                                                        }
                                                    } else {
                                                        if (!is_array($son_ticket_data[$name])) {
                                                            $value = $son_ticket_data[$name];
                                                            $son_ticket_data[$name] = [$value];
                                                        }
                                                        if (!empty($values[$id])) {
                                                            $son_ticket_data[$name][] = $values[$id];
                                                        }
                                                    }
                                                }
                                                if ($params['used_by_ticket'] == 71) {
                                                    $name = "_groups_id_requester";
                                                    if (is_array($values[$id])) {
                                                        foreach ($values[$id] as $usr) {
                                                            $son_ticket_data[$name][] = $usr;
                                                        }
                                                    } else {
                                                        if (!is_array($son_ticket_data[$name])) {
                                                            $value = $son_ticket_data[$name];
                                                            $son_ticket_data[$name] = [$value];
                                                        }
                                                        if (!empty($values[$id])) {
                                                            $son_ticket_data[$name][] = $values[$id];
                                                        }
                                                    }
                                                }
                                                if ($params['used_by_ticket'] == 66) {
                                                    $name = "_users_id_observer";
                                                    if (is_array($values[$id])) {
                                                        foreach ($values[$id] as $usr) {
                                                            $son_ticket_data[$name][] = $usr;
                                                        }
                                                    } else {
                                                        if (!is_array($son_ticket_data[$name])) {
                                                            $value = $son_ticket_data[$name];
                                                            $son_ticket_data[$name] = [$value];
                                                        }
                                                        if (!empty($values[$id])) {
                                                            $son_ticket_data[$name][] = $values[$id];
                                                        }
                                                    }
                                                }
                                                if ($params['used_by_ticket'] == 65) {
                                                    $name = "_groups_id_observer";
                                                    if (is_array($values[$id])) {
                                                        foreach ($values[$id] as $usr) {
                                                            $son_ticket_data[$name][] = $usr;
                                                        }
                                                    } else {
                                                        if (!is_array($son_ticket_data[$name])) {
                                                            $value = $son_ticket_data[$name];
                                                            $son_ticket_data[$name] = [$value];
                                                        }
                                                        if (!empty($values[$id])) {
                                                            $son_ticket_data[$name][] = $values[$id];
                                                        }
                                                    }
                                                }
                                                if ($params['used_by_ticket'] != 4
                                                    && $params['used_by_ticket'] != 71
                                                    && $params['used_by_ticket'] != 66
                                                    && $params['used_by_ticket'] != 65) {
                                                    $son_ticket_data[$name] = $values[$id];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Add son ticket
                    //                $son_ticket_data['_disablenotif']      = true;
                    $son_ticket_data['name'] = self::$SON_PREFIX . $son_ticket_data['tickettasks_name'];
                    $son_ticket_data['type'] = $parent_fields['type'];

                    $son_ticket_data['requesttypes_id'] = $parent_fields['requesttypes_id'];
                    $son_ticket_data['_auto_import'] = 1;
                    $son_ticket_data['status'] = \Ticket::INCOMING;
                    if (isset($parent_fields['locations_id'])) {
                        $son_ticket_data['locations_id'] = $parent_fields['locations_id'];
                    }
                    if (isset($parent_fields['urgency'])) {
                        $son_ticket_data['urgency'] = $parent_fields['urgency'];
                    }
                    if (isset($parent_fields['impact'])) {
                        $son_ticket_data['impact'] = $parent_fields['impact'];
                    }
                    if (isset($parent_fields['priority'])) {
                        $son_ticket_data['priority'] = $parent_fields['priority'];
                    }

                    $content = '';
                    $config = new Config();
                    $config->getFromDB(1);

                    if (!empty($son_ticket_data['content'])) {
                        if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                            $content = "<table class='tab_cadre' style='width: 100%;border:0;background:none;word-break: unset;'>";
                            $content .= "<tr><th colspan='2'>" . __('Child Ticket', 'metademands')
                                . "</th></tr><tr><td colspan='2'>";
                        }

                        $content .= RichText::getSafeHtml($son_ticket_data['content']);

                        if (isset($task->fields['formatastable']) && $task->fields['formatastable'] == true) {
                            $content .= "</td></tr></table><br>";
                        }
                    }

                    if ($config->getField('childs_parent_content') == 1
                        && $task->fields['formatastable'] == true) {
                        if (!empty($parent_fields_content['content'])) {
                            //if (!strstr($parent_fields['content'], __('Parent ticket', 'metademands'))) {
                            $content .= "<table class='tab_cadre' style='width: 100%;border:0;background:none;word-break: unset;'><tr><th colspan='2'>";
                            $content .= _n('Parent tickets', 'Parent tickets', 1, 'metademands')
                                . "</th></tr><tr><td colspan='2'>" . RichText::getSafeHtml(
                                    $parent_fields_content['content']
                                );
                            //if (!strstr($parent_fields['content'], __('Parent ticket', 'metademands'))) {
                            $content .= "</td></tr></table><br>";
                            //}
                        }
                    }

                    $son_ticket_data['content'] = $content;

                    if (isset($meta->fields['initial_requester_childs_tickets'])
                        && $meta->fields['initial_requester_childs_tickets'] == 0) {
                        if (isset($parent_fields['_groups_id_assign'])) {
                            //affect by default precedent group
                            $son_ticket_data['_groups_id_requester'] = $parent_fields['_groups_id_assign'];
                        }
                    }
                    $son_ticket_data = self::mergeFields($son_ticket_data, $inputFieldMain);

                    if (!isset($son_ticket_data['users_id_recipient']) || empty($son_ticket_data['users_id_recipient'])) {
                        $son_ticket_data['users_id_recipient'] = Session::getLoginUserID();
                    }
                    if (!isset($son_ticket_data['locations_id']) || empty($son_ticket_data['locations_id'])) {
                        $son_ticket_data['locations_id'] = 0;
                    }

                    // check if son ticket already exists in case we come from an update of the parent ticket
                    if ($ticket_task->getFromDBByCrit([
                        'parent_tickets_id' => $parent_tickets_id,
                        'level' => $son_ticket_data['level'],
                        'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id'],
                    ])) {
                        $ticket->update($son_ticket_data + ['id' => $ticket_task->fields['tickets_id']]);
                    } elseif ($son_tickets_id = $ticket->add($son_ticket_data)) {
                        $ticket_metademand = new Ticket_Metademand();
                        if (!$ticket_metademand->getFromDBByCrit([
                            'tickets_id' => $son_tickets_id,
                            'parent_tickets_id' => $parent_tickets_id,
                            'plugin_metademands_metademands_id' => $meta->getID(),
                        ])) {
                            $ticket_metademand->add([
                                'tickets_id' => $son_tickets_id,
                                'parent_tickets_id' => $parent_tickets_id,
                                'plugin_metademands_metademands_id' => $meta->getID(),
                                'status' => Ticket_Metademand::RUNNING,
                            ]);
                        }

                        if (Plugin::isPluginActive('fields')) {
                            $cleaninput = [];
                            foreach ($inputField as $c_id => $c_vals) {
                                foreach ($c_vals as $c_name => $c_val) {
                                    if (!empty($c_val)) {
                                        $cleaninput[$c_id][$c_name] = $c_val;
                                    }
                                }
                            }
                            foreach ($cleaninput as $containers_id => $vals) {

                                $container = new PluginFieldsContainer();
                                $vals['plugin_fields_containers_id'] = $containers_id;
                                $vals['itemtype'] = "Ticket";
                                $vals['items_id'] = $son_tickets_id;
                                $container->updateFieldsValues($vals, "Ticket", false);
                            }
                        }
                        // Add son link to parent
                        $ticket_ticket->add([
                            'tickets_id_1' => $parent_tickets_id,
                            'tickets_id_2' => $son_tickets_id,
                            'link' => Ticket_Ticket::PARENT_OF,
                        ]);

                        // task - ticket relation
                        $ticket_task->add([
                            'tickets_id' => $son_tickets_id,
                            'parent_tickets_id' => $parent_tickets_id,
                            'level' => $son_ticket_data['level'],
                            'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id'],
                        ]);
                    } else {
                        $KO[] = 1;
                    }
                } else {
                    if (isset($_SESSION['metademands_hide'])
                        && in_array($son_ticket_data['tickettasks_id'], $_SESSION['metademands_hide'])) {
                        continue;
                    }
                    // task - ticket relation for next tickets
                    if (!Ticket_Field::checkTicketCreation(
                        $son_ticket_data['tasks_id'],
                        $parent_tickets_id
                    )) {
                        continue;
                    }
                    // TODO  check use of this if
                    if ($ticket_task->find([
                        'parent_tickets_id' => $parent_tickets_id,
                        'level' => $son_ticket_data['level'],
                        'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id'],
                    ])) {
                        $ticket->update($son_ticket_data + ['id' => $ticket_task->fields['tickets_id']]);
                    } else {
                        $ticket_task->add([
                            'tickets_id' => 0,
                            'parent_tickets_id' => $parent_tickets_id,
                            'level' => $son_ticket_data['level'],
                            'plugin_metademands_tasks_id' => $son_ticket_data['tasks_id'],
                        ]);
                    }
                }
            }
        }
        if (count($KO)) {
            return false;
        }

        return true;
    }

    /**
     * @param $tickets_data
     *
     * @throws \GlpitestSQLError
     */
    public function addSonTickets($tickets_data, $ticket_metademand)
    {
        global $DB;

        $ticket_task = new Ticket_Task();
        $ticket = new \Ticket();
        $groups_tickets = new Group_Ticket();
        $users_tickets = new Ticket_User();

        // We can add task if one is not already present for ticket
        $search_ticket = $ticket_task->find(['parent_tickets_id' => $tickets_data['id']]);
        if (!count($search_ticket)) {
            $task = new Task();

            $iterator = $DB->request([
                'SELECT'    => [
                    'glpi_plugin_metademands_tickettasks.*',
                    'glpi_plugin_metademands_tasks.plugin_metademands_metademands_id',
                    'glpi_plugin_metademands_tasks.is AS tasks_id',
                    'glpi_plugin_metademands_tickets_tasks.level AS parent_level',
                ],
                'FROM'      => 'glpi_plugin_metademands_tickettasks',
                'LEFT JOIN'       => [
                    'glpi_plugin_metademands_tasks' => [
                        'ON' => [
                            'glpi_plugin_metademands_tasks' => 'id',
                            'glpi_plugin_metademands_tickettasks'          => 'plugin_metademands_tasks_id'
                        ]
                    ],
                    'glpi_plugin_metademands_tickets_tasks' => [
                        'ON' => [
                            'glpi_plugin_metademands_tasks' => 'id',
                            'glpi_plugin_metademands_tickets_tasks'          => 'plugin_metademands_tasks_id'
                        ]
                    ]
                ],
                'WHERE'     => [
                    'glpi_plugin_metademands_tickets_tasks.tickets_id'  => $tickets_data['id']
                ],
            ]);

            if (count($iterator) > 0) {
                $values = [];
                $ticket_field = new Ticket_Field();
                $ticket_id = Ticket_Task::getFirstTicket($tickets_data['id']);
                $fields = $ticket_field->find(['tickets_id' => $ticket_id]);
                foreach ($fields as $f) {
                    $values['fields'][$f['plugin_metademands_fields_id']] = json_decode($f['value']);
                    if ($values['fields'][$f['plugin_metademands_fields_id']] === null) {
                        $values['fields'][$f['plugin_metademands_fields_id']] = $f['value'];
                    }
                }
                foreach ($iterator as $data) {
                    // If child task exists : son ticket creation
                    $child_tasks_data = $task->getChildrenForLevel($data['tasks_id'], $data['parent_level'] + 1);

                    if ($child_tasks_data) {
                        foreach ($child_tasks_data as $child_tasks_id) {
                            $tasks_data = $task->getTasks(
                                $data['plugin_metademands_metademands_id'],
                                ['condition' => ['glpi_plugin_metademands_tasks.id' => $child_tasks_id]]
                            );

                            // Get parent ticket data
                            $ticket->getFromDB($tickets_data['id']);

                            // Find parent metademand tickets_id and get its _groups_id_assign
                            $tickets_found = Ticket::getAncestorTickets($tickets_data['id'], true);
                            $parent_groups_tickets_data = $groups_tickets->find([
                                'tickets_id' => $tickets_found[0]['tickets_id'],
                                'type' => CommonITILActor::ASSIGN,
                            ]);

                            if (count($parent_groups_tickets_data)) {
                                $parent_groups_tickets_data = reset($parent_groups_tickets_data);
                                $ticket->fields['_groups_id_assign'] = $parent_groups_tickets_data['groups_id'];
                            }
                            $parent_groups_tickets_data = $users_tickets->find([
                                'tickets_id' => $tickets_found[0]['tickets_id'],
                                'type' => CommonITILActor::ASSIGN,
                            ]);
                            $requesters = $users_tickets->find([
                                'tickets_id' => $tickets_found[0]['tickets_id'],
                                'type' => CommonITILActor::REQUESTER,
                            ]);
                            if (!empty($requesters)) {
                                $requester = array_shift($requesters);
                                $parent_fields['_users_id_requester'] = $requester['users_id'];
                            } else {
                                $parent_fields['_users_id_requester'] = Session::getLoginUserID();
                            }

                            if (count($parent_groups_tickets_data)) {
                                $parent_groups_tickets_data = reset($parent_groups_tickets_data);
                                $ticket->fields['_users_id_assign'] = $parent_groups_tickets_data['users_id'];
                            }

                            $l = $tasks_data[$child_tasks_id];
                            do {
                                $match = self::getBetween($l['tickettasks_name'], '[', ']');
                                if (empty($match)) {
                                    $explodeTitle = [];
                                    $explodeTitle = explode("#", $l['tickettasks_name']);
                                    foreach ($explodeTitle as $title) {
                                        if (isset($values['fields'][$title])) {
                                            $field = new Field();
                                            $field->getFromDB($title);
                                            $fields = $field->fields;


                                            $fields['value'] = $values['fields'][$title];

                                            $fields['value2'] = '';
                                            if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                $fields['value2'] = $values['fields'][$title . '-2'];
                                            }
                                            $resultData = [];
                                            $resultData['content'] = "";
                                            $resultData[$fields['rank']]['content'] = "";
                                            $resultData[$fields['rank']]['display'] = false;
                                            $parent_fields_id = 0;
                                            $value = self::getContentWithField(
                                                [],
                                                0,
                                                $fields,
                                                $resultData,
                                                $parent_fields_id,
                                                true
                                            );
                                            $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace(
                                                "#" . $title . "#",
                                                $value,
                                                $tasks_data[$child_tasks_id]['tickettasks_name']
                                            );
                                        } else {
                                            $explodeTitle2 = explode(".", $title);

                                            if (isset($values['fields'][$explodeTitle2[0]])) {
                                                $field_object = new Field();
                                                if ($field_object->getFromDB($explodeTitle2[0])) {
                                                    if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                    )) {
                                                        $users_id = $values['fields'][$explodeTitle2[0]];
                                                        $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser(
                                                            $explodeTitle2[1],
                                                            $users_id,
                                                            $_SESSION['glpiactive_entity'],
                                                            $title,
                                                            $tasks_data[$child_tasks_id]['tickettasks_name']
                                                        );
                                                    }
                                                }
                                            }
                                            $users_id = $parent_fields['_users_id_requester']; // TODO
                                            //                                 $users_id = Session::getLoginUserID(); // TODO
                                            $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser(
                                                $title,
                                                $users_id,
                                                $_SESSION['glpiactive_entity'],
                                                $title,
                                                $tasks_data[$child_tasks_id]['tickettasks_name'],
                                                true
                                            );
                                        }
                                    }
                                } else {
                                    $explodeVal = [];
                                    $explodeVal = explode("|", $match);
                                    $find = false;
                                    $val_to_replace = "";
                                    foreach ($explodeVal as $str) {
                                        $explodeTitle = explode("#", $str);
                                        foreach ($explodeTitle as $title) {
                                            if (isset($values['fields'][$title])) {
                                                $field = new Field();
                                                $field->getFromDB($title);
                                                $fields = $field->fields;


                                                $fields['value'] = $values['fields'][$title];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$title . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$title . '-2'];
                                                }
                                                $resultData = [];
                                                $resultData['content'] = "";
                                                $resultData[$fields['rank']]['content'] = "";
                                                $resultData[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField(
                                                    [],
                                                    0,
                                                    $fields,
                                                    $resultData,
                                                    $parent_fields_id,
                                                    true
                                                );
                                                $str = str_replace("#" . $title . "#", $value, $str);
                                                if (!is_null($value) && !empty($value)) {
                                                    $find = true;
                                                }
                                            } else {
                                                $explodeTitle2 = explode(".", $title);

                                                if (isset($values['fields'][$explodeTitle2[0]])) {
                                                    $field_object = new Field();
                                                    if ($field_object->getFromDB($explodeTitle2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                        )) {
                                                            $users_id = $values['fields'][$explodeTitle2[0]];
                                                            $str = self::getContentForUser(
                                                                $explodeTitle2[1],
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $title,
                                                                $str
                                                            );
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $str = self::getContentForUser(
                                                    $explodeTitle2[1],
                                                    $users_id,
                                                    $_SESSION['glpiactive_entity'],
                                                    $title,
                                                    $str
                                                );
                                            }
                                        }
                                        if ($find == true) {
                                            break;
                                        }
                                    }

                                    if (str_contains($match, "#")) {
                                        $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace(
                                            "[" . $match . "]",
                                            $str,
                                            $tasks_data[$child_tasks_id]['tickettasks_name']
                                        );
                                        $l['tickettasks_name'] = str_replace(
                                            "[" . $match . "]",
                                            $str,
                                            $l['tickettasks_name']
                                        );
                                    } else {
                                        $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace(
                                            "[" . $match . "]",
                                            "<@" . $str . "@>",
                                            $tasks_data[$child_tasks_id]['tickettasks_name']
                                        );
                                        $l['tickettasks_name'] = str_replace(
                                            "[" . $match . "]",
                                            "<@" . $str . "@>",
                                            $l['tickettasks_name']
                                        );
                                    }
                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                }
                            } while (!empty($match));

                            $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace(
                                "<@",
                                "[",
                                $tasks_data[$child_tasks_id]['tickettasks_name']
                            );
                            $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace(
                                "@>",
                                "]",
                                $tasks_data[$child_tasks_id]['tickettasks_name']
                            );
                            $l['tickettasks_name'] = str_replace("<@", "[", $l['tickettasks_name']);
                            $l['tickettasks_name'] = str_replace("@>", "]", $l['tickettasks_name']);

                            $explodeTitle = explode("#", $l['tickettasks_name']);
                            foreach ($explodeTitle as $title) {
                                if (isset($values['fields'][$title])) {
                                    $field = new Field();
                                    $field->getFromDB($title);
                                    $fields = $field->fields;


                                    $fields['value'] = $values['fields'][$title];

                                    $fields['value2'] = '';
                                    if (($fields['type'] == 'date_interval'
                                            || $fields['type'] == 'datetime_interval')
                                        && isset($values['fields'][$title . '-2'])) {
                                        $fields['value2'] = $values['fields'][$title . '-2'];
                                    }
                                    $resultData = [];
                                    $resultData['content'] = "";
                                    $resultData[$fields['rank']]['content'] = "";
                                    $resultData[$fields['rank']]['display'] = false;
                                    $parent_fields_id = 0;
                                    $value = self::getContentWithField(
                                        [],
                                        0,
                                        $fields,
                                        $resultData,
                                        $parent_fields_id,
                                        true
                                    );
                                    $tasks_data[$child_tasks_id]['tickettasks_name'] = str_replace(
                                        "#" . $title . "#",
                                        $value,
                                        $tasks_data[$child_tasks_id]['tickettasks_name']
                                    );
                                } else {
                                    $explodeTitle2 = explode(".", $title);

                                    if (isset($values['fields'][$explodeTitle2[0]])) {
                                        $field_object = new Field();
                                        if ($field_object->getFromDB($explodeTitle2[0])) {
                                            if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                            )) {
                                                $users_id = $values['fields'][$explodeTitle2[0]];
                                                $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser(
                                                    $explodeTitle2[1],
                                                    $users_id,
                                                    $_SESSION['glpiactive_entity'],
                                                    $title,
                                                    $tasks_data[$child_tasks_id]['tickettasks_name']
                                                );
                                            }
                                        }
                                    }
                                    $users_id = $parent_fields['_users_id_requester'];
                                    $tasks_data[$child_tasks_id]['tickettasks_name'] = self::getContentForUser(
                                        $title,
                                        $users_id,
                                        $_SESSION['glpiactive_entity'],
                                        $title,
                                        $tasks_data[$child_tasks_id]['tickettasks_name'],
                                        true
                                    );
                                }
                            }

                            //replace #id# in content with the value
                            do {
                                $match = self::getBetween($l['content'], '[', ']');
                                if (empty($match)) {
                                    if ($l['content'] != null) {
                                        $l['content'] = RichText::getTextFromHtml($l['content']);
                                        $explodeContent = explode("#", $l['content']);
                                        foreach ($explodeContent as $content) {
                                            //                                            $field_object = new Field();
                                            //                                            if ($field_object->getFromDB($content)) {
                                            //                                                if ($field_object->fields['type'] == "informations") {
                                            //                                                    $values['fields'][$content] = $field_object->fields['label2'];
                                            //                                                }
                                            //                                            }
                                            if (isset($values['fields'][$content])) {
                                                $field = new Field();
                                                $field->getFromDB($content);
                                                $fields = $field->fields;


                                                $fields['value'] = $values['fields'][$content];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$content . '-2'];
                                                }
                                                $resultData = [];
                                                $resultData['content'] = "";
                                                $resultData[$fields['rank']]['content'] = "";
                                                $resultData[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField(
                                                    [],
                                                    0,
                                                    $fields,
                                                    $resultData,
                                                    $parent_fields_id,
                                                    true
                                                );
                                                $tasks_data[$child_tasks_id]['content'] = str_replace(
                                                    "#" . $content . "#",
                                                    $value,
                                                    $tasks_data[$child_tasks_id]['content']
                                                );
                                            } else {
                                                $explodeContent2 = explode(".", $content);

                                                if (isset($values['fields'][$explodeContent2[0]])) {
                                                    $field_object = new Field();
                                                    if ($field_object->getFromDB($explodeContent2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                        )) {
                                                            $users_id = $values['fields'][$explodeContent2[0]];
                                                            $tasks_data[$child_tasks_id]['content'] = self::getContentForUser(
                                                                $explodeContent2[1],
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $content,
                                                                $tasks_data[$child_tasks_id]['content']
                                                            );
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $tasks_data[$child_tasks_id]['content'] = self::getContentForUser(
                                                    $content,
                                                    $users_id,
                                                    $_SESSION['glpiactive_entity'],
                                                    $content,
                                                    $tasks_data[$child_tasks_id]['content'],
                                                    true
                                                );
                                            }
                                        }
                                    }
                                } else {
                                    $explodeVal = [];
                                    $explodeVal = explode("|", $match);
                                    $find = false;
                                    $val_to_replace = "";
                                    foreach ($explodeVal as $str) {
                                        $explodeContent = explode("#", $str);
                                        foreach ($explodeContent as $content) {
                                            if (isset($values['fields'][$content])) {
                                                $field = new Field();
                                                $field->getFromDB($content);
                                                $fields = $field->fields;


                                                $fields['value'] = $values['fields'][$content];

                                                $fields['value2'] = '';
                                                if (($fields['type'] == 'date_interval' || $fields['type'] == 'datetime_interval') && isset($values['fields'][$content . '-2'])) {
                                                    $fields['value2'] = $values['fields'][$content . '-2'];
                                                }
                                                $resultData = [];
                                                $resultData['content'] = "";
                                                $resultData[$fields['rank']]['content'] = "";
                                                $resultData[$fields['rank']]['display'] = false;
                                                $parent_fields_id = 0;
                                                $value = self::getContentWithField(
                                                    [],
                                                    0,
                                                    $fields,
                                                    $resultData,
                                                    $parent_fields_id,
                                                    true
                                                );
                                                $str = str_replace("#" . $content . "#", $value, $str);
                                                if (!is_null($value) && !empty($value)) {
                                                    $find = true;
                                                }
                                            } else {
                                                $explodeContent2 = explode(".", $content);

                                                if (isset($values['fields'][$explodeContent2[0]])) {
                                                    $field_object = new Field();
                                                    if ($field_object->getFromDB($explodeContent2[0])) {
                                                        if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                        )) {
                                                            $users_id = $values['fields'][$explodeContent2[0]];
                                                            $str = self::getContentForUser(
                                                                $explodeContent2[1],
                                                                $users_id,
                                                                $_SESSION['glpiactive_entity'],
                                                                $content,
                                                                $str
                                                            );
                                                        }
                                                    }
                                                }
                                                $users_id = $parent_fields['_users_id_requester'];
                                                $str = self::getContentForUser(
                                                    $content,
                                                    $users_id,
                                                    $_SESSION['glpiactive_entity'],
                                                    $content,
                                                    $str,
                                                    true
                                                );
                                            }
                                        }
                                        if ($find == true) {
                                            break;
                                        }
                                    }
                                    //                                    $tasks_data[$child_tasks_id]['content'] = str_replace("[" . $match . "]", $str, $tasks_data[$child_tasks_id]['content']);
                                    if (str_contains($match, "#")) {
                                        $tasks_data[$child_tasks_id]['content'] = str_replace(
                                            "[" . $match . "]",
                                            $str,
                                            $tasks_data[$child_tasks_id]['content']
                                        );
                                        $l['content'] = str_replace("[" . $match . "]", $str, $l['content']);
                                    } else {
                                        $tasks_data[$child_tasks_id]['content'] = str_replace(
                                            "[" . $match . "]",
                                            "<@" . $str . "@>",
                                            $tasks_data[$child_tasks_id]['content']
                                        );
                                        $l['content'] = str_replace(
                                            "[" . $match . "]",
                                            "<@" . $str . "@>",
                                            $l['content']
                                        );
                                    }
                                    //                                    $value['value'] = str_replace("[".$match."]", $str,  $value['value']);
                                }
                            } while (!empty($match));

                            if ($tasks_data[$child_tasks_id]['content'] != null) {
                                $tasks_data[$child_tasks_id]['content'] = str_replace(
                                    "<@",
                                    "[",
                                    $tasks_data[$child_tasks_id]['content']
                                );
                                $tasks_data[$child_tasks_id]['content'] = str_replace(
                                    "@>",
                                    "]",
                                    $tasks_data[$child_tasks_id]['content']
                                );
                            }
                            if ($l['content'] != null) {
                                $l['content'] = str_replace("<@", "[", $l['content']);
                                $l['content'] = str_replace("@>", "]", $l['content']);

                                $l['content'] = RichText::getTextFromHtml($l['content']);
                                $explodeContent = explode("#", $l['content']);
                                foreach ($explodeContent as $content) {
                                    //                                    $field_object = new Field();
                                    //                                    if ($field_object->getFromDB($content)) {
                                    //                                        if ($field_object->fields['type'] == "informations") {
                                    //                                            $values['fields'][$content] = $field_object->fields['label2'];
                                    //                                        }
                                    //                                    }
                                    if (isset($values['fields'][$content])) {
                                        $field = new Field();
                                        $field->getFromDB($content);
                                        $fields = $field->fields;


                                        $fields['value'] = $values['fields'][$content];

                                        $fields['value2'] = '';
                                        if (($fields['type'] == 'date_interval'
                                                || $fields['type'] == 'datetime_interval')
                                            && isset($values['fields'][$content . '-2'])) {
                                            $fields['value2'] = $values['fields'][$content . '-2'];
                                        }
                                        $resultData = [];
                                        $resultData['content'] = "";
                                        $resultData[$fields['rank']]['content'] = "";
                                        $resultData[$fields['rank']]['display'] = false;
                                        $parent_fields_id = 0;
                                        $value = self::getContentWithField(
                                            [],
                                            0,
                                            $fields,
                                            $resultData,
                                            $parent_fields_id,
                                            true
                                        );
                                        $tasks_data[$child_tasks_id]['content'] = str_replace(
                                            "#" . $content . "#",
                                            $value,
                                            $tasks_data[$child_tasks_id]['content']
                                        );
                                    } else {
                                        $explodeContent2 = explode(".", $content);

                                        if (isset($values['fields'][$explodeContent2[0]])) {
                                            $field_object = new Field();
                                            if ($field_object->getFromDB($explodeContent2[0])) {
                                                if ($field_object->fields['type'] == "dropdown_object" && $field_object->fields['item'] == User::getType(
                                                )) {
                                                    $users_id = $values['fields'][$explodeContent2[0]];
                                                    $tasks_data[$child_tasks_id]['content'] = self::getContentForUser(
                                                        $explodeContent2[1],
                                                        $users_id,
                                                        $_SESSION['glpiactive_entity'],
                                                        $content,
                                                        $tasks_data[$child_tasks_id]['content']
                                                    );
                                                }
                                            }
                                        }
                                        $users_id = $parent_fields['_users_id_requester'];
                                        $tasks_data[$child_tasks_id]['content'] = self::getContentForUser(
                                            $content,
                                            $users_id,
                                            $_SESSION['glpiactive_entity'],
                                            $content,
                                            $tasks_data[$child_tasks_id]['content'],
                                            true
                                        );
                                    }
                                }
                            }
                            //childs sons
                            self::createSonsTickets(
                                $ticket_metademand->fields['plugin_metademands_metademands_id'],
                                $tickets_data['id'],
                                $ticket->fields,
                                $tickets_found[0]['tickets_id'],
                                $tasks_data,
                                $data['parent_level'] + 1
                            );
                        }
                    }
                }
            } else {
                if (count($ticket_metademand->fields) > 0) {
                    $ticket_metademand->update([
                        'id' => $ticket_metademand->getID(),
                        'status' => Ticket_Metademand::CLOSED,
                    ]);
                }
            }
        }
    }

    /**
     * @param $ticket
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showPluginForTicket($ticket)
    {
        if (!$this->canView()) {
            return false;
        }
        $tovalidate = 0;
        $metaValidation = new MetademandValidation();
        if ($metaValidation->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])
            && ($metaValidation->fields['validate'] == MetademandValidation::TO_VALIDATE
                || $metaValidation->fields['validate'] == MetademandValidation::TO_VALIDATE_WITHOUTTASK)
            && Session::haveRight('plugin_metademands', READ)
            && Session::getCurrentInterface() == 'central') {
            $tovalidate = 1;

            echo "<div class='alert center'>";
            echo __('Metademand need a validation', 'metademands');
            echo "<br>";
            echo __('Do you want to validate her?', 'metademands');
            $style = "btn-orange";
            echo "<a class='btn primary answer-action $style' data-bs-toggle='modal' data-bs-target='#metavalidation'>"
                . "<i class='ti ti-thumb-up' style='margin-left: 10px;'></i>" . __(
                    'Metademand validation',
                    'metademands'
                ) . "</a>";

            echo Ajax::createIframeModalWindow(
                'metavalidation',
                PLUGIN_METADEMANDS_WEBDIR . '/front/metademandvalidation.form.php?tickets_id=' . $ticket->fields['id'],
                [
                    'title' => __('Metademand validation', 'metademands'),
                    'display' => false,
                    'width' => 200,
                    'height' => 400,
                    'reloadonclose' => true,
                ]
            );

            echo "</div>";


            $sons = json_decode($metaValidation->fields['tickets_to_create'], true);
            if (is_array($sons)) {
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='left b' colspan='4'>" . __(
                    'List of tickets / tasks which be created after validation',
                    'metademands'
                ) . "</th>";
                echo "</tr>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='center b'>" . __('Name') . "</th>";
                echo "<th class='center b'>" . __('Type') . "</th>";
                echo "<th class='center b'>" . __('Category') . "</th>";
                echo "<th class='center b'>" . __('Assigned to') . "</th>";
                echo "</tr>";
                foreach ($sons as $son) {
                    if (Ticket_Field::checkTicketCreation($son['tasks_id'], $ticket->fields['id'])) {
                        echo "<tr class='tab_bg_1'>";
                        if ($son['type'] == Task::TICKET_TYPE
                            || $son['type'] == Task::TASK_TYPE) {
                            $color_class = '';
                        } else {
                            $color_class = "class='metademand_metademandtasks'";
                        }

                        echo "<td $color_class>" . urldecode($son['tickettasks_name']) . "</td>";

                        // Type
                        echo "<td $color_class>" . Task::getTaskTypeName($son['type']) . "</td>";

                        $cat = "";
                        if ($son['type'] == Task::TICKET_TYPE
                            && isset($son['itilcategories_id'])
                            && $son['itilcategories_id'] > 0) {
                            $cat = Dropdown::getDropdownName("glpi_itilcategories", $son['itilcategories_id']);
                        }
                        echo "<td $color_class>";
                        echo $cat;
                        echo "</td>";

                        //assign
                        $techdata = "";
                        if ($son['type'] == Task::TICKET_TYPE
                            || $son['type'] == Task::TASK_TYPE) {
                            if (isset($son['users_id_assign'])
                                && $son['users_id_assign'] > 0) {
                                $techdata .= getUserName($son['users_id_assign'], 0, true);
                                $techdata .= "<br>";
                            }
                            if (isset($son['groups_id_assign'])
                                && $son['groups_id_assign'] > 0) {
                                $techdata .= Dropdown::getDropdownName("glpi_groups", $son['groups_id_assign']);
                            }
                        }
                        echo "<td $color_class>";
                        echo $techdata;
                        echo "</td>";

                        echo "</tr>";
                    }
                }
                echo "</table>";
            }
        }

        Ticket_Metademand::changeMetademandGlobalStatus($ticket);

        $ticket_metademand = new Ticket_Metademand();
        $ticket_metademand_data = $ticket_metademand->find(['parent_tickets_id' => $ticket->fields['id']]);
        $tickets_founded = [];

        // If ticket is Parent : Check if all sons ticket are closed
        if (count($ticket_metademand_data)) {
            $ticket_metademand_data = reset($ticket_metademand_data);
            $tickets_founded = Ticket::getSonTickets(
                $ticket->fields['id'],
                $ticket_metademand_data['plugin_metademands_metademands_id'],
                [],
                true,
                true
            );
        } else {
            $ticket_task = new Ticket_Task();
            $ticket_task_data = $ticket_task->find(['tickets_id' => $ticket->fields['id']]);

            if (count($ticket_task_data)) {
                $tickets_founded = Ticket::getAncestorTickets($ticket->fields['id'], true);
            }
        }
        $tickets_list = [];
        $tickets_next = [];
        $parent_ticket = false;
        if ($tovalidate == 0) {
            if (is_array($tickets_founded)
                && count($tickets_founded)) {

                if (isset($tickets_founded[0]['parent_tickets_id']) && $tickets_founded[0]['parent_tickets_id'] > 0) {
                    $parent_ticket = true;
                }
                if ($parent_ticket == true) {
                    $metaStatus = new Ticket_Metademand();
                    $style = '';

                    if ($metaStatus->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])) {
                        if (in_array($metaStatus->fields['status'], [Ticket_Metademand::TO_CLOSED])) {
                            $icon = "ti ti-circle-check";
                            $icon_color = "forestgreen";
                        }

                        if (in_array($metaStatus->fields['status'], [Ticket_Metademand::CLOSED])) {
                            $icon = "ti ti-circle-check";
                            $icon_color = "black";
                        }

                        if (in_array($metaStatus->fields['status'], [Ticket_Metademand::RUNNING])) {
                            $icon = "ti ti-clock";
                            $icon_color = "orange";
                        }
                        $style = 'background-color: white;border-color:' . $icon_color;
                    }
                    echo "<br><div style='display:flex;align-items: center;$style' class='center alert alert-dismissible fade show informations'>";

                    if ($metaStatus->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])) {
                        echo "<div style='margin-right: 20px;'>";
                        echo "<i class='$icon' style='vertical-align: top;font-size:2em;color:$icon_color'></i> ";
                        echo "</div>";
                    }

                    echo __('Demand followup', 'metademands');

                    if ($metaStatus->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])) {
                        echo " - " . Ticket_Metademand::getStatusName($metaStatus->fields['status']);
                    }
                    echo "</div>";
                }
                foreach ($tickets_founded as $tickets) {
                    if (!empty($tickets['tickets_id'])) {
                        $tickets_list[] = $tickets;
                    } else {
                        if (isset($tickets['tickets_id']) && $tickets['tickets_id'] == 0) {
                            $tickets_next[] = $tickets;
                        }
                    }
                }

                if (count($tickets_list)) {
                    echo "<div class='center'><table class='tab_cadre_fixe'>";
                    echo "<tr class='center'>";
                    $title = __('Parent ticket', 'metademands');
                    if ($parent_ticket == true) {
                        $title = __('Existing childs tickets', 'metademands');
                    }
                    echo "<td colspan='7'><h3>" . $title . "</h3></td></tr>";

                    echo "<tr>";
                    echo "<th>" . __('Ticket') . "</th>";
                    echo "<th>" . __('Entity') . "</th>";
                    echo "<th>" . __('Opening date') . "</th>";
                    if (Session::getCurrentInterface() == 'central') {
                        echo "<th>" . __('Assigned to') . "</th>";
                    }
                    echo "<th>" . __('Status') . "</th>";
                    if (Session::getCurrentInterface() == 'central') {
                        echo "<th>" . __('Due date', 'metademands') . "</th>";
                        echo "<th>" . __('Status') . " " . __('SLA') . "</th>";
                    }
                    echo "</tr>";
                    $status = [\Ticket::SOLVED, \Ticket::CLOSED];

                    foreach ($tickets_list as $values) {
                        $color_class = '';
                        // Get ticket values if it exists
                        $childticket = new \Ticket();
                        $childticket->getFromDB($values['tickets_id']);

                        // SLA State
                        $sla_state = Dropdown::EMPTY_VALUE;
                        $is_late = false;
                        switch ($this->checkSlaState($values)) {
                            case self::SLA_FINISHED:
                                $sla_state = __('Task completed.');
                                break;
                            case self::SLA_LATE:
                                $is_late = true;
                                $color_class = "metademand_metademandfollowup_red";
                                $sla_state = __('Late');
                                break;
                            case self::SLA_PLANNED:
                                $sla_state = __('Processing');
                                break;
                            case self::SLA_TODO:
                                $sla_state = __('To do');
                                $color_class = "metademand_metademandfollowup_yellow";
                                break;
                        }

                        $closed = '';
                        if (in_array($childticket->fields['status'], [\Ticket::CLOSED])) {
                            $closed = 'closedchild';
                        }
                        echo "<tr class='tab_bg_1 $closed'>";
                        echo "<td>";
                        // Name
                        if ($values['type'] == Task::TICKET_TYPE) {
                            if ($values['level'] > 1) {
                                $width = (20 * $values['level']);
                                echo "<div style='margin-left:" . $width . "px' class='metademands_tree'></div>";
                            }
                        }

                        if (!empty($values['tickets_id'])) {
                            echo "<a href='" . Toolbox::getItemTypeFormURL('Ticket')
                                . "?id=" . $childticket->fields['id'] . "&glpi_tab=Ticket$" . 'main' . "'>" . $childticket->fields['name'] . "</a>";
                        } else {
                            echo self::$SON_PREFIX . $values['tasks_name'];
                        }

                        echo "</td>";

                        // Entity
                        echo "<td>";
                        echo Dropdown::getDropdownName("glpi_entities", $childticket->fields['entities_id']);
                        echo "</td>";

                        //date
                        echo "<td>";
                        echo Html::convDateTime($childticket->fields['date']);
                        echo "</td>";

                        //group
                        if (Session::getCurrentInterface() == 'central') {
                            $techdata = '';
                            if ($childticket->countUsers(CommonITILActor::ASSIGN)) {
                                foreach ($childticket->getUsers(CommonITILActor::ASSIGN) as $u) {
                                    $k = $u['users_id'];
                                    if ($k) {
                                        $techdata .= getUserName($k);
                                    }

                                    if ($childticket->countUsers(CommonITILActor::ASSIGN) > 1) {
                                        $techdata .= "<br>";
                                    }
                                }
                                $techdata .= "<br>";
                            }

                            if ($childticket->countGroups(CommonITILActor::ASSIGN)) {
                                foreach ($childticket->getGroups(CommonITILActor::ASSIGN) as $u) {
                                    $k = $u['groups_id'];
                                    if ($k) {
                                        $techdata .= Dropdown::getDropdownName("glpi_groups", $k);
                                    }

                                    if ($childticket->countGroups(CommonITILActor::ASSIGN) > 1) {
                                        $techdata .= "<br>";
                                    }
                                }
                            }
                            echo "<td>";
                            echo $techdata;
                            echo "</td>";
                        }
                        //status
                        echo "<td class='center'>";
                        if (in_array($childticket->fields['status'], [\Ticket::SOLVED])) {
                            echo "<i class='ti ti-circle-check' style='font-size:2em;color:forestgreen'></i> ";
                        }

                        if (in_array($childticket->fields['status'], [\Ticket::CLOSED])) {
                            echo "<i class='ti ti-circle-check' style='font-size:2em;color:black'></i> ";
                        }

                        if (!in_array($childticket->fields['status'], $status)) {
                            echo "<i class='ti ti-clock' style='font-size:2em;color:orange'></i> ";
                        }
                        echo \Ticket::getStatus($childticket->fields['status']);
                        echo "</td>";

                        //due date
                        if (Session::getCurrentInterface() == 'central') {
                            echo "<td class='$color_class'>";
                            if ($is_late && !in_array($childticket->fields['status'], $status)) {
                                echo "<i class='ti ti-alert-triangle' style='font-size:2em;color:darkred'></i>";
                            }
                            echo Html::convDateTime($childticket->fields['time_to_resolve']);
                            echo "</td>";

                            //sla state
                            echo "<td>";
                            echo $sla_state;
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table></div>";
                }

                if (count($tickets_next) && Session::getCurrentInterface() == 'central') {
                    $color_class = "metademand_metademandfollowup_grey";
                    echo "<div class='center'><table class='tab_cadre_fixe'>";
                    echo "<tr class='center'>";
                    echo "<td colspan='6'><h3>" . __('Next tickets', 'metademands') . "</h3></td></tr>";

                    echo "<tr>";
                    echo "<th>" . __('Ticket') . "</th>";
                    echo "<th>" . __('Opening date') . "</th>";
                    if (Session::getCurrentInterface() == 'central') {
                        echo "<th>" . __('Assigned to') . "</th>";
                    }
                    echo "<th>" . __('Status') . "</th>";
                    if (Session::getCurrentInterface() == 'central') {
                        echo "<th>" . __('Due date', 'metademands') . "</th>";
                        echo "<th>" . __('Status') . " " . __('SLA') . "</th>";
                    }
                    echo "</tr>";

                    foreach ($tickets_next as $values) {
                        if (isset($values['tickets_id']) && $values['tickets_id'] > 0) {
                            continue;
                        }

                        $childticket->getEmpty();

                        // SLA State
                        $sla_state = Dropdown::EMPTY_VALUE;

                        echo "<tr class='tab_bg_1'>";
                        echo "<td class='$color_class'>";
                        // Name
                        if ($values['type'] == Task::TICKET_TYPE) {
                            if ($values['level'] > 1) {
                                $width = (20 * $values['level']);
                                echo "<div style='margin-left:" . $width . "px' class='metademands_tree'></div>";
                            }
                        }

                        if (!empty($values['tickets_id'])) {
                            echo "<a href='" . Toolbox::getItemTypeFormURL('Ticket')
                                . "?id=" . $childticket->fields['id'] . "'>" . $childticket->fields['name'] . "</a>";
                        } else {
                            $task = new Task();
                            $task->getFromDB($values['tasks_id']);
                            echo self::$SON_PREFIX . $task->getName();
                        }

                        echo "</td>";

                        //date
                        echo "<td class='$color_class'>";
                        echo Html::convDateTime($childticket->fields['date']);
                        echo "</td>";

                        //group
                        if (Session::getCurrentInterface() == 'central') {
                            $techdata = '';
                            if ($childticket->countUsers(CommonITILActor::ASSIGN)) {
                                foreach ($childticket->getUsers(CommonITILActor::ASSIGN) as $u) {
                                    $k = $u['users_id'];
                                    if ($k) {
                                        $techdata .= getUserName($k);
                                    }

                                    if ($childticket->countUsers(CommonITILActor::ASSIGN) > 1) {
                                        $techdata .= "<br>";
                                    }
                                }
                                $techdata .= "<br>";
                            }

                            if ($childticket->countGroups(CommonITILActor::ASSIGN)) {
                                foreach ($childticket->getGroups(CommonITILActor::ASSIGN) as $u) {
                                    $k = $u['groups_id'];
                                    if ($k) {
                                        $techdata .= Dropdown::getDropdownName("glpi_groups", $k);
                                    }

                                    if ($childticket->countGroups(CommonITILActor::ASSIGN) > 1) {
                                        $techdata .= "<br>";
                                    }
                                }
                            }
                            echo "<td class='$color_class'>";
                            echo "</td>";
                        }
                        //status
                        echo "<td class='$color_class center'>";
                        echo "<i class='fas fa-hourglass-half fa-2x'></i> ";
                        echo __('Coming', 'metademands');

                        echo "</td>";

                        if (Session::getCurrentInterface() == 'central') {
                            //due date
                            echo "<td class='$color_class'>";
                            echo Html::convDateTime($childticket->fields['time_to_resolve']);
                            echo "</td>";

                            //sla state
                            echo "<td class='$color_class'>";
                            echo $sla_state;
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table></div>";
                }
            } else {
                echo "<div class='alert  alert-info center'>";
                echo __('There is no childs tickets', 'metademands');
                echo "</div>";
            }
        }
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function executeDuplicate($options = [])
    {
        if (isset($options['metademands_id'])) {
            $metademands_id = $options['metademands_id'];

            $fields = new Field();
            $fieldoptions = new FieldOption();
            $fieldparameters = new FieldParameter();
            $fieldcustoms = new FieldCustomvalue();
            $fieldfreetables = new Freetablefield();
            $fieldcondition = new Condition();
            $ticketfields = new TicketField();
            $tasks = new Task();
            $groups = new Group();
            $tickettasks = new TicketTask();
            $mailtasks = new MailTask();
            $metademandtasks = new MetademandTask();

            // Add the new metademand
            $this->getFromDB($metademands_id);
            unset($this->fields['id']);
            unset($this->fields['itilcategories_id']);

            //TODO To translate ?
            if ($this->fields['comment'] != null) {
                $this->fields['comment'] = addslashes($this->fields['comment']);
            }
            $this->fields['name'] = addslashes($this->fields['name']);

            if ($new_metademands_id = $this->add($this->fields)) {
                $translationMeta = new MetademandTranslation();
                $translationsMeta = $translationMeta->find(
                    ['itemtype' => Metademand::class, "items_id" => $metademands_id]
                );
                foreach ($translationsMeta as $tr) {
                    $translationMeta->getFromDB($tr['id']);
                    $translationMeta->clone(["items_id" => $new_metademands_id]);
                }
                $metademands_data = self::constructMetademands($metademands_id);

                if (count($metademands_data)) {
                    $associated_fields = [];
                    $associated_oldfields = [];
                    $associated_tasks = [];
                    foreach ($metademands_data as $form_step => $data) {
                        foreach ($data as $form_metademands_id => $line) {
                            if (count($line['form'])) {
                                if ($form_metademands_id == $metademands_id) {
                                    // Add metademand fields
                                    foreach ($line['form'] as $values) {
                                        $input = [];
                                        $id = $values['id'];
                                        unset($values['id']);
                                        $input['type'] = $values['type'];
                                        $input['item'] = $values['item'];
                                        $input['rank'] = $values['rank'];
                                        $input['order'] = $values['order'];
                                        $input['entities_id'] = $values['entities_id'];
                                        $input['is_recursive'] = $values['is_recursive'];
                                        $input['plugin_metademands_metademands_id'] = $new_metademands_id;
                                        if (!empty($values['name'])) {
                                            $input['name'] = addslashes($values['name']);
                                        }
                                        if (!empty($values['label2'])) {
                                            $input['label2'] = addslashes($values['label2']);
                                        }
                                        if (!empty($values['comment'])) {
                                            $input['comment'] = addslashes($values['comment']);
                                        }

                                        $newID = $fields->add($input);
                                        $associated_oldfields[$id] = $newID;

                                        $associated_fields[$newID] = $id;

                                        $translation = new FieldTranslation();
                                        $translations = $translation->find(
                                            ['itemtype' => Field::class, "items_id" => $id]
                                        );
                                        foreach ($translations as $tr) {
                                            $translation->getFromDB($tr['id']);
                                            $translation->clone(["items_id" => $newID]);
                                        }
                                    }

                                    // Add metademand group
                                    $groups_data = $groups->find(
                                        ['plugin_metademands_metademands_id' => $metademands_id]
                                    );
                                    if (count($groups_data)) {
                                        foreach ($groups_data as $values) {
                                            unset($values['id']);
                                            $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                                            $groups->add($values);
                                        }
                                    }
                                }
                            }

                            // Add tasks
                            if (count($line['tasks']) && $form_metademands_id == $metademands_id) {
                                $parent_tasks = [];
                                foreach ($line['tasks'] as $values) {
                                    $tasks->getFromDB($values['tasks_id']);
                                    if (array_key_exists($values['parent_task'], $parent_tasks)) {
                                        $tasks->fields['plugin_metademands_tasks_id'] = $parent_tasks[$values['parent_task']];
                                    }
                                    $tasks->fields['plugin_metademands_metademands_id'] = $new_metademands_id;
                                    $tasks->fields['sons_cache'] = '';
                                    $tasks->fields['ancestors_cache'] = '';
                                    if (isset($tasks->fields['name'])) {
                                        $tasks->fields['name'] = addslashes($tasks->fields['name']);
                                    }
                                    if (isset($tasks->fields['completename'])) {
                                        $tasks->fields['completename'] = addslashes($tasks->fields['completename']);
                                    }
                                    if (isset($tasks->fields['comment'])) {
                                        $tasks->fields['comment'] = addslashes($tasks->fields['comment']);
                                    }

                                    unset($tasks->fields['id']);

                                    $new_tasks_id = $tasks->add($tasks->fields);
                                    $associated_tasks[$values['tasks_id']] = $new_tasks_id;
                                    $parent_tasks[$values['tasks_id']] = $new_tasks_id;

                                    // Ticket tasks
                                    if ($values['type'] == Task::TICKET_TYPE) {
                                        $tickettasks_data = $tickettasks->find(
                                            ['plugin_metademands_tasks_id' => $values['tasks_id']]
                                        );
                                        if (count($tickettasks_data)) {
                                            foreach ($tickettasks_data as $values) {
                                                unset($values['id']);
                                                $values['plugin_metademands_tasks_id'] = $new_tasks_id;
                                                $values['content'] = addslashes($values['content']);
                                                $tickettasks->add($values);
                                            }
                                        }
                                    }
                                    if ($values['type'] == Task::MAIL_TYPE) {
                                        $mailtasks_data = $mailtasks->find(
                                            ['plugin_metademands_tasks_id' => $values['tasks_id']]
                                        );
                                        if (count($mailtasks_data)) {
                                            foreach ($mailtasks_data as $values) {
                                                unset($values['id']);
                                                $values['plugin_metademands_tasks_id'] = $new_tasks_id;
                                                if (!empty($values['content'])) {
                                                    $values['content'] = addslashes($values['content']);
                                                }
                                                if (!empty($values['email'])) {
                                                    $values['email'] = addslashes($values['email']);
                                                }
                                                $mailtasks->add($values);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $associated_fields[0] = 0;
                $associated_oldfields[0] = 0;
                $associated_tasks[0] = 0;

                $mapTableCheckValue = [];
                // duplicate metademand task
                $tasks_data = $tasks->find([
                    'plugin_metademands_metademands_id' => $metademands_id,
                    'type' => Task::METADEMAND_TYPE,
                ]);
                if (count($tasks_data)) {
                    foreach ($tasks_data as $values) {
                        $metademandtasks_data = $metademandtasks->find(
                            ['plugin_metademands_tasks_id' => $values['id']]
                        );
                        $id = $values['id'];
                        unset($values['id']);
                        $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                        $new_tasks_id = $tasks->add($values);
                        $associated_tasks[$id] = $new_tasks_id;
                        if (count($metademandtasks_data)) {
                            foreach ($metademandtasks_data as $data) {
                                $metademandtasks->add([
                                    'plugin_metademands_metademands_id' => $data['plugin_metademands_metademands_id'],
                                    'plugin_metademands_tasks_id' => $new_tasks_id,
                                ]);
                            }
                        }
                    }
                }

                $newFields = $fields->find(['plugin_metademands_metademands_id' => $new_metademands_id]);
                foreach ($newFields as $newField) {
                    $old_field_id = $associated_fields[$newField["id"]];
                    $oldParams = $fieldparameters->find(['plugin_metademands_fields_id' => $old_field_id]);
                    foreach ($oldParams as $oldParam) {
                        $input = [];
                        $input['custom'] = $oldParam['custom'];
                        $input['default'] = $oldParam['default'];
                        $input['hide_title'] = $oldParam['hide_title'];
                        $input['is_mandatory'] = $oldParam['is_mandatory'];
                        $input['max_upload'] = $oldParam['max_upload'];
                        $input['regex'] = $oldParam['regex'];
                        $input['color'] = $oldParam['color'];
                        $input['row_display'] = $oldParam['row_display'];
                        $input['is_basket'] = $oldParam['is_basket'];
                        $input['display_type'] = $oldParam['display_type'];
                        $input['used_by_ticket'] = $oldParam['used_by_ticket'];
                        $input['used_by_child'] = $oldParam['used_by_child'];
                        $input['link_to_user'] = $oldParam['link_to_user'];
                        $input['default_use_id_requester'] = $oldParam['default_use_id_requester'];
                        $input['default_use_id_requester_supervisor'] = $oldParam['default_use_id_requester_supervisor'];
                        $input['use_future_date'] = $oldParam['use_future_date'];
                        $input['use_date_now'] = $oldParam['use_date_now'];
                        $input['additional_number_day'] = $oldParam['additional_number_day'];
                        $input['informations_to_display'] = $oldParam['informations_to_display'];
                        $input['use_richtext'] = $oldParam['use_richtext'];
                        $input['icon'] = $oldParam['icon'];
                        $input['readonly'] = $oldParam['readonly'];
                        $input['hidden'] = $oldParam['hidden'];
                        $input['authldaps_id'] = $oldParam['authldaps_id'];
                        $input['ldap_attribute'] = $oldParam['ldap_attribute'];
                        $input['ldap_filter'] = $oldParam['ldap_filter'];
                        $input['plugin_metademands_fields_id'] = $newField['id'];
                        $fieldparameters->add($input);
                    }

                    $old_field_id = $associated_fields[$newField["id"]];
                    $oldCustoms = $fieldcustoms->find(['plugin_metademands_fields_id' => $old_field_id]);
                    foreach ($oldCustoms as $oldCustom) {
                        $inputc = [];
                        $inputc['name'] = $oldCustom['name'];
                        $inputc['is_default'] = $oldCustom['is_default'];
                        $inputc['comment'] = $oldCustom['comment'];
                        $inputc['rank'] = $oldCustom['rank'];
                        $inputc['plugin_metademands_fields_id'] = $newField['id'];

                        $newcustomfield = $fieldcustoms->add($inputc);
                        $mapTableCheckValue[$oldCustom["id"]] = $newcustomfield;
                    }

                    $old_field_id = $associated_fields[$newField["id"]];
                    $oldFreeTables = $fieldfreetables->find(['plugin_metademands_fields_id' => $old_field_id]);
                    foreach ($oldFreeTables as $oldFreeTable) {
                        $inputf = [];
                        $inputf['name'] = $oldFreeTable['name'];
                        $inputf['internal_name'] = $oldFreeTable['internal_name'];
                        $inputf['type'] = $oldFreeTable['type'];
                        $inputf['is_mandatory'] = $oldFreeTable['is_mandatory'];
                        $inputf['comment'] = $oldFreeTable['comment'];
                        $inputf['dropdown_values'] = $oldFreeTable['dropdown_values'];
                        $inputf['rank'] = $oldFreeTable['rank'];
                        $inputf['plugin_metademands_fields_id'] = $newField['id'];

                        $newfreetablefield = $fieldfreetables->add($inputf);
                        $mapTableCheckValue[$oldFreeTable["id"]] = $newfreetablefield;
                    }

                    $old_field_id = $associated_fields[$newField["id"]];
                    $oldOptions = $fieldoptions->find(['plugin_metademands_fields_id' => $old_field_id]);
                    foreach ($oldOptions as $oldOption) {
                        $inputo = [];
                        $inputo['plugin_metademands_tasks_id'] = $associated_tasks[$oldOption['plugin_metademands_tasks_id']] ?? 0;
                        $inputo['fields_link'] = $associated_oldfields[$oldOption['fields_link']] ?? 0;
                        $inputo['hidden_link'] = $associated_oldfields[$oldOption['hidden_link']] ?? 0;
                        $inputo['hidden_block'] = $oldOption['hidden_block'];
                        $inputo['users_id_validate'] = $oldOption['users_id_validate'];
                        $inputo['childs_blocks'] = $oldOption['childs_blocks'];
                        $inputo['checkbox_value'] = $oldOption['checkbox_value'];
                        $inputo['checkbox_id'] = $oldOption['checkbox_id'];
                        $inputo['plugin_metademands_fields_id'] = $newField['id'];

                        $check_value = $oldOption["check_value"] ?? 0;
                        if ($check_value != 0
                            && isset($mapTableCheckValue[$check_value])) {
                            $inputo['check_value'] = $mapTableCheckValue[$check_value];
                        }

                        $fieldoptions->add($inputo);
                    }

                    $old_field_id = $associated_fields[$newField["id"]];
                    $oldConds = $fieldcondition->find(['plugin_metademands_fields_id' => $old_field_id]);

                    foreach ($oldConds as $oldCond) {
                        $input = [];
                        $input['type'] = $oldCond['type'];
                        $input['order'] = $oldCond['order'];
                        $input['show_condition'] = $oldCond['show_condition'];
                        $input['show_logic'] = $oldCond['show_logic'];
                        if (isset($oldCond["check_value"]) && is_numeric($oldCond["check_value"])) {
                            $check_value = $oldCond["check_value"] ?? 0;
                            if ($check_value != 0
                                && isset($mapTableCheckValue[$check_value])) {
                                $input['check_value'] = $mapTableCheckValue[$check_value];
                            }
                        } elseif (isset($oldCond["check_value"]) && is_string($oldCond["check_value"])) {
                            $input['check_value'] = $oldCond["check_value"];
                        } else {
                            $input['check_value'] = 0;
                        }
                        $input['item'] = $oldCond['item'];
                        $input['items_id'] = $oldCond['items_id'];
                        $input['plugin_metademands_metademands_id'] = $new_metademands_id;
                        $input['plugin_metademands_fields_id'] = $newField['id'];
                        $fieldcondition->add($input);
                    }
                }
                // Add ticket fields
                //                $ticketfields_data = $ticketfields->find(['plugin_metademands_metademands_id' => $metademands_id]);
                //                if (count($ticketfields_data)) {
                //                    foreach ($ticketfields_data as $values) {
                //                        unset($values['id']);
                //                        $values['plugin_metademands_metademands_id'] = $new_metademands_id;
                //                        $values['value'] = addslashes($values['value']);
                //                        $ticketfields->add($values);
                //                    }
                //                }

                // Redirect on finish
                if (isset($options['redirect'])) {
                    Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/metademand.form.php?id=" . $new_metademands_id);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * @param $values
     *
     * @return int
     */
    public function checkSlaState($values)
    {
        $ticket = new \Ticket();
        $status = [\Ticket::SOLVED, \Ticket::CLOSED];

        $notcreated = false;
        // Get ticket values if it exists
        if (!empty($values['tickets_id'])) {
            $ticket->getFromDB($values['tickets_id']);
        } else {
            $notcreated = true;
            $ticket->getEmpty();
        }

        // SLA State
        if (!$notcreated) {
            if ((!empty($ticket->fields['time_to_resolve'])
                    && ($ticket->fields['solvedate'] > $ticket->fields['time_to_resolve'])
                    || (!empty($ticket->fields['time_to_resolve']) && (strtotime(
                        $ticket->fields['time_to_resolve']
                    ) < time())))
                && !in_array($ticket->fields['status'], $status)
            ) {
                $sla_state = self::SLA_LATE;
            } else {
                if (!in_array($ticket->fields['status'], $status)
                    && $ticket->fields['time_to_resolve'] != null
                    && $ticket->fields['date'] != null) {
                    $total_time = (strtotime($ticket->fields['time_to_resolve']) - strtotime($ticket->fields['date']));
                    $current_time = $total_time - (strtotime($ticket->fields['time_to_resolve']) - time());

                    if ($total_time > 0) {
                        $time_percent = $current_time * 100 / $total_time;
                    } else {
                        $time_percent = 100;
                    }

                    if (!empty($ticket->fields['time_to_resolve']) && $time_percent > 75) {
                        $sla_state = self::SLA_TODO;
                    } else {
                        $sla_state = self::SLA_PLANNED;
                    }
                } else {
                    $sla_state = self::SLA_NOTCREATED;
                }
            }
        } else {
            $sla_state = self::SLA_NOTCREATED;
        }

        return $sla_state;
    }

    /**
     * Get the specific massive actions
     *
     * @param null $checkitem link item to check right   (default NULL)
     *
     * @return array array of massive actions
     * *@since version 0.84
     */
    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);
        if ($isadmin) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate'] = _sx('button', 'Duplicate');
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'exportXML'] = __('Export XML', 'metademands');
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'exportJSON'] = __(
                'Export JSON',
                'metademands'
            );
        }

        return $actions;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     *
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'duplicate':
                echo "&nbsp;"
                    . Html::submit(__('Validate'), ['name' => 'massiveaction']);
                return true;
            case 'exportXML':
            case 'exportJSON':
                if (extension_loaded('zip')) {
                    $items = $_POST['items'][__CLASS__];

                    $url = PLUGIN_METADEMANDS_WEBDIR . "/ajax/export_metademand.php";
                    $data = json_encode($items);
                    $action = $ma->getAction();
                    echo "&nbsp;";
                    echo "<button id='export_metademand' class='btn'>" . __(
                        'Start the download',
                        'metademands'
                    ) . "</button>";
                    echo "<br><small class='text-danger'><i class='fa fa-exclamation-triangle' aria-hidden='true'></i> " . __(
                        'This action may take some time depending on the number of selected metademands',
                        'metademands'
                    ) . "</small>";
                    // download done through ajax & POST request to avoid request length restriction from GET request
                    echo "<script>
                        $(document).ready(function() {
                            $('#export_metademand').on('click', function(e) {
                                e.preventDefault();
                                const buttonExport = document.getElementById('export_metademand');
                                buttonExport.style.display = 'none';
                                const spinner = document.createElement('i');
                                spinner.classList = 'fas fa-3x fa-spinner fa-pulse m-1'
                                buttonExport.parentElement.prepend(spinner);
                                $.ajax({
                                    url: '$url',
                                    type: 'POST',
                                    data: { metademands : $data, action : '$action' },
                                    xhrFields: {
                                        responseType: 'blob'
                                    },
                                    success: function(blob, status, xhr) {
                                            let url = window.URL.createObjectURL(blob);
                                            let link = document.createElement('a');
                                            link.href = url;

                                            const contentDisposition = xhr.getResponseHeader('Content-Disposition');
                                            let filename = 'export_' + new Date().toISOString().slice(0, 10) + '.zip';

                                            if (contentDisposition) {
                                                let matches = contentDisposition.match(/filename[^;=\\n]*=((['\"]).*?\\2|[^;\\n]*)/);
                                                if (matches != null && matches[1]) {
                                                    filename = matches[1].replace(/['\"]/g, '');
                                                }
                                            }

                                            link.download = filename;
                                            document.body.appendChild(link);
                                            link.click();
                                            buttonExport.parentElement.removeChild(spinner);
                                            buttonExport.removeAttribute('style');
                                            window.URL.revokeObjectURL(url);
                                            document.body.removeChild(link);
                                    }
                                })
                            })
                        })
                    </script>";
                    return true;
                }
                echo __('This action requires PHP extension zip', 'metademands');
                return false;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM $item
     * @param array $ids
     *
     * @return void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'duplicate':
                if (__CLASS__ == $item->getType()) {
                    foreach ($ids as $key) {
                        if ($item->can($key, UPDATE)) {
                            if ($item->executeDuplicate(['metademands_id' => $key])) {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                            }
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * @return array
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        $forbidden[] = 'merge';
        $forbidden[] = 'clone';
        return $forbidden;
    }


    public function displayHeader()
    {
        Html::header(
            __('Configure demands', 'metademands'),
            '',
            "helpdesk",
            Metademand::class,
            "metademand"
        );
    }

    /**
     * Action after ticket creation with metademands
     *
     * @param $plug
     */
    public static function getPluginAfterCreateTicket($plug, $params)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            if (Plugin::isPluginActive($plug)) {
                $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

                foreach ($pluginclasses as $pluginclass) {
                    if (!class_exists($pluginclass)) {
                        continue;
                    }
                    $form[$pluginclass] = [];
                    $item = $dbu->getItemForItemtype($pluginclass);
                    if ($item && is_callable([$item, 'afterCreateTicket'])) {
                        $item->afterCreateTicket($params);
                    }
                }
            }
        }
    }

    /**
     * Returns the translation of the field
     *
     * @param $id
     * @param  $field
     * @param string $lang
     *
     * @return
     * @global  $DB
     */
    public static function displayField($id, $field, $lang = '')
    {
        global $DB;

        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_metademands_metademandtranslations',
            'WHERE' => [
                'itemtype' => self::getType(),
                'items_id' => $id,
                'field' => $field,
                'language' => $_SESSION['glpilanguage'],
            ],
        ]);

        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                'FROM' => 'glpi_plugin_metademands_metademandtranslations',
                'WHERE' => [
                    'itemtype' => self::getType(),
                    'items_id' => $id,
                    'field' => $field,
                    'language' => $lang,
                ],
            ]);
        }


        if (count($iterator)) {
            foreach ($iterator as $data) {
                $res = $data['value'];
            }
        }
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            if (count($iterator2)) {
                foreach ($iterator2 as $data2) {
                    $res .= ' / ' . $data2['value'];
                    $iterator2->next();
                }
            }
        }
        return $res;
    }


    public static function getRunningMetademands(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => __("Running metademands", 'metademands'),
            'icon' => Menu::getIcon(),
            'apply_filters' => [],
        ];

        $get_running_parents_tickets_meta = [
            'SELECT' => ['COUNT' => 'glpi_plugin_metademands_tickets_metademands.id AS total_running'],
            'FROM' => 'glpi_plugin_metademands_tickets_metademands',
            'LEFT JOIN'       => [
                'glpi_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_plugin_metademands_tickets_metademands'          => 'tickets_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_tickets.is_deleted' => 0,
                'glpi_tickets.status' => ['NOT IN', [\Ticket::SOLVED, \Ticket::CLOSED]],
                'glpi_plugin_metademands_tickets_metademands.status' => Ticket_Metademand::RUNNING,
            ],
        ];
        $get_running_parents_tickets_meta['WHERE'] = $get_running_parents_tickets_meta['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_tickets'
            );

        $total_running_parents_meta = $DB->request($get_running_parents_tickets_meta);

        $total_running = 0;
        foreach ($total_running_parents_meta as $row) {
            $total_running = $row['total_running'];
        }


        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 9500, // status
                    'searchtype' => 'equals',
                    'value' => Ticket_Metademand::RUNNING,
                ],
                [
                    'link'       => 'AND',
                    'field'      => 12, // status
                    'searchtype' => 'equals',
                    'value'      => 'notold',
                ],
            ],
            'reset' => 'reset',
        ];

        $url = \Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);


        return [
            'number' => $total_running,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }

    public static function getRunningMetademandsAndMygroups(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => __("Running metademands with tickets of my groups", "metademands"),
            'icon' => Menu::getIcon(),
            'apply_filters' => [],
        ];

        $get_running_parents_tickets_meta  = [
            'SELECT' => ['COUNT' => 'glpi_plugin_metademands_tickets_metademands.id AS total_running'],
            'DISTINCT'        => true,
            'FROM' => 'glpi_tickets',
            'LEFT JOIN'       => [
                'glpi_plugin_metademands_tickets_metademands' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_plugin_metademands_tickets_metademands'          => 'tickets_id'
                    ]
                ],
                'glpi_plugin_metademands_tickets_tasks' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_plugin_metademands_tickets_tasks'          => 'parent_tickets_id'
                    ]
                ],
                'glpi_groups_tickets AS glpi_groups_tickets_metademands' => [
                    'ON' => [
                        'glpi_plugin_metademands_tickets_tasks' => 'tickets_id',
                        'glpi_groups_tickets_metademands'          => 'tickets_id'
                    ]
                ],
                'glpi_groups AS glpi_groups_metademands' => [
                    'ON' => [
                        'glpi_groups_tickets_metademands' => 'groups_id',
                        'glpi_groups_metademands'          => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_tickets.is_deleted' => 0,
                'glpi_plugin_metademands_tickets_metademands.status' => Ticket_Metademand::RUNNING,
                'glpi_groups_metademands.id' => $_SESSION['glpigroups'],
            ],
        ];
        $get_running_parents_tickets_meta['WHERE'] = $get_running_parents_tickets_meta['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_tickets'
            );

        $total_running_parents_meta = $DB->request($get_running_parents_tickets_meta);

        $total_running = 0;
        foreach ($total_running_parents_meta as $row) {
            $total_running = $row['total_running'];
        }


        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 9500, // metademand status
                    'searchtype' => 'equals',
                    'value' => Ticket_Metademand::RUNNING,
                ],
                [
                    'link' => 'AND',
                    'field' => 9502, // group
                    'searchtype' => 'equals',
                    'value' => "mygroups",
                ],
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => "notold",
                ],
            ],
            'reset' => 'reset',
        ];

        $url = \Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);


        return [
            'number' => $total_running,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }


    public static function getMetademandsToBeClosed(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label' => __("Metademands to be closed", 'metademands'),
            'icon' => Menu::getIcon(),
            'apply_filters' => [],
        ];

        $get_closed_parents_tickets_meta = [
            'SELECT' => ['COUNT' => 'glpi_plugin_metademands_tickets_metademands.id AS total_to_closed'],
            'FROM' => 'glpi_plugin_metademands_tickets_metademands',
            'LEFT JOIN'       => [
                'glpi_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_plugin_metademands_tickets_metademands'          => 'tickets_id'
                    ]
                ],
                'glpi_plugin_metademands_metademandvalidations' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_plugin_metademands_metademandvalidations'          => 'tickets_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_tickets.is_deleted' => 0,
                'glpi_tickets.status' => ['NOT IN', [\Ticket::SOLVED, \Ticket::CLOSED]],
                'glpi_plugin_metademands_tickets_metademands.status' => Ticket_Metademand::TO_CLOSED,
                'glpi_plugin_metademands_metademandvalidations.validate' => [MetademandValidation::TICKET_CREATION],
            ],
        ];
        $get_closed_parents_tickets_meta['WHERE'] = $get_closed_parents_tickets_meta['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_tickets'
            );

        $results_closed_parents = $DB->request($get_closed_parents_tickets_meta);

        $total_closed = 0;
        foreach ($results_closed_parents as $row) {
            $total_closed = $row['total_to_closed'];
        }


        $s_criteria = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => 9500, // status
                    'searchtype' => 'equals',
                    'value' => Ticket_Metademand::TO_CLOSED,
                ],
                [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => "notold",
                ],
                [
                    'link' => 'AND',
                    'field' => 9501, // validation
                    'searchtype' => 'equals',
                    'value' => MetademandValidation::TICKET_CREATION,
                ],
            ],
            'reset' => 'reset',
        ];

        $url = \Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);

        return [
            'number' => $total_closed,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }

    public static function getMetademandsToBeValidated(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $dbu = new DbUtils();

        $default_params = [
            'label' => __("Metademands to be validated", 'metademands'),
            'icon' => Menu::getIcon(),
            'apply_filters' => [],
        ];

        $get_to_validated_meta = [
            'SELECT' => ['COUNT' => 'glpi_plugin_metademands_metademandvalidations.id AS total_to_validated'],
            'FROM' => 'glpi_plugin_metademands_metademandvalidations',
            'LEFT JOIN'       => [
                'glpi_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_plugin_metademands_metademandvalidations'          => 'tickets_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_tickets.is_deleted' => 0,
                'glpi_tickets.status' => ['NOT IN', [\Ticket::SOLVED, \Ticket::CLOSED]],
                'glpi_plugin_metademands_metademandvalidations.validate' => [MetademandValidation::TO_VALIDATE, MetademandValidation::TO_VALIDATE_WITHOUTTASK],
            ],
        ];
        $get_to_validated_meta['WHERE'] = $get_to_validated_meta['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_tickets'
            );

        $results_meta_to_validated = $DB->request($get_to_validated_meta);

        $total_to_validated = 0;
        foreach ($results_meta_to_validated as $row) {
            $total_to_validated = $row['total_to_validated'];
        }


        $s_criteria = [
            'criteria' => [
                0 => [
                    'link' => 'AND',
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => "notold",
                ],
                [
                    'link' => 'AND',
                    'criteria' => [
                        [
                            'link' => 'AND',
                            'field' => 9501, // validation status
                            'searchtype' => 'equals',
                            'value' => MetademandValidation::TO_VALIDATE,
                        ],
                        [
                            'link' => 'OR',
                            'field' => 9501, // validation status
                            'searchtype' => 'equals',
                            'value' => MetademandValidation::TO_VALIDATE_WITHOUTTASK,
                        ],
                    ],
                ],
            ],
            'reset' => 'reset',
        ];

        $url = \Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria);

        return [
            'number' => $total_to_validated,
            'url' => $url,
            'label' => $default_params['label'],
            'icon' => $default_params['icon'],
            's_criteria' => $s_criteria,
            'itemtype' => 'Ticket',
        ];
    }


    /**
     * Actions done when item is deleted from the database
     *
     * @return void
     **/
    public function cleanDBonPurge()
    {
        $temp = new MetademandTask();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Task();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Field();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new TicketField();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Ticket_Metademand();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new MetademandTask();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Group();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new GroupConfig();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Metademand_Resource();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Basketline();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new MetademandValidation();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Draft();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Pluginfields();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Form();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Stepform();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Step();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Configstep();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

        $temp = new Condition();
        $temp->deleteByCriteria(['plugin_metademands_metademands_id' => $this->fields['id']]);

    }

    /**
     * @param $id
     **/
    public static function showAvailableTags($id)
    {
        $self = new self();
        $tags = $self->getTags($id);

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th>" . __('Tag') . "</th>
                <th>" . __('Label') . "</th>
            </tr>";
        foreach ($tags as $tag => $values) {
            echo "<tr>
                  <td>#" . $tag . "#</td>
                  <td>" . $values . "</td>
               </tr>";
        }
        echo "</table></div>";
    }


    /** Display Tags available for the metademand $id
     *
     * @param $id
     **/
    public function getTags($id)
    {
        $fields = $this->find(['id' => $id]);
        $res = [];
        foreach ($fields as $field) {
            $res[$field['id']] = $field['name'];
        }

        return $res;
    }



    public static function getBetween($string, $start = "", $end = "")
    {
        if ($string != null && str_contains($string, $start)) { // required if $start not exist in $string
            $startCharCount = strpos($string, $start) + strlen($start);
            $firstSubStr = substr($string, $startCharCount, strlen($string));
            $endCharCount = strpos($firstSubStr, $end);
            if ($endCharCount == 0) {
                $endCharCount = strlen($firstSubStr);
            }
            return substr($firstSubStr, 0, $endCharCount);
        } else {
            return '';
        }
    }

    /**
     * @param       $field
     * @param       $users_id
     * @param       $title
     * @param       $line
     * @param false $bypass
     *
     * @return array|string|string[]
     */
    public static function getContentForUser($field, $users_id, $entities_id, $title, $line, $bypass = false)
    {
        if ($bypass === true && is_numeric($title)) {
            return str_replace("#" . $title . "#", "", $line);
        }
        if (!is_array($users_id)) {
            $users_id = [$users_id];
        }

        switch ($field) {
            case "login":
            case "requester.login":
                foreach ($users_id as $usr) {
                    $user = new User();
                    $user->getFromDB($usr);
                    $value = $user->fields['name'];
                    if ($value != null) {
                        return str_replace("#" . $title . "#", $value, $line);
                    }
                }
                break;
            case "name":
            case "requester.name":
                foreach ($users_id as $usr) {
                    $user = new User();
                    $user->getFromDB($usr);
                    $value = $user->fields['realname'] ?? "";
                    if ($value != null) {
                        return str_replace("#" . $title . "#", $value, $line);
                    }
                }
                break;
            case "firstname":
            case "requester.firstname":
                foreach ($users_id as $usr) {
                    $user = new User();
                    $user->getFromDB($usr);
                    $value = $user->fields['firstname'] ?? "";
                    if ($value != null) {
                        return str_replace("#" . $title . "#", $value, $line);
                    }
                }
                break;
            case "email":
            case "requester.email":
                foreach ($users_id as $usr) {
                    $user = new UserEmail();
                    $user->getFromDBByCrit(['users_id' => $usr, 'is_default' => 1]);
                    $value = $user->fields['email'];
                    if ($value != null) {
                        return str_replace("#" . $title . "#", $value, $line);
                    }
                }
                break;
            case "entity":
                $value = Dropdown::getDropdownName("glpi_entities", $entities_id);
                if ($value != null) {
                    return str_replace("#" . $title . "#", $value, $line);
                }
                break;
        }
        return $line;
    }

    /**
     * @param $state
     *
     * @return string
     */
    public static function getStateItem($state)
    {
        switch ($state) {
            case self::TODO:
                return "<span><i class=\"fas fa-2x fa-hourglass-half\"></i></span>";
            case self::DONE:
                return "<span><i class=\"fas fa-2x fa-check\"></i></span>";
            case self::FAIL:
                return "<span><i class=\"fas fa-2x fa-times\"></i></span>";
        }
        return "";
    }

    public function showProgressionForm($item)
    {

        $tickets_found = [];
        $tickets_next = [];

        $ticket_metademand = new Ticket_Metademand();
        $ticket_metademand_datas = $ticket_metademand->find(['tickets_id' => $item->fields['id']]);

        // If ticket is Parent : Check if all sons ticket are closed
        if (count($ticket_metademand_datas)) {
            $ticket_metademand_datas = reset($ticket_metademand_datas);
            $tickets_found = Ticket::getSonTickets(
                $item->fields['id'],
                $ticket_metademand_datas['plugin_metademands_metademands_id'],
                [],
                true,
                true
            );
        }

        $tickets_existant = [];

        echo Html::css(PLUGIN_METADEMANDS_WEBDIR . "/css/_process-chart.css");
        echo "<div class='row'>";
        echo "<div class='col-12 col-lg-12'>";
        echo "<ul class='process-chart'>";
        echo "<li class='entry-title align-items-center d-flex justify-content-center my-4 pb-6 fs-2 fw-bold'>";
        echo "<i class='ti ti-brand-databricks me-1'></i>";
        echo "<span>" . __('Progression of your demand', 'metademands') . "</span>";
        echo "</li>";

        echo "<li class='entry-point fs-3'>";
        echo "<span class='icon-stack fa-2x'>";
        echo "<i class='ti ti-circle-dashed'></i>";
        echo "<i class='ti ti-calendar' style='font-size: 0.5em;'></i>";
        echo "</span>";
        echo "<span>" . __("Creation date") . " &nbsp;:&nbsp;" . Html::convDateTime($item->fields["date"]) . "</span>";
        echo "</li>";

        if (count($tickets_found)) {
            foreach ($tickets_found as $tickets) {
                if (!empty($tickets['tickets_id'])) {
                    $tickets_existant[] = $tickets;
                } else {
                    $tickets_next[] = $tickets;
                }
            }
            if (count($tickets_existant)) {
                $ticket = new \Ticket();
                foreach ($tickets_existant as $values) {
                    // Get ticket values if it exists
                    $ticket->getFromDB($values['tickets_id']);
                    $fa = "fa-tasks";

                    if (Plugin::isPluginActive("servicecatalog")) {
                        $fa = ServicecatalogCategory::getUsedConfig(
                            "inherit_config",
                            $ticket->fields['itilcategories_id'],
                            'icon'
                        );
                    }

                    $class = '';
                    if (in_array($ticket->fields['status'], [\Ticket::SOLVED, \Ticket::CLOSED])) {
                        $class = 'closedchild';
                    }
                    echo "<li class='step'>";
                    echo "<a class='btn flex-column fs-3 $class' href='" . $ticket->getLinkURL() . "'>";
                    echo "<div class='d-flex align-items-center'>";
                    echo "<i class='fas $fa' style='float: right;'></i>";
                    echo "<span>&nbsp;" . $ticket->getName();
                    echo "</span>";
                    echo "</div>";
                    echo "<div class='text-muted'>";
                    $statusicon = CommonITILObject::getStatusClass($ticket->fields['status']);

                    $dateEnd = (!empty($ticket->fields["solvedate"])) ? __(
                        'Done on',
                        'metademands'
                    ) . " " . Html::convDateTime($ticket->fields["solvedate"]) : __("In progress", 'metademands');
                    echo "<br>";
                    echo "<i class='" . $statusicon . "'></i>&nbsp;";
                    echo $dateEnd;
                    echo "</div>";
                    echo "</a>";
                    echo "</li>";
                }
            }
        }

        //end ticket
        $dateEnd = (!empty($item->fields["solvedate"])) ? Html::convDateTime($item->fields["solvedate"]) : __(
            "Not yet completed",
            'metademands'
        );
        $fa_end = (!empty($item->fields["solvedate"])) ? "ti-check" : "ti-hourglass";

        echo "<li class='end fs-3 '>";
        echo "<span class='icon-stack fa-2x'>";
        echo "<i class='ti ti-circle-dashed'></i>";
        echo "<i class='ti $fa_end' style='font-size: 0.5em;'></i>";
        echo "</span>";
        echo "<span>" . $dateEnd . "</span>";
        echo "</li>";

        echo "</ul><br><br>";
        echo "</div>";
    }

    /**
     * Manage events from js/fuzzysearch.js
     *
     * @param string $action action to switch (should be actually 'getHtml' or 'getList')
     *
     * @return string
     * @since 9.2
     *
     */
    public static function fuzzySearch($action = '', $type = \Ticket::DEMAND_TYPE)
    {
        $title = __("Find a form", "metademands");

        switch ($action) {
            case 'getHtml':
                $placeholder = $title;
                $html = <<<HTML
               <div class="" tabindex="-1" id="mt-fuzzysearch">
                  <div class="">
                     <div class="modal-content">
                        <div class="modal-body" style="padding: 10px;">
                           <input type="text" class="mt-home-trigger-fuzzy form-control" placeholder="{$placeholder}">
                           <input type="hidden" name="meta_type" id="meta_type" value="$type"/>
                           <ul class="results list-group mt-2" style="background: #FFF;"></ul>
                        </div>
                     </div>
                  </div>
               </div>

HTML;
                return $html;

            default:
                $metas = [];
                $metademands = Wizard::selectMetademands(false, "", $type);

                foreach ($metademands as $id => $values) {
                    $meta = new Metademand();
                    if ($meta->getFromDB($id)) {
                        $icon = "fa-share-alt";
                        if (!empty($meta->fields['icon'])) {
                            $icon = $meta->fields['icon'];
                        }
                        if (str_contains($icon, 'fa-')) {
                            $icon = "fas " . $icon;
                        } else {
                            $icon = "ti " . $icon;
                        }
                        if (empty($n = self::displayField($meta->getID(), 'name'))) {
                            $name = $meta->getName();
                        } else {
                            $name = $n;
                        }
                        $comment_meta = "";
                        if (empty($comm = Metademand::displayField(
                            $meta->getID(),
                            'comment'
                        )) && !empty($meta->fields['comment'])) {
                            $comment_meta = $meta->fields['comment'];
                        } elseif (!empty(
                            $comm = Metademand::displayField(
                                $meta->getID(),
                                'comment'
                            ))) {
                            $comment_meta = $comm;
                        }

                        $metas[] = [
                            'title' => $name,
                            'comment' => ($comment_meta != null) ? Html::resume_text(RichText::getTextFromHtml($comment_meta), "50") : "",
                            'icon' => $icon,
                            'url' => PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=" . $id . "&step=2",
                        ];
                    }
                }

                // return the entries to ajax call
                return json_encode($metas);

        }
    }

    /**
     * @param     $target
     * @param int $add
     */
    public function listOfTemplates($target, $add = 0)
    {
        $dbu = new DbUtils();

        $restrict = ["is_template" => 1]
            + $dbu->getEntitiesRestrictCriteria($this->getTable(), '', '', $this->maybeRecursive())
            + ["ORDER" => "name"];

        $templates = $dbu->getAllDataFromTable($this->getTable(), $restrict);

        if (Session::isMultiEntitiesMode()) {
            $colsup = 1;
        } else {
            $colsup = 0;
        }

        echo "<div class='center'><table class='tab_cadre'>";
        if ($add) {
            echo "<tr><th colspan='" . (2 + $colsup) . "'>" . __('Choose a template', 'metademands') . " - " . self::getTypeName(
                2
            ) . "</th>";
        } else {
            echo "<tr><th colspan='" . (2 + $colsup) . "'>" . __('Templates') . " - " . self::getTypeName(2) . "</th>";
        }

        echo "</tr>";
        if ($add) {
            echo "<tr>";
            echo "<td colspan='" . (2 + $colsup) . "' class='center tab_bg_1'>";
            echo "<a href=\"$target?id=-1&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;" . __(
                'Blank Template'
            ) . "&nbsp;&nbsp;&nbsp;</a></td>";
            echo "</tr>";
        }

        foreach ($templates as $template) {
            $templname = $template["template_name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($template["template_name"])) {
                $templname .= "(" . $template["id"] . ")";
            }

            echo "<tr>";
            echo "<td class='center tab_bg_1'>";
            if (!$add) {
                echo "<a href=\"$target?id=" . $template["id"] . "&amp;withtemplate=1\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

                if (Session::isMultiEntitiesMode()) {
                    echo "<td class='center tab_bg_2'>";
                    echo Dropdown::getDropdownName("glpi_entities", $template['entities_id']);
                    echo "</td>";
                }
                echo "<td class='center tab_bg_2'>";
                Html::showSimpleForm(
                    $target,
                    'purge',
                    _x('button', 'Delete permanently'),
                    ['id' => $template["id"], 'withtemplate' => 1],
                );
                echo "</td>";
            } else {
                echo "<a href=\"$target?id=" . $template["id"] . "&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";

                if (Session::isMultiEntitiesMode()) {
                    echo "<td class='center tab_bg_2'>";
                    echo Dropdown::getDropdownName("glpi_entities", $template['entities_id']);
                    echo "</td>";
                }
            }
            echo "</tr>";
        }
        if (!$add) {
            echo "<tr>";
            echo "<td colspan='" . (2 + $colsup) . "' class='tab_bg_2 center'>";
            echo "<b><a href=\"$target?withtemplate=1\">" . __('Add a template') . "</a></b>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }

    /**
     * Get the list of available categories for a given metademand
     * @param $id int metademand id
     * @return array id => completename
     */
    public static function getAvailableItilCategories($id)
    {
        global $PLUGIN_HOOKS;

        $metademand = new self();
        $metademand->getFromDB($id);

        $critMeta = [];
        $critCategory = [];

        if ($metademand->fields['object_to_create'] == 'Ticket') {
            if ($metademand->fields['type']) {
                switch ($metademand->fields['type']) {
                    case \Ticket::INCIDENT_TYPE:
                        $critCategory['is_incident'] = 1;
                        $critMeta['type'] = \Ticket::INCIDENT_TYPE;
                        break;

                    case \Ticket::DEMAND_TYPE:
                        $critCategory['is_request'] = 1;
                        $critMeta['type'] = \Ticket::DEMAND_TYPE;
                        break;
                }
            } else {
                $critCategory = ['is_incident' => 1];
                $critMeta = ['type' => \Ticket::INCIDENT_TYPE];
            }
        } elseif ($metademand->fields['object_to_create'] == 'Problem') {
            $critCategory = ['is_problem' => 1];
            $critMeta = ['object_to_create' => 'Problem'];
        } elseif ($metademand->fields['object_to_create'] == 'Change') {
            $critCategory = ['is_change' => 1];
            $critMeta = ['object_to_create' => 'Change'];
        }
        $critCategory += getEntitiesRestrictCriteria(
            ITILCategory::getTable(),
            'entities_id',
            $_SESSION['glpiactiveentities'],
            true
        );
        if (isset($PLUGIN_HOOKS['metademands'])
            && $metademand->fields['type'] != 1
            && $metademand->fields['type'] != 2) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginObjectItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    $critMeta = ['object_to_create' => $new_fields['object_to_create']];
                    $critCategory = $new_fields['critcategory'];
                }
            }
        }

        $dbu = new DbUtils();

        $critMeta["is_deleted"] = 0;
        $critMeta["is_template"] = 0;
        $critMeta["type"] = $metademand->fields['type'];
        $critMeta += [
            'NOT' => [
                'id' => $id,
            ],
        ];

        $metademands = $dbu->getAllDataFromTable(self::getTable(), $critMeta);

        $usedCategories = [];
        foreach ($metademands as $item) {
            $tempcats = json_decode($item['itilcategories_id'], true);
            if (is_array($tempcats)) {
                foreach ($tempcats as $tempcat) {
                    $usedCategories[] = $tempcat;
                }
            }
        }

        if (!isset($resultat['critcategory']['use_custom_cat'])) {
            $usedCategories = array_unique($usedCategories);
            if (count($usedCategories) > 0) {
                $critCategory += [
                    'NOT' => [
                        'id' => $usedCategories,
                    ],
                ];
            }
            $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $critCategory);
        }

        if (isset($PLUGIN_HOOKS['metademands'])
            && $critMeta["type"] != 1
            && $critMeta["type"] != 2) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_categories = self::checkPluginUniqueItilcategory($plug, $dbu);
                if (Plugin::isPluginActive($plug) && $new_categories != null) {
                    $result = $new_categories;
                }
            }
        }

        $availableCategories = [];
        foreach ($result as $item) {
            $availableCategories[$item['id']] = html_entity_decode($item['completename']);
        }

        return $availableCategories;
    }

    public static function getPluginUniqueDropdown($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getUniqueDropdown'])) {
                    return $item->getUniqueDropdown();
                }
            }
        }
        return false;
    }

    public static function checkPluginUniqueItilcategory($plug, $dbu)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'checkUniqueItilcategory'])) {
                    return $item->checkUniqueItilcategory($dbu);
                }
            }
        }
        return false;
    }

    public static function addPluginObjectItems($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'objectItems'])) {
                    return $item->objectItems();
                }
            }
        }
        return false;
    }

    private static function getPluginObjectType($plug)
    {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();
        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }
                $form[$pluginclass] = [];
                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'getObjectType'])) {
                    return $item->getObjectType();
                }
            }
        }
        return false;
    }

    /**
     * Check if formValue == optionValue while taking into account the differences between field types
     * @param $optionValue int option -> check_value
     * @param $field array array representing a field created by Metademand::constructMetademands
     * @param $option array array representing an option of $field created by Metademand::constructMetademands
     * @param $formValue mixed value for $field sent to the form (null if no value passed)
     * @return boolean
     */
    private static function compareValueToOption($optionValue, $field, $option, $formValue)
    {
        switch ($field['type']) {
            case 'tel':
            case 'email':
            case 'url':
            case 'textarea':
            case 'text':
                // not empty is the only option
                if ($optionValue == 1 && $formValue) {
                    return !trim($formValue);
                }
                return false;
            case 'dropdown_meta':
            case 'radio':
                // not empty ($formValue != 0)
                if ($optionValue == -1) {
                    return $formValue;
                } else {
                    return $optionValue == $formValue;
                }
                // no break
            case 'checkbox':
                // not empty ($formValue != null)
                if ($optionValue == -1) {
                    return $formValue;
                } else {
                    return ($formValue && in_array($optionValue, $formValue));
                }
                // no break
            case 'dropdown':
            case 'dropdown_ldap':
            case 'dropdown_object':
            case 'yesno':
                return $optionValue == $formValue;
            case 'dropdown_multiple':
                return ($formValue && in_array($optionValue, $formValue));
        }
        return false;
    }

    #[Override]
    public function getServiceCatalogLink(): string
    {
        $id = $this->getID();
        return PLUGIN_METADEMANDS_WEBDIR . "/front/wizard.form.php?metademands_id=$id&step=2";
    }

    #[Override]
    public function getServiceCatalogItemTitle(): string
    {
        $name = "";
        if (empty($name = self::displayField($this->getID(), 'name'))) {
            $name =  $this->fields['name'];
        }
        return $name;
    }

    #[Override]
    public function getServiceCatalogItemDescription(): string
    {
        if (empty($comment = self::displayField($this->getID(), 'comment'))) {
            $comment =  $this->fields['comment'] ?? "";
        }
        if (empty($comment) && empty($description = self::displayField($this->getID(), 'description'))) {
            $description =  $this->fields['description'] ?? "";
        }
        return $comment ?? $description ?? "";
    }

    #[Override]
    public function getServiceCatalogItemIllustration(): string
    {
        if ($this->fields['illustration']) {
            return $this->fields['illustration'];
        }
        $category = new Category();
        if ($this->fields['forms_categories_id'] && $category->getFromDB($this->fields['forms_categories_id'])) {
            return $category->fields['illustration'];
        }
        return IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function isServiceCatalogItemPinned(): bool
    {
        return $this->fields['is_pinned'] ?? 0;
    }

    public function getUsageCount(): int
    {
        return $this->fields['usage_count'] ?? 0;
    }

    protected static function incrementFormUsageCount(Metademand $form): void
    {
        global $DB;

        // Note: Using direct DB update prevents race conditions
        $DB->update(
            Metademand::getTable(),
            ['usage_count' => new QueryExpression('usage_count + 1')],
            ['id' => $form->getID()]
        );
    }
}
