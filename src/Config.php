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
use CommonGLPI;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Migration;
use Session;
use Toolbox;

/**
 * Class Config
 */
class Config extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    private static $instance;

    public function __construct()
    {
        global $DB;

        if ($DB->tableExists($this->getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Plugin setup', 'metademands');
    }

    public function getName($options = [])
    {
        return _n('Meta-Demand', 'Meta-Demands', 2, 'metademands');
    }


    public static function getIcon()
    {
        return "ti ti-share";
    }

    /**
     * Whether the current user may read $requested_users_id's profile attributes
     * (location / title / supervisor / tooltip) through the requester-driven form
     * lookups. Mirrors the entity boundary of the requester dropdown itself
     * (Dropdownobject: right 'all' scoped to $_SESSION['glpiactiveentities']), so a
     * user building a form for someone else keeps working, while enumeration of every
     * user's PII by an arbitrary users_id — across entities the caller cannot see — is
     * blocked. Self and full-interface callers (User READ) are always allowed.
     *
     * @param int $requested_users_id
     * @return bool
     */
    public static function canCurrentUserViewRequester(int $requested_users_id): bool
    {
        // Non-positive id means "no requester selected" (defaults to self).
        if ($requested_users_id <= 0 || $requested_users_id === (int) Session::getLoginUserID()) {
            return true;
        }

        // Full-interface callers who may read users (technicians / admins), entity
        // boundary included: can() loads the row and replays the entity check that a bare
        // haveRight() skips. This is the rule the strictest endpoints of the
        // u*Update.php family carried inline before they were unified onto this helper.
        if ((new \User())->can($requested_users_id, READ)) {
            return true;
        }

        // Otherwise the target must share at least one entity the caller can currently see,
        // exactly as the requester dropdown is entity-restricted.
        $my_entities     = $_SESSION['glpiactiveentities'] ?? [];
        $target_entities = \Profile_User::getUserEntities($requested_users_id, true);

        return count(array_intersect((array) $my_entities, $target_entities)) > 0;
    }

    /**
     * [S3] Constrain a reflected form field name to the charset a name attribute may safely
     * contain (letters, digits, underscore, brackets), so a crafted $_POST['field'] cannot
     * break out of the attribute (reflected XSS).
     *
     * Shared by the whole u*Update.php family, which all echo the posted field name back as
     * the name of the dropdown they render: keeping the rule in one place stops a new
     * endpoint of the family from silently shipping without it.
     *
     * @param mixed $field_name
     *
     * @return string
     */
    public static function normalizePostedFieldName($field_name): string
    {
        return (string) preg_replace('/[^A-Za-z0-9_\[\]]/', '', (string) ($field_name ?? ''));
    }

    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    /**
     * Restrict the icon columns to a plain icon class token: they are echoed into HTML class
     * attributes by the service catalog and the wizard, so a crafted value would break out of
     * the attribute (stored XSS). Same allow-list as Metademand and FieldCustomvalue.
     * An invalid value is dropped so the column keeps its previous value.
     *
     * @param array $input
     *
     * @return array
     */
    public static function sanitizeIconInput($input)
    {
        foreach (['icon_request', 'icon_incident', 'icon_problem', 'icon_change'] as $icon_field) {
            if (
                isset($input[$icon_field])
                && $input[$icon_field] !== ''
                && !preg_match('/^[a-zA-Z0-9 _-]+$/', (string) $input[$icon_field])
            ) {
                unset($input[$icon_field]);
            }
        }
        return $input;
    }

    /**
     * Columns the configuration form is allowed to write.
     *
     * The controller hands the whole POST array over: without this list every column of the
     * table would be addressable from the form, including one a future migration adds.
     */
    private const EDITABLE_FIELDS = [
        'id',
        'simpleticket_to_metademand',
        'display_type',
        'parent_ticket_tag',
        'son_ticket_tag',
        'childs_parent_content',
        'create_pdf',
        'use_draft',
        'languageTech',
        'display_buttonlist_servicecatalog',
        'add_groups_with_regex',
        'see_top',
        'show_form_changes',
        'icon_incident',
        'icon_request',
        'icon_problem',
        'icon_change',
        'title_servicecatalog',
        'comment_servicecatalog',
        'fa_servicecatalog',
    ];

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareInputForAdd($input)
    {
        return self::sanitizeIconInput(self::keepEditableFields($input));
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareInputForUpdate($input)
    {
        return self::sanitizeIconInput(self::keepEditableFields($input));
    }

    /**
     * Drop every key the configuration form does not own.
     *
     * @param array $input
     *
     * @return array
     */
    private static function keepEditableFields($input)
    {
        return array_intersect_key($input, array_flip(self::EDITABLE_FIELDS));
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
                        `simpleticket_to_metademand`        tinyint               DEFAULT '0',
                        `parent_ticket_tag`                 varchar(255)          DEFAULT NULL,
                        `son_ticket_tag`                    varchar(255)          DEFAULT NULL,
                        `create_pdf`                        tinyint               DEFAULT '0',
                        `show_requester_informations`       tinyint               DEFAULT 0,
                        `childs_parent_content`             tinyint               DEFAULT 0,
                        `display_type`                      tinyint               DEFAULT 1,
                        `display_buttonlist_servicecatalog` tinyint               DEFAULT 1,
                        `title_servicecatalog`              varchar(255)          DEFAULT NULL,
                        `comment_servicecatalog`            text                  DEFAULT NULL,
                        `fa_servicecatalog`                 varchar(100) NOT NULL DEFAULT 'ti ti-share',
                        `languageTech`                      varchar(100)          DEFAULT NULL,
                        `use_draft`                         tinyint               DEFAULT 0,
                        `show_form_changes`                 tinyint      NOT NULL DEFAULT '0',
                        `add_groups_with_regex`             tinyint      NOT NULL DEFAULT '0',
                        `icon_request`                      varchar(255)          DEFAULT NULL,
                        `icon_incident`                     varchar(255)          DEFAULT NULL,
                        `icon_problem`                      varchar(255)          DEFAULT NULL,
                        `icon_change`                       varchar(255)          DEFAULT NULL,
                        `see_top`                           tinyint      NOT NULL DEFAULT '1',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

            $DB->insert(
                $table,
                ['id' => 1,
                    'simpleticket_to_metademand' => 1,
                    'childs_parent_content' => 1],
            );
        }
        if (!$DB->fieldExists($table, "parent_ticket_tag")) {
            $migration->addField($table, "parent_ticket_tag", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "son_ticket_tag")) {
            $migration->addField($table, "son_ticket_tag", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "show_requester_informations")) {
            $migration->addField($table, "show_requester_informations", "tinyint DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "create_pdf")) {
            $migration->addField($table, "create_pdf", "tinyint DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "childs_parent_content")) {
            $migration->addField($table, "childs_parent_content", "tinyint DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        //version 2.7.4
        if (!$DB->fieldExists($table, "display_buttonlist_servicecatalog")) {
            $migration->addField($table, "display_buttonlist_servicecatalog", "tinyint DEFAULT 1");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "title_servicecatalog")) {
            $migration->addField($table, "title_servicecatalog", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "comment_servicecatalog")) {
            $migration->addField($table, "comment_servicecatalog", "TEXT DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "fa_servicecatalog")) {
            $migration->addField($table, "fa_servicecatalog", "varchar(100) NOT NULL DEFAULT 'ti ti-share'");
            $migration->migrationOneTable($table);
        }
        $migration->dropField($table, 'enable_application_environment');
        $migration->dropField($table, 'enable_families');
        //version 2.7.9
        if (!$DB->fieldExists($table, "languageTech")) {
            $migration->addField($table, "languageTech", "varchar(100) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        //version 3.0.0
        if (!$DB->fieldExists($table, "use_draft")) {
            $migration->addField($table, "use_draft", "tinyint DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        //version 3.1.0
        if (!$DB->fieldExists($table, "show_form_changes")) {
            $migration->addField($table, "show_form_changes", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.3.8
        if (!$DB->fieldExists($table, "add_groups_with_regex")) {
            $migration->addField($table, "add_groups_with_regex", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        //version 3.2.19
        $migration->changeField($table, 'use_draft', 'use_draft', "tinyint DEFAULT 0");
        //version 3.3.0
        $migration->changeField($table, 'show_form_changes', 'show_form_changes', "tinyint NOT NULL DEFAULT 0");
        //version 3.3.20
        if (!$DB->fieldExists($table, "icon_request")) {
            $migration->addField($table, "icon_request", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "icon_incident")) {
            $migration->addField($table, "icon_incident", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "icon_problem")) {
            $migration->addField($table, "icon_problem", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "icon_change")) {
            $migration->addField($table, "icon_change", "varchar(255) DEFAULT NULL");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "see_top")) {
            $migration->addField($table, "see_top", "tinyint NOT NULL DEFAULT '1'");
            $migration->migrationOneTable($table);
        }
        //version 3.5.0
        $query = $DB->buildUpdate(
            $table,
            [
                'show_form_changes' => 0,
            ],
            [1],
        );
        $DB->doQuery($query);

        //version 3.5.4
        if ($DB->fieldExists($table, "fa_servicecatalog")) {
            $migration->changeField($table, 'fa_servicecatalog', 'fa_servicecatalog', "varchar(100) NOT NULL DEFAULT 'ti ti-share'");
            $migration->migrationOneTable($table);
        }

    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    /**
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == __CLASS__) {
            $item->showConfigForm();
        }
        return true;
    }


    /**
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(self::getTypeName());
    }


    /**
     * @param array $options
     *
     * @return array
     * @see CommonGLPI::defineTabs()
     */
    public function defineTabs($options = [])
    {
        $ong = [];
        //      $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab(Tools::class, $ong, $options);
        $this->addStandardTab(CheckSchema::class, $ong, $options);

        return $ong;
    }

    /**
     * @return bool
     */
    public function showConfigForm()
    {
        if (!$this->canCreate() || !$this->canView()) {
            return false;
        }

        $config = Config::getInstance();

        TemplateRenderer::getInstance()->display('@metademands/config.html.twig', [
            'config' => $config,
            'action' => Toolbox::getItemTypeFormURL(Config::class),
        ]);
    }

    /**
     * @return bool|mixed
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $temp = new Config();

            $data = $temp->getConfigFromDB();
            if ($data) {
                self::$instance = $data;
            }
        }

        return self::$instance;
    }

    /**
     * getConfigFromDB : get all configs in the database
     *
     * @param array $options
     *
     * @return bool|mixed
     */
    public function getConfigFromDB($options = [])
    {
        $table = $this->getTable();
        $where = [];
        if (isset($options['where'])) {
            $where = $options['where'];
        }
        $dbu        = new DbUtils();
        $dataConfig = $dbu->getAllDataFromTable($table, $where);
        if (count($dataConfig) > 0) {
            return array_shift($dataConfig);
        }

        return false;
    }
}
