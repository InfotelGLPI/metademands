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

use Ajax;
use Appliance;
use Budget;
use CartridgeItem;
use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Computer;
use ConsumableItem;
use Contact;
use Contract;
use DBConnection;
use DbUtils;
use Document;
use Entity;
use Glpi\DBAL\QueryExpression;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Features\Clonable;
use Glpi\ItemTranslation\Context\ProvideTranslationsInterface;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Fields\Basket;
use GlpiPlugin\Metademands\Fields\Checkbox;
use GlpiPlugin\Metademands\Fields\Date;
use GlpiPlugin\Metademands\Fields\Dateinterval;
use GlpiPlugin\Metademands\Fields\Datetime;
use GlpiPlugin\Metademands\Fields\Datetimeinterval;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use GlpiPlugin\Metademands\Fields\Dropdownmultiple;
use GlpiPlugin\Metademands\Fields\Dropdownobject;
use GlpiPlugin\Metademands\Fields\Email;
use GlpiPlugin\Metademands\Fields\Freetable;
use GlpiPlugin\Metademands\Fields\Information;
use GlpiPlugin\Metademands\Fields\Ldapdropdown;
use GlpiPlugin\Metademands\Fields\Link;
use GlpiPlugin\Metademands\Fields\Number;
use GlpiPlugin\Metademands\Fields\Radio;
use GlpiPlugin\Metademands\Fields\Range;
use GlpiPlugin\Metademands\Fields\Signature;
use GlpiPlugin\Metademands\Fields\Tel;
use GlpiPlugin\Metademands\Fields\Text;
use GlpiPlugin\Metademands\Fields\Textarea;
use GlpiPlugin\Metademands\Fields\Time;
use GlpiPlugin\Metademands\Fields\Title;
use GlpiPlugin\Metademands\Fields\Titleblock;
use GlpiPlugin\Metademands\Fields\Upload;
use GlpiPlugin\Metademands\Fields\Url;
use GlpiPlugin\Metademands\Fields\Yesno;
use GlpiPlugin\Resources\Resource;
use Group_Item;
use Html;
use Line;
use MassiveAction;
use Migration;
use Monitor;
use NetworkEquipment;
use PassiveDCEquipment;
use Peripheral;
use Phone;
use Plugin;
use Printer;
use Problem;
use Profile_User;
use Project;
use Reminder;
use RSSFeed;
use Search;
use Session;
use Software;
use Supplier;
use TicketRecurrent;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * Class Field
 */
class Field extends CommonDBChild implements ProvideTranslationsInterface
{
    use Clonable;

    public static $rightname = 'plugin_metademands';

    public static $itemtype = Metademand::class;
    public static $items_id = 'plugin_metademands_metademands_id';

    public $dohistory = true;
    // Request type
    public const MAX_FIELDS = 40;

    public static $field_types = [
        '',
        'dropdown',
        'dropdown_object',
        'dropdown_meta',
        'dropdown_multiple',
        'dropdown_ldap',
        'title',
        'title-block',
        'informations',
        'text',
        'tel',
        'email',
        'url',
        'textarea',
        'yesno',
        'checkbox',
        'radio',
        'number',
        'range',
        'freetable',
        'basket',
        'date',
        'time',
        'datetime',
        'date_interval',
        'datetime_interval',
        'upload',
        'link',
        'signature',
        'parent_field',
    ];

    public static $field_title_types = [
        'title',
        'title-block',
        'informations',
    ];

    public static $field_customvalues_types = [
        'dropdown_meta',
        'dropdown_multiple',
        'checkbox',
        'radio',
    ];

    public static $field_dropdown_types = [
        'dropdown',
        //        'dropdown_ldap',
        'dropdown_object',
    ];

    public static $field_text_types = [
        'text',
        'tel',
        'email',
        'url',
        'textarea',
        'signature',
    ];

    public static $field_date_types = [
        'date',
        'time',
        'datetime',
        'date_interval',
        'datetime_interval',
    ];

    public static $field_specificobjects = [
        'urgency',
        'impact',
        'priority',
        'mydevices',
    ];

    public static $field_withobjects = [
        'dropdown',
        'dropdown_object',
        'dropdown_meta',
        'dropdown_multiple',
        'basket',
    ];

    public static $not_null = 'NOT_NULL';


    public static function getIcon()
    {
        return "ti ti-shape";
    }


    public function canCreateItem(): bool
    {
        return true;
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
        return __('Wizard creation', 'metademands');
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

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `entities_id`                       int {$default_key_sign}         NOT NULL DEFAULT '0',
                        `is_recursive`                      int                             NOT NULL DEFAULT '0',
                        `comment`                           text COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL,
                        `rank`                              int                             NOT NULL DEFAULT '0',
                        `order`                             int                             NOT NULL DEFAULT '0',
                        `name`                              varchar(255)                             DEFAULT NULL,
                        `label2`                            text COLLATE utf8mb4_unicode_ci NULL     DEFAULT NULL,
                        `type`                              varchar(255)                             DEFAULT NULL,
                        `item`                              varchar(255)                             DEFAULT NULL,
                        `plugin_metademands_fields_id`      int {$default_key_sign}        NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign}        NOT NULL DEFAULT '0',
                        `date_creation`                     timestamp                       NULL     DEFAULT NULL,
                        `date_mod`                          timestamp                       NULL     DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        if (!$DB->fieldExists($table, "comment")) {
            $migration->addField($table, "comment", "text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "label2")) {
            $migration->addField($table, "label2", "text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
            $migration->migrationOneTable($table);
        }

        $migration->changeField($table, 'comment', 'comment', "TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL");
        $migration->migrationOneTable($table);
        $migration->changeField($table, 'label2', 'label2', "TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL");
        $migration->migrationOneTable($table);

        if (!$DB->fieldExists($table, "order")) {
            $migration->addField($table, "order", "int NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "plugin_metademands_fields_id")) {
            $migration->addField(
                $table,
                "plugin_metademands_fields_id",
                "int {$default_key_sign} NOT NULL DEFAULT '0'",
            );
            if (!isIndex($table, "plugin_metademands_fields_id")) {
                $migration->addKey($table, "plugin_metademands_fields_id");
            }
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

        //Version 2.7.4
        $field = new Field();
        $fields = $field->find(['type' => "dropdown", "item" => "user"]);
        foreach ($fields as $f) {
            $f["item"] = "User";
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "usertitle"]);
        foreach ($fields as $f) {
            $f["item"] = "UserTitle";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "usercategory"]);
        foreach ($fields as $f) {
            $f["item"] = "UserCategory";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "group"]);
        foreach ($fields as $f) {
            $f["item"] = "Group";
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "location"]);
        foreach ($fields as $f) {
            $f["item"] = "Location";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "appliance"]);
        foreach ($fields as $f) {
            $f["item"] = "Appliance";
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "itilcategory"]);
        foreach ($fields as $f) {
            $f["item"] = "ITILCategory_Metademands";
            $f["type"] = "dropdown_meta";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => "other"]);
        foreach ($fields as $f) {
            $f["item"] = "other";
            $f["type"] = "dropdown_meta";
            $field->update($f);
        }
        $fields = $field->find(['type' => "dropdown", "item" => Resource::class]);
        foreach ($fields as $f) {
            $f["type"] = "dropdown_object";
            $field->update($f);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }

    public function getCloneRelations(): array
    {
        return [
            FieldParameter::class,
            FieldOption::class,
            FieldCustomvalue::class,
            FieldTranslation::class,
            Freetablefield::class,
        ];
    }

    /**
     * Get request criteria to search for an item
     *
     * @param string $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return array|null
     **@since 9.4
     *
     */
    public static function getSQLCriteriaToSearchForItem($itemtype, $items_id)
    {
        $table = static::getTable();

        $criteria = [
            'SELECT' => [
                static::getIndexName(),
                'plugin_metademands_metademands_id AS items_id',
            ],
            'FROM' => $table,
            'WHERE' => [
                $table . '.' . 'plugin_metademands_metademands_id' => $items_id,
            ],
        ];

        // Check item 1 type
        $request = false;
        if (preg_match('/^itemtype/', static::$itemtype)) {
            $criteria['SELECT'][] = static::$itemtype . ' AS itemtype';
            $criteria['WHERE'][$table . '.' . static::$itemtype] = $itemtype;
            $request = true;
        } else {
            $criteria['SELECT'][] = new QueryExpression("'" . static::$itemtype . "' AS itemtype");
            if (
                ($itemtype == static::$itemtype)
                || is_subclass_of($itemtype, static::$itemtype)
            ) {
                $request = true;
            }
        }
        if ($request === true) {
            return $criteria;
        }
        return null;
    }


    /**
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == Metademand::class) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()],
                    ),
                );
            }
            return self::getTypeName();
        }
        return '';
    }

    /**
     *
     * @static
     *
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool|true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::listFields($item);

        return true;
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
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(FieldParameter::class, $ong, $options);
        if ($this->fields['type'] == 'freetable') {
            $this->addStandardTab(Freetablefield::class, $ong, $options);
        } else {
            $this->addStandardTab(FieldCustomvalue::class, $ong, $options);
        }
        $this->addStandardTab(FieldOption::class, $ong, $options);
        if (Session::getCurrentInterface() == 'central') {
            $this->addStandardTab('Log', $ong, $options);
        }
        return $ong;
    }


    public function showExistingForm($ID, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!$this->canview()) {
            return false;
        }

        if (!$this->cancreate()) {
            return false;
        }

        $metademand = new Metademand();

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            if (!isset($item)) {
                return false;
            }
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
            $metademand->getFromDB($item->getID());
            // Create item
            $this->check(-1, CREATE, $options);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
            $this->fields['color'] = '#000';
        }

        $sessionId = $ID > 0 ? $ID : 0;
        if (isset($_SESSION['glpi_plugin_metademands_fields'][$sessionId])) {
            foreach ($_SESSION['glpi_plugin_metademands_fields'][$sessionId] as $key => $value) {
                $this->fields[$key] = $value;
            }
            unset($_SESSION['glpi_plugin_metademands_fields']);
        }

        ob_start();
        $type_rand = self::dropdownFieldTypes(
            self::$field_types,
            ['metademands_id' => $this->fields["plugin_metademands_metademands_id"]],
        );
        $type_html = ob_get_clean();

        ob_start();
        $rank_rand = \Dropdown::showNumber('rank', [
            'value' => $this->fields["rank"],
            'min'   => 1,
            'max'   => self::MAX_FIELDS,
        ]);
        $rank_html = ob_get_clean();

        ob_start();
        $this->showOrderDropdown($this->fields);
        $order_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/field_existing_form.html.twig', [
            'action'         => Toolbox::getItemTypeFormURL(Field::class),
            'metademand_id'  => $this->fields['plugin_metademands_metademands_id'],
            'field_id'       => $this->fields['id'] ?? 0,
            'prev_fields_id' => $this->fields['plugin_metademands_fields_id'] ?? 0,
            'type_html'      => $type_html,
            'type_rand'      => $type_rand,
            'rank_html'      => $rank_html,
            'rank_rand'      => $rank_rand,
            'order_html'     => $order_html,
            'is_title_block' => ($this->fields['type'] ?? '') === 'title-block',
            'plugin_web_dir' => PLUGIN_METADEMANDS_WEBDIR,
        ]);

        return true;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showForm($ID, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }
        Html::requireJs('tinymce');

        $metademand = new Metademand();

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            if (!isset($item)) {
                return false;
            }
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
            $metademand->getFromDB($item->getID());
            // Create item
            $this->check(-1, CREATE, $options);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
        }

        // Data saved in session
        $sessionId = $ID > 0 ? $ID : 0;
        if (isset($_SESSION['glpi_plugin_metademands_fields'][$sessionId])) {
            foreach ($_SESSION['glpi_plugin_metademands_fields'][$sessionId] as $key => $value) {
                $this->fields[$key] = $value;
            }
            unset($_SESSION['glpi_plugin_metademands_fields']);
        }

        // --- Warnings ---
        $metademand_fields = new self();
        $metafield_itil = false;
        if ($metafields = $metademand_fields->find([
            'plugin_metademands_metademands_id' => $this->fields['plugin_metademands_metademands_id'],
            'item' => 'ITILCategory_Metademands',
        ])) {
            if (count($metafields) > 0) {
                $metafield_itil = true;
            }
        }

        $categories = [];
        if (isset($metademand->fields['itilcategories_id'])) {
            if (is_array(json_decode($metademand->fields['itilcategories_id'], true))) {
                $categories = json_decode($metademand->fields['itilcategories_id'], true);
            }
        }
        $metafield_itil_warning = ($metafield_itil == false && count($categories) > 1);
        $basket_warning = ($metademand->fields['is_basket'] == 0
            && in_array($this->fields['type'] ?? '', ['basket', 'free_input']));

        $customvalues_warning = false;
        if ($ID > 0) {
            $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
            $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;
            if ((in_array($this->fields['type'], $allowed_customvalues_types)
                    || in_array($this->fields['item'], $allowed_customvalues_items))
                && !in_array($this->fields["item"], self::$field_specificobjects)
                && $this->fields['item'] != "Appliance"
                && $this->fields['item'] != "Group") {
                $field_custom = new FieldCustomvalue();
                if (!$field_custom->find(["plugin_metademands_fields_id" => $this->getID()])) {
                    $customvalues_warning = true;
                }
            }
        }

        // --- Type dropdown (logique conditionnelle complexe) ---
        $type_rand = null;
        $ajax_url  = PLUGIN_METADEMANDS_WEBDIR . "/ajax/viewtypefields.php?id=" . $this->fields['id'];
        $params_type = [
            'value'         => '__VALUE__',
            'type'          => '__VALUE__',
            'item'          => $this->fields['item'] ?? '',
            'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
            'change_type'   => 1,
        ];

        ob_start();
        if ($ID < 1) {
            $type_rand = self::dropdownFieldTypes(self::$field_types, [
                'value'          => $this->fields["type"] ?? '',
                'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
            ]);
            Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
        } else {
            if (in_array($this->fields["type"], self::$field_title_types)) {
                $type_rand = self::dropdownFieldTypes(self::$field_title_types, [
                    'value'          => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                ]);
                Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
            } elseif (in_array($this->fields["type"], self::$field_customvalues_types)) {
                if (in_array($this->fields["item"], Dropdownmultiple::$dropdown_multiple_objects)) {
                    $type_rand = self::dropdownFieldTypes(["dropdown_multiple"], [
                        'value'          => $this->fields["type"],
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    ]);
                    Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
                } else {
                    if ($this->fields["item"] == "other" || $this->fields["type"] == "radio" || $this->fields["type"] == "checkbox") {
                        $type_rand = self::dropdownFieldTypes(self::$field_customvalues_types, [
                            'value'          => $this->fields["type"],
                            'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                        ]);
                        Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
                    } else {
                        echo self::getFieldTypesName($this->fields['type']);
                        echo Html::hidden('type', ['value' => $this->fields['type']]);
                    }
                }
            } elseif (in_array($this->fields["type"], self::$field_text_types)) {
                $type_rand = self::dropdownFieldTypes(self::$field_text_types, [
                    'value'          => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                ]);
                Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
            } elseif (in_array($this->fields["type"], self::$field_date_types)) {
                $type_rand = self::dropdownFieldTypes(self::$field_date_types, [
                    'value'          => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                ]);
                Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
            } elseif (in_array($this->fields["type"], self::$field_dropdown_types)) {
                $type_rand = self::dropdownFieldTypes(self::$field_dropdown_types, [
                    'value'          => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                ]);
                Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
            } elseif ($this->fields["type"] == "dropdown_multiple") {
                if (in_array($this->fields["item"], Dropdownmultiple::$dropdown_multiple_objects)) {
                    echo self::getFieldTypesName($this->fields['type']);
                    echo Html::hidden('type', ['value' => $this->fields['type']]);
                    $type_rand = mt_rand();
                } else {
                    $type_rand = self::dropdownFieldTypes(["dropdown_multiple"], [
                        'value'          => $this->fields["type"],
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    ]);
                    Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_values", $ajax_url, $params_type);
                }
            } else {
                echo self::getFieldTypesName($this->fields['type']);
                echo Html::hidden('type', ['value' => $this->fields['type']]);
            }
        }
        $type_html = ob_get_clean();

        // --- Rank ---
        ob_start();
        $rank_rand = \Dropdown::showNumber('rank', [
            'value' => $this->fields["rank"],
            'min'   => 1,
            'max'   => self::MAX_FIELDS,
        ]);
        $rank_html = ob_get_clean();

        // --- Item/Objet ---
        $item_label_html = '';
        $params_item = [
            'value'          => '__VALUE__',
            'type'           => '__VALUE__',
            'item'           => $this->fields['item'] ?? '',
            'step'           => 'object',
            'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
            'change_type'    => 1,
        ];
        if ($type_rand) {
            $params_item['rand'] = $type_rand;
        }

        ob_start();
        if ($ID < 1) {
            $item_label_html = '<span id="show_item_object" style="display:none">'
                . __('Object', 'metademands')
                . '<span style="color:red">&nbsp;*&nbsp;</span></span>'
                . '<span id="show_item_label_title" style="display:none"></span>';
            echo '<span id="show_item">';
            self::dropdownFieldItems($this->fields["type"] ?? '', ['value' => $this->fields["item"] ?? '']);
            echo '</span>';
            Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_item", $ajax_url, $params_item);
            echo '<span id="show_item_title" style="display:none">';
        } else {
            $item_label_html = __('Object', 'metademands');
            if ($this->fields["type"] == "dropdown_meta") {
                $metademand_custom = new FieldCustomvalue();
                if ($customs = $metademand_custom->find(["plugin_metademands_fields_id" => $this->fields['id']])) {
                    if (count($customs) > 0) {
                        echo self::getFieldItemsName($this->fields['type'], 'other');
                        echo Html::hidden('item', ['value' => 'other']);
                    }
                } else {
                    echo self::getFieldItemsName($this->fields['type'], $this->fields['item']);
                    echo Html::hidden('item', ['value' => $this->fields['item']]);
                }
            } elseif (in_array($this->fields["type"], self::$field_dropdown_types)) {
                echo '<span id="show_item">';
                self::dropdownFieldItems($this->fields["type"], ['value' => $this->fields["item"]]);
                echo '</span>';
                Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_item", $ajax_url, $params_item);
                echo '<span id="show_item_title" style="display:none">';
            } elseif ($this->fields["type"] == "dropdown_multiple") {
                if ($this->fields["item"] == "other") {
                    echo self::getFieldItemsName($this->fields['type'], $this->fields['item']);
                    echo Html::hidden('item', ['value' => $this->fields['item'] ?? null]);
                } else {
                    echo '<span id="show_item">';
                    self::dropdownFieldItems($this->fields["type"], [
                        'value'    => $this->fields["item"],
                        'criteria' => Dropdownmultiple::$dropdown_multiple_items,
                    ]);
                    echo '</span>';
                    Ajax::updateItemOnSelectEvent('dropdown_type' . $type_rand, "show_item", $ajax_url, $params_item);
                    echo '<span id="show_item_title" style="display:none">';
                }
            } else {
                echo self::getFieldItemsName($this->fields['type'], $this->fields['item']);
                echo Html::hidden('item', ['value' => $this->fields['item'] ?? null]);
            }
        }
        $item_value_html = ob_get_clean();

        // --- Ordre ---
        ob_start();
        $this->showOrderDropdown($this->fields);
        $order_html = ob_get_clean();

        // --- Entités ---
        if ($ID < 1 && isset($item)) {
            $entities_id  = $item->fields["entities_id"];
            $is_recursive = $item->fields["is_recursive"];
        } else {
            $entities_id  = $this->fields["entities_id"] ?? 0;
            $is_recursive = $this->fields["is_recursive"] ?? 0;
        }

        TemplateRenderer::getInstance()->display('@metademands/field_form.html.twig', [
            'action'                 => Toolbox::getItemTypeFormURL(Field::class),
            'metademand_id'          => $this->fields['plugin_metademands_metademands_id'],
            'field_id'               => $this->fields['id'] ?? 0,
            'is_new'                 => $ID < 1,
            'entities_id'            => $entities_id,
            'is_recursive'           => $is_recursive,
            'item_name'              => stripslashes($this->fields['name'] ?? ''),
            'label2'                 => $this->fields['label2'] ?? '',
            'comment'                => $this->fields['comment'] ?? '',
            'show_label2_required'   => $ID > 0 && in_array($this->fields['type'] ?? '', ['datetime_interval', 'date_interval']),
            'metafield_itil_warning' => $metafield_itil_warning,
            'type_html'              => $type_html,
            'basket_warning'         => $basket_warning,
            'rank_html'              => $rank_html,
            'rank_rand'              => $rank_rand,
            'item_label_html'        => $item_label_html,
            'item_value_html'        => $item_value_html,
            'is_title_block'         => ($this->fields['type'] ?? '') === 'title-block',
            'order_html'             => $order_html,
            'customvalues_warning'   => $customvalues_warning,
            'prev_fields_id'         => $this->fields['plugin_metademands_fields_id'] ?? 0,
            'plugin_web_dir'         => PLUGIN_METADEMANDS_WEBDIR,
            'can_delete'             => $ID > 0 && $this->can($ID, UPDATE),
        ]);

        return true;
    }

    /**
     * Show the field list of a metademand, grouped in one tab per block.
     *
     * @param $item the Metademand the tab is displayed for (typed CommonGLPI by the tab callback)
     *
     * @throws \GlpitestSQLError
     */
    private static function listFields($item)
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        $rand = mt_rand();
        $meta_id = $item->getID();
        $canedit = $item->can($meta_id, UPDATE);
        $webdir = PLUGIN_METADEMANDS_WEBDIR;

        $add = null;
        if ($canedit) {
            $modal_new_id = "metaFieldNewModal{$rand}";
            $modal_existing_id = "metaFieldExistingModal{$rand}";
            $fn_new = "addFieldmeta{$meta_id}{$rand}";
            $fn_existing = "addExistingFieldmeta{$meta_id}{$rand}";
            // HEX_TAG/HEX_AMP only: the payload is consumed as a JS object literal, so
            // escaping quotes would break it (see the plugin inline-JSON convention).
            $params_json = json_encode([
                'type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $meta_id,
                'id' => -1,
            ], JSON_HEX_TAG | JSON_HEX_AMP);
            $url_new = $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php";
            $url_existing = $webdir . "/ajax/viewexistingsubitem.php";

            $add = [
                'modal_new_id' => $modal_new_id,
                'modal_existing_id' => $modal_existing_id,
                'fn_new' => $fn_new,
                'fn_existing' => $fn_existing,
                'script_html' => Html::scriptBlock("
            function {$fn_new}() {
                $.post('{$url_new}', {$params_json}, function(html) {
                    $('#{$modal_new_id}_body').html(html);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('{$modal_new_id}')).show();
                });
            }
            function {$fn_existing}() {
                $.post('{$url_existing}', {$params_json}, function(html) {
                    $('#{$modal_existing_id}_body').html(html);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('{$modal_existing_id}')).show();
                });
            }
            (function () {
                var p = new URLSearchParams(window.location.search);
                if (p.get('open_add_field') === '{$meta_id}') {
                    p.delete('open_add_field');
                    var qs = p.toString();
                    history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);
                    {$fn_new}();
                }
            })();"),
            ];
        }

        $cond['plugin_metademands_metademands_id'] = $meta_id;

        $searched_block = $_SESSION['plugin_metademands_searchresults'][$meta_id]['block'] ?? 0;
        $block_filter_script_html = '';
        if ($searched_block != 0) {
            $cond['rank'] = $searched_block;
            $block = (int) $searched_block;
            $block_filter_script_html = Html::scriptBlock("
            $(document).ready(function() {
                        var fieldid = {$block} || sessionStorage.getItem('loadedblock') || '1';
                        var meta_id = {$meta_id};
                        var urlmeta = '{$webdir}';

                        sessionStorage.setItem('loadedblock', fieldid);
                        function updateActiveTab(rank) {
                            document.querySelectorAll('a[id^=\"ablock\"]').forEach(a => a.classList.remove('active'));
                            document.querySelectorAll('div[id^=\"block\"]').forEach(div => div.classList.remove('active'));

                            document.getElementById('ablock' + rank)?.classList.add('active');
                            $('div[id^=\"block\"]').hide();
                            $('#block' + rank).show();
                        }
                         updateActiveTab(fieldid);
                        function loadPreview(fieldid) {
                            $.ajax({
                                url: urlmeta + '/ajax/previewMetademand.php',
                                type: 'POST',
                                datatype: 'HTML',
                                data: { block: fieldid, metademands_id: meta_id },
                                success: function (response) {
                                    $('#see_block_preview').html(response);
                                },
                                error: function (xhr, status, error) {
                                    console.log(xhr);
                                    console.log(status);
                                    console.log(error);
                                }
                            });
                        }

                        loadPreview(fieldid);
                        window.location.hash = '#block' + fieldid;
                });");
        }
        if (isset($_SESSION['plugin_metademands_searchresults'][$meta_id]['type'])
            && $_SESSION['plugin_metademands_searchresults'][$meta_id]['type'] != 0) {
            $cond['type'] = $_SESSION['plugin_metademands_searchresults'][$meta_id]['type'];
        }
        if (isset($_SESSION['plugin_metademands_searchresults'][$meta_id]['item'])
            && $_SESSION['plugin_metademands_searchresults'][$meta_id]['item'] != 0) {
            $cond['item'] = $_SESSION['plugin_metademands_searchresults'][$meta_id]['item'];
        }

        $self = new self();
        $data = $self->find($cond, ['rank', 'order']);

        $search_form_html = self::getSearchForm($item, $cond);

        $tabs_state_script_html = '';
        if ($searched_block == 0) {
            $tabs_state_script_html = Html::scriptBlock(
                '$(document).ready(function () {
                        var hash = window.location.hash;
                        var fieldid = sessionStorage.getItem("loadedblock") || "1";

                        function updateActiveTab(rank) {
                            $("a[id^=\"ablock\"]").removeClass("active");
                            $("div[id^=\"block\"]").removeClass("active").hide();
                            $("#ablock" + rank).addClass("active");
                            $("#block" + rank).addClass("active").show();
                        }

                        if (fieldid && document.getElementById(fieldid)) {
                            updateActiveTab(fieldid.replace("block", ""));
                            hash = "#" + fieldid;
                        } else if (hash.startsWith("#block") && document.getElementById(hash.substring(1))) {
                            updateActiveTab(hash.replace("#block", ""));
                            sessionStorage.setItem("loadedblock", hash.substring(1));
                        } else {
                            updateActiveTab(1);
                            sessionStorage.setItem("loadedblock", "block1");
                            window.location.hash = "#block1";
                        }

                        $("#fieldslist a").click(function (e) {
                            e.preventDefault();
                            var tabId = $(this).attr("href").replace("#", "");

                            if (document.getElementById(tabId)) {
                                var rank = tabId.replace("block", "");
                                sessionStorage.setItem("loadedblock", tabId);
                                updateActiveTab(rank);
                                window.location.hash = tabId;
                            }
                        });

                        $("ul.nav-tabs > li > a").on("shown.bs.tab", function (e) {
                            var id = $(e.target).attr("href").substr(1);
                            sessionStorage.setItem("loadedblock", id);
                            window.location.hash = "#" + id;
                        });

                        function scrollToActiveTab() {
                            const activeTab = document.querySelector(".scrollable-tabs .active");
                            const container = document.querySelector(".scrollable-tabs");

                            if (activeTab && container) {
                                const offsetLeft = activeTab.offsetLeft;
                                const containerWidth = container.clientWidth;
                                const tabWidth = activeTab.offsetWidth;

                                // Center the active tab
                                const scrollTo = offsetLeft - (containerWidth / 2) + (tabWidth / 2);
                                container.scrollTo({ left: scrollTo, behavior: "smooth" });
                            }
                        }
                        scrollToActiveTab();
                    });',
            );
        }

        $fieldparameter = new FieldParameter();
        $field_custom = new FieldCustomvalue();

        // Batch-load all related data for this metademand's fields (avoids N+1 per field)
        $all_ids_for_preload = array_column(is_array($data) ? $data : [], 'id');
        FieldParameter::preloadForFields($all_ids_for_preload);
        FieldCustomvalue::preloadForFields($all_ids_for_preload);
        FieldOption::preloadForFields($all_ids_for_preload);
        Freetablefield::preloadForFields($all_ids_for_preload);

        $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

        $new_types = [];
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginDropdownFieldItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    foreach ($new_fields as $plugin) {
                        foreach ($plugin as $k => $field) {
                            $new_types[] = $k;
                        }
                    }
                }
            }
        }

        $blocks = [];
        $databyblocks = [];
        foreach ($data as $id => $value) {
            $databyblocks[$value['rank']][] = $data[$id];
        }

        foreach ($databyblocks as $blockid => $blockfields) {
            $i = 0;
            foreach ($blockfields as $value) {
                if ($value['type'] == 'title-block' && $value['rank'] == $blockid) {
                    $i++;
                    if ($i > 0) {
                        $blocks[$blockid] = $value['name'] . " #$blockid";
                    }
                }
                if ($i == 0) {
                    $blocks[$blockid] = __('Block', 'metademands') . " " . $value['rank'];
                }
            }
        }

        $tabs = [];
        $block_fields = [];
        $tabs_scroll_script_html = '';
        if (count($blocks) > 0) {
            foreach ($blocks as $idblock => $block) {
                $tabs[] = ['id' => $idblock, 'name' => $block];
            }

            $tabs_scroll_script_html = Html::scriptBlock(
                '
                setTimeout(() => {
                    const scrollContainer = document.querySelector(".scrollable-tabs");
                    const scrollLeftBtn = document.querySelector(".scroll-left");
                    const scrollRightBtn = document.querySelector(".scroll-right");

                    if (scrollLeftBtn && scrollRightBtn && scrollContainer) {
                        scrollLeftBtn.addEventListener("click", function () {
                            scrollContainer.scrollBy({ left: -150, behavior: "smooth" });
                        });

                        scrollRightBtn.addEventListener("click", function () {
                            scrollContainer.scrollBy({ left: 150, behavior: "smooth" });
                        });
                    }
                }, 500);
            ',
            );

            foreach ($blocks as $idblock => $block) {
                foreach ($data as $value) {
                    if ($idblock == $value['rank']) {
                        $block_fields[$idblock][] = $value;
                    }
                }
            }
        }

        // Kept outside the block loop, as in the legacy code: the counters accumulate, so a
        // block missing a parameter also flags every block rendered after it.
        $koparams = 0;
        $kocustom = 0;
        $searchOption = Search::getOptions('Ticket');
        $debug = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $is_order = $item->fields['is_order'] == 1;
        $form_url = self::getFormURL();

        $panels = [];
        foreach ($block_fields as $idblock => $blockdata) {
            $blockrand = mt_rand();
            $container = 'massMetaFields' . $blockrand;

            $orders = [];
            foreach ($blockdata as $value) {
                $orders[] = $value['order'];
            }

            $order_warning_html = '';
            if ($searched_block == 0 && self::isSequentialFromOne($orders) == false) {
                $order_warning_html = Html::getSimpleForm(
                    $form_url,
                    'fixorders',
                    _x('button', 'Do you want to fix them ?', 'metademands'),
                    [
                        'plugin_metademands_metademands_id' => $meta_id,
                        'rank' => $idblock,
                    ],
                    'ti-settings',
                    "class='btn btn-warning'",
                );
            }

            foreach ($blockdata as $value) {
                $fp_check = FieldParameter::getFromStaticCache((int) $value['id']);
                if ($fp_check === false) {
                    $fp_check = $fieldparameter->find(["plugin_metademands_fields_id" => $value['id']]);
                }
                if (!$fp_check) {
                    $koparams++;
                }

                if (self::needsCustomValues($value, $allowed_customvalues_types, $allowed_customvalues_items, $new_types)) {
                    $fc_check = FieldCustomvalue::getFromStaticCache((int) $value['id']);
                    if ($fc_check === false) {
                        $fc_check = $field_custom->find(["plugin_metademands_fields_id" => $value['id']]);
                    }
                    if (!$fc_check) {
                        $kocustom++;
                    }
                }
            }

            $rows = [];
            if (is_array($blockdata) && count($blockdata) > 0) {
                // Init navigation list for field items
                Session::initNavigateListItems($self->getType(), self::getTypeName(1));

                foreach ($blockdata as $value) {
                    Session::addToNavigateListItems($self->getType(), $value['id']);

                    // N+1 fix: load per-field data from static cache (preloaded before this loop)
                    $fp_cur = FieldParameter::getFromStaticCache((int) $value['id']);
                    if ($fp_cur === false) {
                        $fp_cur = $fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $value['id']])
                            ? $fieldparameter->fields
                            : null;
                    }
                    $fc_cur = FieldCustomvalue::getFromStaticCache((int) $value['id']);
                    if ($fc_cur === false) {
                        $fc_cur = $field_custom->find(["plugin_metademands_fields_id" => $value['id']]) ?: [];
                    }
                    $fo_cur = FieldOption::getFromStaticCache((int) $value['id']);
                    if ($fo_cur === false) {
                        $fo_cur = (new FieldOption())->find(["plugin_metademands_fields_id" => $value['id']]) ?: [];
                    }

                    $needs_custom = self::needsCustomValues($value, $allowed_customvalues_types, $allowed_customvalues_items, $new_types);

                    // Label
                    $name = $value['name'] ?? '';
                    $label = empty(trim($name)) ? __('ID') . " - " . $value['id'] : $name;

                    // Type / object
                    $type_label = self::getFieldTypesName($value['type']);
                    if ($value['type'] == 'parent_field') {
                        $parent_opt = count($fo_cur) > 0 ? reset($fo_cur) : null;
                        if ($parent_opt) {
                            $parent_field = new self();
                            if ($parent_field->getFromDB($parent_opt['parent_field_id'])) {
                                $type_label .= empty(trim($parent_field->fields['name']))
                                    ? " ( ID - " . ($value['parent_field_id'] ?? '') . ")"
                                    : " (" . $parent_field->fields['name'] . ")";
                            }
                        }
                    }
                    $itemtypename = self::getFieldItemsName($value['type'], $value['item']);
                    if ($itemtypename != \Dropdown::EMPTY_VALUE) {
                        $type_label .= " (" . $itemtypename . ")";
                    }

                    // Value to check: a plain label, or the markup produced by getValueToCheck()
                    $value_to_check_label = null;
                    $value_to_check_html = '';
                    if (count($fo_cur) > 1) {
                        $value_to_check_label = __('Multiples', 'metademands');
                    } elseif (count($fo_cur) === 0) {
                        $value_to_check_label = \Dropdown::EMPTY_VALUE;
                    } else {
                        foreach ($fo_cur as $opt) {
                            $datao = [
                                'item' => $value['item'],
                                'type' => $value['type'],
                                'id' => $value['id'],
                                'check_value' => $opt['check_value'],
                                'parent_field_id' => $opt['parent_field_id'],
                                'check_type_value' => $opt['check_type_value'],
                                'check_value_regex' => $opt['check_value_regex'],
                                'custom_values' => $value['custom_values'] ?? [],
                            ];
                            if (isset($value['type'])
                                && in_array($value['type'], $allowed_customvalues_types)
                                || in_array($value['item'], $allowed_customvalues_items)) {
                                $datao['custom_values'] = count($fc_cur) > 0 ? $fc_cur : [];
                            }
                            // getValueToCheck() writes to the output buffer instead of returning
                            ob_start();
                            FieldOption::getValueToCheck($datao);
                            $value_to_check_html .= ob_get_clean();
                        }
                    }

                    // Object field
                    $object_field_label = \Dropdown::EMPTY_VALUE;
                    if ($fp_cur
                        && $fp_cur['used_by_ticket']
                        && !in_array($value['type'], ['text', 'email', 'tel', 'url'], true)) {
                        $object_field_label = $searchOption[$fp_cur['used_by_ticket']]['name'];
                    }

                    // Tasks launched by the field
                    $task_names = [];
                    foreach ($fo_cur as $opt) {
                        if (empty($opt['plugin_metademands_tasks_id'])) {
                            continue;
                        }
                        $metatask = new Task();
                        if (!$metatask->getFromDB($opt['plugin_metademands_tasks_id'])) {
                            continue;
                        }
                        if ($metatask->fields['type'] == Task::METADEMAND_TYPE) {
                            $metachildtask = new MetademandTask();
                            if ($metachildtask->getFromDBByCrit(
                                ["plugin_metademands_tasks_id" => $opt['plugin_metademands_tasks_id']],
                            )) {
                                $task_names[] = \Dropdown::getDropdownName(
                                    'glpi_plugin_metademands_metademands',
                                    $metachildtask->fields['plugin_metademands_metademands_id'],
                                );
                            }
                        } else {
                            $task_names[] = $metatask->getName();
                        }
                    }

                    $rows[] = [
                        'order' => $value['order'],
                        'id' => $value['id'],
                        'checkbox_html' => $canedit ? Html::getMassiveActionCheckBox(__CLASS__, $value['id']) : '',
                        'has_warning' => !$fp_cur || ($needs_custom && !$fc_cur),
                        'label' => $label,
                        'url' => Toolbox::getItemTypeFormURL(__CLASS__) . "?id=" . $value['id'],
                        'type_label' => $type_label,
                        'is_mandatory' => $fp_cur ? $fp_cur['is_mandatory'] == 1 : false,
                        'mandatory_label' => $fp_cur ? \Dropdown::getYesNo($fp_cur['is_mandatory']) : null,
                        'value_to_check_label' => $value_to_check_label,
                        'value_to_check_html' => $value_to_check_html,
                        'basket_label' => \Dropdown::getYesNo($fp_cur['is_basket'] ?? 0),
                        'object_field_label' => $object_field_label,
                        'task_names' => $task_names,
                        'no_task_label' => \Dropdown::EMPTY_VALUE,
                        'debug_order' => $debug ? $value['order'] : null,
                        'purge_form_html' => Html::getSimpleForm(
                            $form_url,
                            'purge',
                            "",
                            [
                                "id" => $value['id'],
                                "plugin_metademands_metademands_id" => $value['plugin_metademands_metademands_id'],
                            ],
                            "fa-times-circle fa-1x",
                            "",
                            __('Are you sure you want to delete this field ?', 'metademands'),
                        ),
                    ];
                }
            }

            $ma_open_html = '';
            $ma_top_html = '';
            $ma_bottom_html = '';
            $close_form_html = '';
            $check_all_html = '';
            if ($canedit && count($rows)) {
                $massiveactionparams = ['item' => __CLASS__,
                    'container' => $container,
                    'display' => false,
                ];
                $ma_open_html = Html::getOpenMassiveActionsForm($container);
                $ma_top_html = Html::showMassiveActions($massiveactionparams);
                // Legacy id, kept as is: it does not match the container above, so the
                // "check all" box currently drives no checkbox.
                $check_all_html = Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $blockrand);
                // Built after the rows on purpose: showMassiveActions() empties
                // $_SESSION['glpimassiveactionselected'] when it is not the top one, and the
                // row checkboxes read that selection to restore their checked state.
                $massiveactionparams['ontop'] = false;
                $ma_bottom_html = Html::showMassiveActions($massiveactionparams);
                $close_form_html = Html::closeForm(false);
            }

            $panels[] = [
                'id' => $idblock,
                'is_default' => $idblock == 1,
                'order_warning_html' => $order_warning_html,
                'ko_params' => $koparams > 0,
                'ko_custom' => $kocustom > 0,
                'drag_id' => 'drag' . $blockrand,
                'sortable_url' => $webdir . '/ajax/reorderfields.php',
                'sortable_params' => json_encode([
                    'plugin_metademands_metademands_id' => $meta_id,
                    'rank' => $idblock,
                ]),
                'ma_open_html' => $ma_open_html,
                'ma_top_html' => $ma_top_html,
                'ma_bottom_html' => $ma_bottom_html,
                'close_form_html' => $close_form_html,
                'check_all_html' => $check_all_html,
                'rows' => $rows,
            ];
        }

        $preview_script_html = '';
        if ($searched_block == 0) {
            $preview_script_html = Html::scriptBlock("
            $(document).ready(function () {
                var meta_id = {$meta_id};
                var urlmeta = '{$webdir}';
                var fieldid = '1';

                function loadPreview(fieldid) {
                    $.ajax({
                        url: urlmeta + '/ajax/previewMetademand.php',
                        type: 'POST',
                        datatype: 'HTML',
                        data: { block: fieldid, metademands_id: meta_id },
                        success: function (response) {
                            $('#see_block_preview').html(response);
                        },
                        error: function (xhr, status, error) {
                            console.log(xhr);
                            console.log(status);
                            console.log(error);
                        }
                    });
                }

                if (fieldid === 1) {
                    loadPreview(fieldid);
                }
                var storefieldid = sessionStorage.getItem('loadedblock');
                if (storefieldid) {
                    loadPreview(parseInt(storefieldid.substr(5)));
                }
                $('#fieldslist a').click(function (e) {
                    e.preventDefault();
                    var tabId = $(this).attr('href').replace('#', '');
                    if (typeof tabId !== 'undefined' && tabId.length > 0) {
                        fieldid = parseInt(tabId.substr(5));
                        loadPreview(fieldid);
                    }
                });
            });");
        }

        echo TemplateRenderer::getInstance()->render('@metademands/field_list.html.twig', [
            'canedit' => $canedit,
            'is_order' => $is_order,
            'add' => $add,
            'block_filter_script_html' => $block_filter_script_html,
            'search_form_html' => $search_form_html,
            'tabs_state_script_html' => $tabs_state_script_html,
            'tabs_scroll_script_html' => $tabs_scroll_script_html,
            'preview_script_html' => $preview_script_html,
            'tabs' => $tabs,
            'panels' => $panels,
            'drag_style' => 'cursor: move;border-width: 0 !important;border-style: none !important;'
                . ' border-color: initial !important;border-image: initial !important;',
        ]);
    }

    /**
     * Does this field require custom values to be configured?
     *
     * Extracted verbatim from the condition the field list used to repeat three times.
     *
     * @param array $value  field row
     * @param array $allowed_types
     * @param array $allowed_items
     * @param array $new_types itemtypes contributed by other plugins
     *
     * @return bool
     */
    private static function needsCustomValues($value, $allowed_types, $allowed_items, $new_types)
    {
        return (isset($value['type'])
                && in_array($value['type'], $allowed_types)
                && $value['item'] != "ITILCategory_Metademands"
                && !in_array($value["item"], self::$field_specificobjects)
                && !in_array($value['item'], $new_types))
            || (in_array($value['item'], $allowed_items)
                && $value['item'] != 'Appliance'
                && $value['item'] != 'Group');
    }

    /**
     * Show field types dropdown
     *
     * @param  $name
     * @param array $param
     *
     * @return integer|string dropdown id/rand as returned by \Dropdown::showFromArray()
     * @throws \GlpitestSQLError
     */
    public static function dropdownFieldTypes($type_fields, $param = [])
    {
        global $PLUGIN_HOOKS;

        $name = "type";
        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginTextFieldItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    $type_fields = array_merge($type_fields, $new_fields);
                }
            }
        }

        foreach ($type_fields as $key => $types) {
            //delete type parent_field if no parent metademand & not field
            if ($types == 'parent_field') {
                $metademands_parent = MetademandTask::getAncestorOfMetademandTask(
                    $p['metademands_id'],
                );
                $list_fields = [];
                $field = new self();
                foreach ($metademands_parent as $parent_id) {
                    $condition = [
                        'plugin_metademands_metademands_id' => $parent_id,
                        ['NOT' => ['type' => ['parent_field', 'upload']]],
                    ];
                    $datas_fields = $field->find($condition, ['rank', 'order']);
                    foreach ($datas_fields as $data_field) {
                        $list_fields[$data_field['id']] = $data_field['name'];
                    }
                }

                if (count($metademands_parent) == 0) {
                    continue;
                } elseif (count($list_fields) == 0) {
                    continue;
                }
            }
            if (empty($types)) {
                $options[$key] = self::getFieldTypesName($types);
            } else {
                $options[$types] = self::getFieldTypesName($types);
            }
        }

        return \Dropdown::showFromArray($name, $options, $p);
    }

    /**
     * get field types name
     *
     * @param string $value
     *
     * @return string types
     */
    public static function getFieldTypesName($value = '')
    {
        global $PLUGIN_HOOKS;

        $class = self::getClassFromType($value);

        switch ($value) {
            case 'title':
            case 'title-block':
            case 'informations':
            case 'text':
            case 'tel':
            case 'email':
            case 'url':
            case 'textarea':
            case 'dropdown_meta':
            case 'dropdown_object':
            case 'dropdown':
            case 'dropdown_multiple':
            case 'dropdown_ldap':
            case 'checkbox':
            case 'radio':
            case 'yesno':
            case 'number':
            case 'range':
            case 'freetable':
            case 'date':
            case 'time':
            case 'datetime':
            case 'date_interval':
            case 'datetime_interval':
            case 'upload':
            case 'link':
            case 'basket':
            case 'signature':
                return $class::getTypeName();
            case 'parent_field':
                return __('Father\'s field', 'metademands');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldTypesName($plug);

                        if (Plugin::isPluginActive($plug)
                            && is_array($new_fields)) {
                            if (isset($new_fields[$value])) {
                                return $new_fields[$value];
                            } else {
                                continue;
                            }
                            return \Dropdown::EMPTY_VALUE;
                        }
                    }
                }
                return \Dropdown::EMPTY_VALUE;
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginCaseCustomFields($plug, $name, $p)
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
                if ($item && is_callable([$item, 'addCaseCustomFields'])) {
                    return $item->addCaseCustomFields($name, $p);
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function showPluginFieldCase(
        $plug,
        $metademands_data,
        $data,
        $on_order = false,
        $itilcategories_id = 0,
        $idline = 0
    ) {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();

        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }

                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'showFieldCase'])) {
                    echo $item->showFieldCase(
                        $metademands_data,
                        $data,
                        $on_order = false,
                        $itilcategories_id = 0,
                        $idline = 0,
                    );
                }
            }
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginFieldItems($plug)
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
                if ($item && is_callable([$item, 'addFieldItems'])) {
                    return $item->addFieldItems();
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginDropdownFieldItems($plug)
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
                if ($item && is_callable([$item, 'addDropdownFieldItems'])) {
                    return $item->addDropdownFieldItems();
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginDropdownMultipleFieldItems($plug)
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
                if ($item && is_callable([$item, 'addDropdownMultipleFieldItems'])) {
                    return $item->addDropdownMultipleFieldItems();
                }
            }
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     *
     * @return void
     */
    public static function addPluginTextFieldItems($plug)
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
                if ($item && is_callable([$item, 'addTextFieldItems'])) {
                    return $item->addTextFieldItems();
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function getPluginFieldTypesName($plug)
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
                if ($item && is_callable([$item, 'getFieldTypesName'])) {
                    return $item->getFieldTypesName();
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function getPluginFieldItemsName($plug)
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
                if ($item && is_callable([$item, 'getFieldItemsName'])) {
                    return $item->getFieldItemsName();
                }
            }
        }
    }


    /**
     * saves data fields option from plugins
     *
     * @param $plug
     */
    public static function getPluginSaveOptions($plug, $params)
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
                    if ($item && is_callable([$item, 'saveOptions'])) {
                        return $item->saveOptions($params);
                    }
                }
            }
        }
    }

    /**
     * Show field item dropdown
     *
     * @param  $name
     * @param array $param
     *
     * @return false|int|string of items
     */
    public static function dropdownFieldItems($typefield, $param = [])
    {
        global $PLUGIN_HOOKS;

        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        $name = "item";

        $type_fields = Dropdownmeta::$dropdown_meta_items;
        $type_fields_multiple = Dropdownmultiple::$dropdown_multiple_items;
        if (isset($p["criteria"])) {
            $type_fields_multiple = $p["criteria"];
        }

        switch ($typefield) {
            case "dropdown_multiple":
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $options[0] = \Dropdown::EMPTY_VALUE;
                }

                foreach ($type_fields_multiple as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName("dropdown_multiple", $items);
                    } else {
                        $options[$items] = self::getFieldItemsName("dropdown_multiple", $items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownMultipleFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return \Dropdown::showFromArray($name, $options, $p);
            case "dropdown":
                $options = \Dropdown::getStandardDropdownItemTypes();
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $allowedDropdownValues[0] = \Dropdown::EMPTY_VALUE;
                    $options = array_merge($allowedDropdownValues, $options);
                }

                return \Dropdown::showFromArray($name, $options, $p);
            case "dropdown_meta":
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $options[0] = \Dropdown::EMPTY_VALUE;
                }
                foreach ($type_fields as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName("dropdown_meta", $items);
                    } else {
                        $options[$items] = self::getFieldItemsName("dropdown_meta", $items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return \Dropdown::showFromArray($name, $options, $p);
            case "dropdown_object":
                $options = self::getGlpiObject();
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $allowedDropdownValues[0] = \Dropdown::EMPTY_VALUE;
                    $options = array_merge($allowedDropdownValues, $options);
                }
                return \Dropdown::showFromArray($name, $options, $p);
            case "basket":
                $options = new Basketobjecttype();
                return $options->Dropdown(["name" => $name, 'value' => $p['value']]);
            default:

                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $newcase = self::addPluginCaseCustomFields($plug, $name, $p);
                    }
                    return $newcase;
                }
                break;
        }
    }

    /**
     * get field items name
     *
     * @param string $value
     *
     * @return string item
     */
    public static function getFieldItemsName($type = '', $value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($type) {
            case 'basket':
                $basketobject = new Basketobjecttype();
                $name = \Dropdown::EMPTY_VALUE;
                if ($basketobject->getFromDB($value)) {
                    $name = $basketobject->getName();
                }
                return $name;
        }

        switch ($value) {
            case 'other':
                return __('My values', 'metademands');
            case 'ITILCategory_Metademands':
                return __('Category of the metademand', 'metademands');
            case 'mydevices':
                return __('My devices');
            case 'urgency':
                return __('Urgency');
            case 'impact':
                return __('Impact');
            case 'priority':
                return __('Priority');
            case 'user':
                return __('User');
            case 'appliance':
                return __('Appliance');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldItemsName($plug);
                        if (Plugin::isPluginActive($plug)
                            && is_array($new_fields)) {
                            if (isset($new_fields[$value])) {
                                return $new_fields[$value];
                            } else {
                                continue;
                            }
                            return \Dropdown::EMPTY_VALUE;
                        }
                    }
                }
                $dbu = new DbUtils();
                if (!is_numeric($value)) {
                    if ($value != null && $item = $dbu->getItemForItemtype($value)) {
                        if (is_callable([$item, 'getTypeName'])) {
                            return $item::getTypeName();
                        }
                    }
                }
                return \Dropdown::EMPTY_VALUE;
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function getPluginFieldItemsType($plug)
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
                if ($item && is_callable([$item, 'getFieldItemsType'])) {
                    return $item->getFieldItemsType();
                }
            }
        }
    }


    /**
     * Return a Field object for the given ID, memoised for the duration of the request.
     * Avoids repeated DB hits when the same field ID is referenced in multiple loops.
     */
    public static function getCachedField(int $id): ?self
    {
        static $cache = [];
        if (!isset($cache[$id])) {
            $f = new self();
            $cache[$id] = $f->getFromDB($id) ? $f : null;
        }
        return $cache[$id];
    }

    public static function getAllParamsFromField($field)
    {
        static $metademand_cache = [];

        $metademand = new Metademand();
        $metademand_params = new FieldParameter();
        $field_custom = new FieldCustomvalue();
        $freetablefield = new Freetablefield();

        $params = [];

        $id = $field->getID();
        if (isset($id) && $id > 0) {

            // FieldParameter — use static cache (preloadForFields() may have warmed it)
            $fp_cached = FieldParameter::getFromStaticCache($id);
            if ($fp_cached === false) {
                $metademand_params->getFromDBByCrit(["plugin_metademands_fields_id" => $id]);
            } else {
                $metademand_params->fields = $fp_cached ?? [];
            }

            // Parent metademand — static cache keyed by metademand ID
            $meta_id = $field->fields['plugin_metademands_metademands_id'] ?? 0;
            if ($meta_id > 0) {
                if (!isset($metademand_cache[$meta_id])) {
                    $metademand->getFromDB($meta_id);
                    $metademand_cache[$meta_id] = $metademand->fields;
                } else {
                    $metademand->fields = $metademand_cache[$meta_id];
                }
            }

            $default_values = [];
            if (isset($metademand_params->fields['default'])) {
                $default_values = FieldParameter::_unserialize($metademand_params->fields['default']);
            }

            $custom_values = [];
            if (isset($metademand_params->fields['custom'])) {
                $custom_values = FieldParameter::_unserialize($metademand_params->fields['custom']);
            }

            $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
            $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

            if (isset($field->fields['type'])
                && (in_array($field->fields['type'], $allowed_customvalues_types)
                    || in_array($field->fields['item'], $allowed_customvalues_items))
                && !in_array($field->fields["item"], self::$field_specificobjects)
                && $field->fields['item'] != "Appliance"
                && $field->fields['item'] != "Group") {
                $custom_values = [];
                // FieldCustomvalue — use static cache (preloadForFields() may have warmed it)
                $fc_cached = FieldCustomvalue::getFromStaticCache($id);
                if ($fc_cached === false) {
                    $customs = $field_custom->find(["plugin_metademands_fields_id" => $id], "rank");
                } else {
                    $customs = $fc_cached;
                }
                if ($customs && count($customs) > 0) {
                    $custom_values = $customs;
                    $default_values = [];
                }
            }

            if (isset($field->fields['type'])
                && $field->fields['type'] == "freetable") {
                $custom_values = [];
                $ft_cached = Freetablefield::getFromStaticCache($id);
                $customs = ($ft_cached !== false) ? $ft_cached : $freetablefield->find(["plugin_metademands_fields_id" => $field->getID()], "rank");
                if (!empty($customs)) {
                    $custom_values = $customs;
                    $default_values = [];
                }
            }

            $params = [
                'id' => $field->fields['id'],
                'object_to_create' => $metademand->fields['object_to_create'] ?? 0,
                'is_order' => $metademand->fields['is_order'] ?? 0,
                'name' => $field->fields['name'],
                'comment' => $field->fields['comment'],
                'label2' => $field->fields['label2'],
                'rank' => $field->fields['rank'],
                'order' => $field->fields['order'],
                'plugin_metademands_metademands_id' => $field->fields["plugin_metademands_metademands_id"],
                'plugin_metademands_fields_id' => $field->getID(),
                'item' => $field->fields['item'],
                'type' => $field->fields['type'],
                'row_display' => $metademand_params->fields['row_display'] ?? 0,
                'display_type' => $metademand_params->fields['display_type'] ?? 0,
                'hide_title' => $metademand_params->fields['hide_title'] ?? 0,
                'is_basket' => $metademand_params->fields['is_basket'] ?? 0,
                'color' => $metademand_params->fields['color'] ?? "",
                'icon' => $metademand_params->fields['icon'] ?? "",
                'is_mandatory' => $metademand_params->fields['is_mandatory'] ?? 0,
                'used_by_ticket' => $metademand_params->fields['used_by_ticket'] ?? 0,
                'used_by_child' => $metademand_params->fields['used_by_child'] ?? 0,
                'use_richtext' => $metademand_params->fields['use_richtext'] ?? 0,
                'default_use_id_requester' => $metademand_params->fields['default_use_id_requester'] ?? 0,
                'default_use_id_requester_supervisor' => $metademand_params->fields['default_use_id_requester_supervisor'] ?? 0,
                'readonly' => $metademand_params->fields['readonly'] ?? 0,
                'max_upload' => $metademand_params->fields['max_upload'] ?? 0,
                'regex' => $metademand_params->fields['regex'] ?? 0,
                'use_future_date' => $metademand_params->fields['use_future_date'] ?? 0,
                'use_date_now' => $metademand_params->fields['use_date_now'] ?? 0,
                'additional_number_day' => $metademand_params->fields['additional_number_day'] ?? 0,
                'display_type' => $metademand_params->fields['display_type'] ?? 0,
                'informations_to_display' => $metademand_params->fields['informations_to_display'] ?? ['fullname'],
                'link_to_user' => $metademand_params->fields["link_to_user"] ?? 0,
                'hidden' => $metademand_params->fields["hidden"] ?? 0,
                'authldaps_id' => $metademand_params->fields["authldaps_id"] ?? 0,
                'ldap_filter' => $metademand_params->fields["ldap_filter"] ?? "",
                'ldap_attribute' => $metademand_params->fields["ldap_attribute"] ?? 0,
                'root_items_id' => (int) ($metademand_params->fields["root_items_id"] ?? 0),
                'location_depth' => (int) ($metademand_params->fields["location_depth"] ?? 0),
                'custom_values' => $custom_values,
                'default_values' => $default_values,
            ];
        }
        return $params;
    }

    /**
     * @param        $data
     * @param        $metademands_data
     * @param bool $preview
     * @param string $config_link
     * @param int $itilcategories_id
     */
    public static function displayFieldByType(
        $metademands,
        $metademands_data,
        $data,
        $preview = false,
        $itilcategories_id = 0,
        $count = 0
    ) {
        global $PLUGIN_HOOKS;

        $fieldparameter = new FieldParameter();
        if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $data['id']])) {
            unset($fieldparameter->fields['plugin_metademands_fields_id']);
            unset($fieldparameter->fields['id']);

            $params = $fieldparameter->fields;
            $data = array_merge($data, $params);

            if (isset($fieldparameter->fields['default'])) {
                $data['default_values'] = FieldParameter::_unserialize(
                    $fieldparameter->fields['default'],
                );
            }

            if (isset($fieldparameter->fields['custom'])) {
                $data['custom_values'] = FieldParameter::_unserialize(
                    $fieldparameter->fields['custom'],
                );
            }
        }

        $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

        if (isset($data['type'])
            && (in_array($data['type'], $allowed_customvalues_types)
                || in_array($data['item'], $allowed_customvalues_items))
            && $data['item'] != "urgency"
            && $data['item'] != "priority"
            && $data['item'] != "impact") {
            $field_custom = new FieldCustomvalue();
            if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $data['id']], "rank")) {
                if (count($customs) > 0) {
                    $data['custom_values'] = $customs;
                }
            }
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

        // start wrapper div classes
        if ($data['type'] == 'title') {
            $data['row_display'] = 1;
            $data['is_mandatory'] = 0;
        }
        $class = "";
        if (isset($data['row_display'])
            && $data['row_display'] == 1 && $data['type'] == "link") {
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

        if ($data['type'] != 'title-block'
            && $data['type'] != 'title') {
            if (isset($data['row_display'])
                && $data['row_display'] == 1) {
                $bottomclass = "col-md-12 md-bottom";
            } else {
                $bottomclass = "col-md-6 md-bottom";
            }
        }

        $is_basket = ($data['type'] == 'basket');
        if (isset($data['row_display']) && $data['row_display'] == 1) {
            $wrapper_class = "$bottomclass $class";
            $count++;
        } elseif ($data['type'] != 'title-block' && $data['type'] != 'title') {
            $wrapper_class = "$bottomclass $class";
        } else {
            $wrapper_class = "col-md-12 $bottomclass $class";
        }

        $config_link = "";
        if (Session::getCurrentInterface() == 'central' && $preview) {
            $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL(
                Field::class,
            ) . "?id=" . $data['id'] . "'>";
            $config_link .= "<i class='ti ti-settings'></i></a>";
        }
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        $upload = "";
        if ($data['type'] == "upload") {
            $max = "";
            if ($data["max_upload"] > 0) {
                $max = "( " . sprintf(
                    __("Maximum number of documents : %s ", "metademands"),
                    $data["max_upload"],
                ) . ")";
            }

            $upload = "$max (" . Document::getMaxUploadSize() . ")";
        }

        if (empty($label = self::displayField($data['id'], 'name'))) {
            $label = "";
            if (isset($data['name'])) {
                $label = $data['name'];
            }
        }

        $hidden = $data['hidden'] ?? 0;
        if ($hidden == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
            && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $hidden = 0;
        }

        // Label block: rendered only for non-title/title-block/informations types.
        // render_label is computed on the pre-mutation type; the PLUGIN_HOOKS mutation
        // below only remaps to plugin field types, never to those display types, so the
        // same flag stays valid for the closing branch further down.
        $render_label = ($data['type'] != "title"
            && $data['type'] != "title-block"
            && $data['type'] != "informations");
        $hide_title_zero = (isset($data['hide_title']) && $data['hide_title'] == 0);
        $hidden_zero = ($hidden == 0);

        $has_icon = false;
        $icon = "";
        $show_comment_tooltip = false;
        $comment_tooltip_html = "";
        $is_mandatory_star = false;

        if ($render_label && $hide_title_zero && $hidden_zero) {
            // Icon/label/mandatory + comment tooltip are all evaluated on the pre-mutation
            // type, exactly as in the original label block.
            if ($data['icon']) {
                $has_icon = true;
                $icon = (string) $data['icon'];
            }

            if (empty($comment = self::displayField($data['id'], 'comment'))) {
                $comment = $data['comment'];
            }
            if ($data['type'] != "text"
                && $data['type'] != "tel"
                && $data['type'] != "email"
                && $data['type'] != "url"
                && !empty($comment)) {
                if ($data['use_richtext'] != 0) {
                    $show_comment_tooltip = true;
                    // display => false makes showToolTip return the HTML instead of echoing it.
                    $comment_tooltip_html = Html::showToolTip(RichText::getSafeHtml($comment), [
                        'awesome-class' => 'ti ti-info-circle',
                        'display' => false,
                    ]);
                }
            }

            $is_mandatory_star = ($data['is_mandatory'] == 1 && $data['type'] != 'parent_field');

            //use plugin fields types (mutates $data['type'] before the widget is rendered)
            if (isset($PLUGIN_HOOKS['metademands'])) {
                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                    $new_fields = self::getPluginFieldItemsType($plug);
                    if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                        if (in_array($data['type'], array_keys($new_fields))) {
                            $data['type'] = $new_fields[$data['type']];
                        }
                    }
                }
            }
        }

        // Label 2: shared by the alert block and the date-interval block. All type checks
        // below use the (possibly mutated) type, exactly as in the original.
        $has_label2 = (!empty($data['label2'])
            && $data['type'] != 'link'
            && $data['type'] != "title-block"
            && $data['type'] != "title");

        $label2 = '';
        if ($has_label2) {
            if (empty($label2 = self::displayField($data['id'], 'label2'))) {
                $label2 = htmlspecialchars_decode(stripslashes($data['label2']));
            }
        }

        $show_label2_alert = ($has_label2
            && $data['type'] != 'informations'
            && $data['type'] != 'datetime_interval'
            && $data['type'] != 'date_interval');
        $label2_alert_html = "";
        if ($show_label2_alert) {
            $label2_alert_html = RichText::getSafeHtml($label2);
        }

        // Widget: getFieldInput() echoes the widget internally but the parent_field case
        // returns a string, so capture both the buffered output and the return value.
        ob_start();
        $field_ret = self::getFieldInput($metademands_data, $data, false, $itilcategories_id, 0, $preview, $config_link);
        $field_html = ob_get_clean();
        if (is_string($field_ret)) {
            $field_html .= $field_ret;
        }

        $close_hidetitle_div = ($render_label
            && isset($data['hide_title']) && $data['hide_title'] == 1);

        // Date-interval second widget.
        $is_interval = false;
        $interval_label_html = "";
        $interval_required_class = "";
        $interval_required_icon = "";
        $interval_html = "";
        if ($has_label2) {
            if ($data['is_mandatory']) {
                $interval_required_class = "class='metademands_wizard_red'";
                $interval_required_icon = " * ";
            }
            if ($data['type'] == 'datetime_interval' || $data['type'] == 'date_interval') {
                $is_interval = true;
                // Rendered raw to avoid double-encoding the text extracted from HTML.
                $interval_label_html = RichText::getTextFromHtml($label2);
                $value2 = '';
                if (isset($data['value-2'])) {
                    $value2 = $data['value-2'];
                }
                $namefield = "field[" . $data['id'] . "-2]";
                $end = true;
                ob_start();
                switch ($data['type']) {
                    case 'date_interval':
                        Dateinterval::showWizardField($data, $namefield, $value2, $end);
                        $count++;
                        break;
                    case 'datetime_interval':
                        Datetimeinterval::showWizardField($data, $namefield, $value2, $end);
                        $count++;
                        break;
                }
                $interval_html = ob_get_clean();
            }
        }

        echo TemplateRenderer::getInstance()->render('@metademands/fields/field_wrapper.html.twig', [
            'id'                      => $data['id'],
            'wrapper_class'           => $wrapper_class,
            'is_basket'               => $is_basket,
            'render_label'            => $render_label,
            'hide_title_zero'         => $hide_title_zero,
            'hidden_zero'             => $hidden_zero,
            'has_icon'                => $has_icon,
            'icon'                    => $icon,
            'label'                   => $label,
            'upload'                  => $upload,
            'debug'                   => $debug,
            'preview'                 => (bool) $preview,
            'config_link'             => $config_link,
            'show_comment_tooltip'    => $show_comment_tooltip,
            'comment_tooltip_html'    => $comment_tooltip_html,
            'is_mandatory_star'       => $is_mandatory_star,
            'show_label2_alert'       => $show_label2_alert,
            'label2_alert_html'       => $label2_alert_html,
            'field_html'              => $field_html,
            'close_hidetitle_div'     => $close_hidetitle_div,
            'is_interval'             => $is_interval,
            'interval_label_html'     => $interval_label_html,
            'interval_required_class' => $interval_required_class,
            'interval_required_icon'  => $interval_required_icon,
            'interval_html'           => $interval_html,
        ]);
    }

    public static function getClassFromType($type)
    {
        switch ($type) {
            case 'title':
                return Title::class;
            case 'title-block':
                return Titleblock::class;
            case 'informations':
                return Information::class;
            case 'text':
                return Text::class;
            case 'tel':
                return Tel::class;
            case 'email':
                return Email::class;
            case 'url':
                return Url::class;
            case 'textarea':
                return Textarea::class;
            case 'dropdown_meta':
                return Dropdownmeta::class;
            case 'dropdown_object':
                return Dropdownobject::class;
            case 'dropdown_ldap':
                return Ldapdropdown::class;
            case 'dropdown':
                return Dropdown::class;
            case 'dropdown_multiple':
                return Dropdownmultiple::class;
            case 'checkbox':
                return Checkbox::class;
            case 'radio':
                return Radio::class;
            case 'yesno':
                return Yesno::class;
            case 'number':
                return Number::class;
            case 'range':
                return Range::class;
            case 'freetable':
                return Freetable::class;
            case 'date':
                return Date::class;
            case 'time':
                return Time::class;
            case 'datetime':
                return Datetime::class;
            case 'date_interval':
                return Dateinterval::class;
            case 'datetime_interval':
                return Datetimeinterval::class;
            case 'upload':
                return Upload::class;
            case 'link':
                return Link::class;
            case 'basket':
                return Basket::class;
            case 'signature':
                return Signature::class;
            default:
                break;
        }
    }

    /**
     * Generate the HTML to display a field
     * @param      $metademands_data
     * @param array $data row from DB with associated options, see Metademand->constructForm() for details
     * @param bool $on_order
     * @param int $itilcategories_id
     *
     * @param int $idline
     *
     * @return int|mixed|String
     */
    public static function getFieldInput(
        $metademands_data,
        $data,
        $on_order = false,
        $itilcategories_id = 0,
        $idline = 0,
        $preview = false,
        $config_link = ''
    ) {
        global $PLUGIN_HOOKS;

        $metademand = new Metademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        $field = '';
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }

        if ($on_order == false) {
            $namefield = 'field';
        } else {
            $namefield = 'field_basket_' . $idline;
        }

        $class = self::getClassFromType($data['type']);

        switch ($data['type']) {
            case 'title-block':
            case 'informations':
            case 'title':
                $class::showWizardField($data, $namefield, $value, $on_order, $preview, $config_link);
                break;
            case 'tel':
            case 'email':
            case 'textarea':
            case 'url':
            case 'dropdown_multiple':
            case 'checkbox':
            case 'radio':
            case 'yesno':
            case 'range':
            case 'number':
            case 'freetable':
            case 'date':
            case 'time':
            case 'datetime':
            case 'link':
            case 'signature':
            case 'text':
                $class::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'dropdown_object':
            case 'dropdown_ldap' :
            case 'dropdown':
            case 'dropdown_meta':
                $class::showWizardField(
                    $data,
                    $namefield,
                    $value,
                    $on_order,
                    $itilcategories_id,
                );
                break;
            case 'datetime_interval':
            case 'date_interval':
                $class::showWizardField($data, $namefield, $value, false);
                break;
            case 'upload':
                $class::showWizardField($data, $namefield, $value, $on_order, $idline);
                break;
            case 'basket':
                $class::showWizardField($data, $on_order, $itilcategories_id, $idline);
                break;
            case 'parent_field':
                foreach ($metademands_data as $metademands_data_steps) {
                    foreach ($metademands_data_steps as $line_data) {
                        foreach ($line_data['form'] as $field_id => $field_value) {
                            if (isset($data['options'])) {
                                $opts = $data['options'];

                                if (isset($opts[0]['parent_field_id'])) {
                                    $value_parent_field = '';
                                    $parent_field_id = 0;
                                    if (isset($opts[0]['parent_field_id'])) {
                                        $parent_field_id = $opts[0]['parent_field_id'];
                                        $field_parentmeta = new Field();
                                        $field_parentmeta->getFromDB($parent_field_id);
                                        $parameters = Field::getAllParamsFromField($field_parentmeta);
                                        $meta_parent_id = $field_parentmeta->fields['plugin_metademands_metademands_id'];
                                    }

                                    if (isset($_SESSION['plugin_metademands'][$meta_parent_id]['fields'][$parent_field_id])) {
                                        if (isset($_SESSION['plugin_metademands'][$meta_parent_id]['fields'][$parent_field_id])) {
                                            $value = $_SESSION['plugin_metademands'][$meta_parent_id]['fields'][$parent_field_id];
                                        } else {
                                            $value = 0;
                                        }

                                        switch ($field_parentmeta->fields['type']) {
                                            case 'dropdown_multiple':
                                                if (!empty($parameters['custom_values'])) {
                                                    $value_parent_field = $parameters['custom_values'][$parent_field_id];
                                                }
                                                break;
                                            case 'dropdown':
                                            case 'dropdown_ldap':
                                            case 'dropdown_object':
                                            case 'dropdown_meta':
                                                if (!empty($parameters['custom_values'])
                                                    && $parameters['item'] == 'other') {
                                                    $value_parent_field = $parameters['custom_values'][$parent_field_id];
                                                } else {
                                                    switch ($parameters['item']) {
                                                        case 'User':
                                                            $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                            $params['value'] = $value;
                                                            $class_parent = self::getClassFromType($field_parentmeta->fields['type']);
                                                            $value_parent_field .= $class_parent::getFieldValue($params);
                                                            break;
                                                        default:
                                                            $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                            $params['value'] = $value;
                                                            $params['item'] = $parameters['item'];
                                                            $class_parent = self::getClassFromType($field_parentmeta->fields['type']);
                                                            $value_parent_field .= $class_parent::getFieldValue($params);
                                                            break;
                                                    }
                                                }
                                                break;
                                            case 'checkbox':
                                                if (!empty($parameters['custom_values'])) {
                                                    $parameters['custom_values'] = FieldParameter::_unserialize(
                                                        $parameters['custom_values'],
                                                    );
                                                    foreach ($parameters['custom_values'] as $k => $val) {
                                                        if (!empty(
                                                            $ret = self::displayField(
                                                                $parameters["id"],
                                                                "custom" . $k,
                                                            )
                                                        )) {
                                                            $parameters['custom_values'][$k] = $ret;
                                                        }
                                                    }
                                                    $checkboxes = FieldParameter::_unserialize($value);

                                                    $custom_checkbox = [];
                                                    $value_parent_field = "";
                                                    foreach ($parameters['custom_values'] as $key => $label) {
                                                        $checked = isset($checkboxes[$key]) ? 1 : 0;
                                                        if ($checked) {
                                                            $custom_checkbox[] = $label;
                                                            $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='checkbox'>";
                                                        }
                                                    }
                                                    $value_parent_field .= implode('<br>', $custom_checkbox);
                                                }
                                                break;

                                            case 'radio':
                                                if (!empty($parameters['custom_values'])) {
                                                    $parameters['custom_values'] = FieldParameter::_unserialize(
                                                        $parameters['custom_values'],
                                                    );
                                                    foreach ($parameters['custom_values'] as $k => $val) {
                                                        if (!empty(
                                                            $ret = self::displayField(
                                                                $parameters["id"],
                                                                "custom" . $k,
                                                            )
                                                        )) {
                                                            $parameters['custom_values'][$k] = $ret;
                                                        }
                                                    }
                                                    foreach ($parameters['custom_values'] as $key => $label) {
                                                        if ($value == $key) {
                                                            $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$key' >";
                                                            $value_parent_field .= $label;
                                                            break;
                                                        }
                                                    }
                                                }
                                                break;

                                            case 'time':
                                            case 'datetime':
                                            case 'yesno':
                                            case 'date':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                $params['value'] = $value;
                                                $class_parent = self::getClassFromType($field_parentmeta->fields['type']);
                                                $value_parent_field .= $class_parent::getFieldValue($params);
                                                break;
                                            case 'datetime_interval':
                                            case 'date_interval':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                if (isset($_SESSION['plugin_metademands'][$meta_parent_id]['fields'][$data['parent_field_id'] . "-2"])) {
                                                    $value2 = $_SESSION['plugin_metademands'][$meta_parent_id]['fields'][$parent_field_id . "-2"];
                                                    $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value2 . "'>";
                                                } else {
                                                    $value2 = 0;
                                                }
                                                $params['value'] = $value;
                                                $params['value2'] = $value2;
                                                $class_parent = self::getClassFromType($field_parentmeta->fields['type']);
                                                $value_parent_field .= $class_parent::getFieldValue($params);
                                                break;

                                            case 'basket':

                                                break;
                                            default:
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                $params['value'] = $value;
                                                $class_parent = self::getClassFromType($field_parentmeta->fields['type']);
                                                $value_parent_field .= $class_parent::getFieldValue($params);
                                        }
                                    }
                                    $field .= $value_parent_field;
                                    break;
                                }
                                //                                }
                            }
                        }
                    }
                }
                break;
            default:
                //plugin case
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    $hooks_plugins = $PLUGIN_HOOKS['metademands'];
                    foreach ($hooks_plugins as $plug => $pluginclass) {
                        if (Plugin::isPluginActive($plug)) {
                            echo self::showPluginFieldCase(
                                $plug,
                                $metademands_data,
                                $data,
                                $on_order = false,
                                $itilcategories_id = 0,
                                $idline = 0,
                            );
                        }
                    }
                }
                break;
        }
        //        if ($on_order == false) {
        echo $field;
        //        } else {
        //            return $field;
        //        }
    }


    /**
     * @param        $entity
     * @param        $userid
     * @param string $filter
     * @param bool $first
     *
     * @return array|int|mixed
     */
    public static function getUserGroup($entity, $userid, $cond = '', $first = true)
    {
        global $DB;

        $dbu = new DbUtils();

        $where = [];
        if ($cond) {
            $where = $cond;
        }

        $query = [
            'SELECT' => ['glpi_groups.id'],
            'FROM' => 'glpi_groups_users',
            'INNER JOIN' => [
                'glpi_groups' => [
                    'FKEY' => [
                        'glpi_groups' => 'id',
                        'glpi_groups_users' => 'groups_id',
                    ],
                ],
            ],
            'WHERE' => [
                'users_id' => $userid,
                $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
            ] + $where,
        ];

        $rep = [];
        foreach ($DB->request($query) as $data) {
            if ($first) {
                return $data['id'];
            }
            $rep[] = $data['id'];
        }
        return ($first ? 0 : $rep);
    }


    /**
     * @param $input
     *
     * @return mixed
     */
    public static function _unserialize($input)
    {
        if (!empty($input)) {
            if (!is_array($input)) {
                $input = json_decode($input, true);
            }
            if (is_array($input) && !empty($input)) {
                foreach ($input as &$value) {
                    if ($value != null) {
                        $value = urldecode($value);
                    }
                }
            }
        }

        return $input;
    }

    /**
     * @param $metademands_id
     *
     * @return array
     */
    public function listMetademandsfields($metademands_id)
    {
        $field = new self();
        $listMetademandsFields = $field->find(['plugin_metademands_metademands_id' => $metademands_id]);

        return $listMetademandsFields;
    }


    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        // A new field always relies on the AUTO_INCREMENT primary key. Forms post
        // an empty 'id', which MySQL rejects as "Incorrect integer value: ''"
        // (and fails hard in strict mode); drop it so the key is auto-generated.
        if (isset($input['id']) && !is_numeric($input['id'])) {
            unset($input['id']);
        }

        // legacy support
        if (isset($input['existing_field_id']) && isset($input['item']) && $input['item'] == 'User') {
            if (isset($input['informations_to_display']) && $input['informations_to_display'] == '[]') {
                $input['informations_to_display'] = '["full_name"]';
            }
        }

        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        //      $meta = new Metademand();

        //      if ($meta->getFromDB($input['plugin_metademands_metademands_id'])
        //          && $meta->fields['is_order'] == 1) {
        //         $input['is_basket'] = 1;
        //      }

        if (isset($input["type"]) && $input["type"] == "checkbox") {
            $input["item"] = "checkbox";
        }
        if (isset($input["type"]) && $input["type"] == "radio") {
            $input["item"] = "radio";
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
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }
        if (isset($input["type"]) && $input["type"] == "checkbox") {
            $input["item"] = "checkbox";
        }
        if (isset($input["type"]) && $input["type"] == "radio") {
            $input["item"] = "radio";
        }

        return $input;
    }

    public function cleanDBonPurge()
    {
        $temp = new FieldParameter();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new FieldCustomvalue();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Freetablefield();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new FieldOption();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Ticket_Field();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Basketline();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Draft_Value();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Pluginfields();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']]);

        $temp = new Form_Value();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Stepform_Value();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new Condition();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);
    }

    /**
     * @param $value
     *
     * @return bool|string
     */
    public static function setColor($value)
    {
        return substr(
            substr(dechex(($value * 298)), 0, 2)
            . substr(dechex(($value * 7777)), 0, 3)
            . substr(dechex(($value * 1)), 0, 1)
            . substr(dechex(($value * 64)), 0, 1)
            . substr(dechex(($value * 13)), 0, 1)
            . substr(dechex(($value * 1)), 0, 1),
            0,
            6,
        );
    }

    /**
     * @param $input
     *
     * @return bool
     */
    public function checkMandatoryFields($input)
    {
        $msg = [];
        $checkKo = false;

        $mandatory_fields = [
            'name' => __('Label'),
            'label2' => __('Additional label', 'metademands'),
            'type' => __('Type'),
            'item' => __('Object', 'metademands'),
        ];
        $id = $input['id'] ?? 0;
        foreach ($input as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    if (($key == 'item' && ($input['type'] == 'dropdown'
                                || $input['type'] == 'dropdown_object'
                                || $input['type'] == 'dropdown_meta'))
                        || ($key == 'label2' && ($input['type'] == 'date_interval' || $input['type'] == 'datetime_interval'))) {
                        $msg[] = $mandatory_fields[$key];
                        $checkKo = true;
                    } elseif ($key != 'item' && $key != 'label2') {
                        $msg[] = $mandatory_fields[$key];
                        $checkKo = true;
                    }
                }
            }
            $_SESSION['glpi_plugin_metademands_fields'][$id][$key] = $value;
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(
                sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)),
                false,
                ERROR,
            );
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(1),
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
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id' => '814',
            'table' => $this->getTable(),
            'field' => 'rank',
            'name' => __('Block', 'metademands'),
            'datatype' => 'specific',
            'massiveaction' => true,
        ];

        $tab[] = [
            'id' => '815',
            'table' => $this->getTable(),
            'field' => 'order',
            'name' => __('Order', 'metademands'),
            'datatype' => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id' => '817',
            'table' => $this->getTable(),
            'field' => 'label2',
            'name' => __('Additional label', 'metademands'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id' => '818',
            'table' => $this->getTable(),
            'field' => 'comment',
            'name' => __('Comments'),
            'datatype' => 'text',
        ];

        //        $tab[] = [
        //            'id' => '819',
        //            'table' => $this->getTable(),
        //            'field' => 'is_mandatory',
        //            'name' => __('Mandatory field'),
        //            'datatype' => 'bool'
        //        ];
        //
        //        $tab[] = [
        //            'id' => '820',
        //            'table' => $this->getTable(),
        //            'field' => 'is_basket',
        //            'name' => __('Display into the basket', 'metademands'),
        //            'datatype' => 'bool'
        //        ];

        $tab[] = [
            'id' => '880',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '886',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __('Child entities'),
            'datatype' => 'bool',
        ];

        return $tab;
    }


    /**
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'change_color':
                echo Html::showColorField('color', ['display' => false]);
                echo "<br>"
                    . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            case 'change_icon':
                $icon_selector_id = 'icon_' . mt_rand();
                $return = Html::select(
                    'icon',
                    [],
                    [
                        'id' => $icon_selector_id,
                        'display' => false,
                        'style' => 'width:175px;',
                    ],
                );

                $return .= Html::script('js/modules/Form/WebIconSelector.js');
                $return .= Html::scriptBlock(
                    "$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );",
                );

                echo $return;
                echo "&nbsp;"
                    . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return false;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions['GlpiPlugin\Metademands\Field:change_icon'] = __("Modify icon", "metademands");
            $actions['GlpiPlugin\Metademands\Field:change_color'] = __("Modify color", "metademands");
        }

        return $actions;
    }

    /**
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case "change_icon":
                $input = $ma->getInput();

                // Defense in depth against stored XSS: the icon is later injected
                // into a class attribute at render time. Reject anything that is not
                // a plain icon class token (letters, digits, spaces, dashes,
                // underscores) so a payload can never be persisted in the first place.
                if (isset($input['icon'])
                    && $input['icon'] !== ''
                    && !preg_match('/^[a-zA-Z0-9 _-]+$/', (string) $input['icon'])) {
                    $ma->addMessage(__('You cannot do this for this field', 'metademands'));
                    foreach ($ids as $id) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                    return;
                }

                foreach ($ids as $id) {
                    $field = new Field();
                    $param = new FieldParameter();
                    $msg = MassiveAction::ACTION_OK;
                    if ($param->getFromDBByCrit(["plugin_metademands_fields_id" => $id])) {
                        $field->getFromDB($id);
                        if ($field->fields['type'] == 'title-block'
                            || $field->fields['type'] == 'title') {
                            $param->update(['id' => $param->fields['id'], 'icon' => $input['icon']]);
                        } else {
                            $ma->addMessage(__('You cannot do this for this field', 'metademands'));
                            $msg = MassiveAction::ACTION_KO;
                        }
                    }
                    $item->getFromDB($id);
                    $ma->itemDone($item->getType(), $id, $msg);
                }
                return;
            case "change_color":
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $field = new Field();
                    $param = new FieldParameter();
                    $msg = MassiveAction::ACTION_OK;
                    if ($param->getFromDBByCrit(["plugin_metademands_fields_id" => $id])) {
                        $field->getFromDB($id);
                        if ($field->fields['type'] == 'title-block'
                            || $field->fields['type'] == 'title') {
                            $param->update(['id' => $param->fields['id'], 'color' => $input['color']]);
                        } else {
                            $ma->addMessage(__('You cannot do this for this field', 'metademands'));
                            $msg = MassiveAction::ACTION_KO;
                        }
                    }
                    $item->getFromDB($id);
                    $ma->itemDone($item->getType(), $id, $msg);
                }
                return;
        }
        return;
    }

    /**
     * @param $field
     * @param $name (default '')
     * @param $values (default '')
     * @param $options   array
     *
     * @return string
     **@since version 0.84
     *
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'rank':
                $options['min'] = 1;
                $options['max'] = self::MAX_FIELDS;

                return \Dropdown::showNumber($name, $options);
                break;
            case 'order':
                return \Dropdown::showNumber($name, $options);
                break;
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * @param $rank
     * @param $fields_id
     * @param $previous_fields_id
     * @param $metademands_id
     */
    public function showOrderDropdown($params)
    {
        if (empty($params['rank'])) {
            $params['rank'] = 1;
        }
        $restrict = [
            'rank' => $params['rank'],
            'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id'],
        ];
        if (!empty($fields['id'])) {
            $restrict += ['NOT' => ['id' => $params['id']]];
        }

        $previous_fields_id = $params['plugin_metademands_fields_id'];
        $select = [\Dropdown::EMPTY_VALUE];

        foreach ($this->find($restrict, ['order']) as $id => $values) {
            $select[$id] = $values['name'] ?: $id;
            if (empty(trim($select[$id]))) {
                $select[$id] = __('ID') . " - " . $id;
            }
        }
        if (isset($params['order'])
            && $params['order'] > 0) {
            $previous_order = $params['order'] - 1;
            $field = new Field();
            if ($field->getFromDBByCrit([
                'rank' => $params['rank'],
                'order' => $previous_order,
                'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id'],
            ])) {
                $previous_fields_id = $field->fields['id'];
            }
        }

        \Dropdown::showFromArray('plugin_metademands_fields_id', $select, ['value' => $previous_fields_id]);
    }

    /**
     * @param $input
     */
    public function recalculateOrder($input)
    {
        $previousfield = new self();
        $new_order = [];

        // Set current field after selected field
        if (!empty($input['plugin_metademands_fields_id'])) {
            $previousfield->getFromDB($input['plugin_metademands_fields_id']);
            $input['order'] = $previousfield->fields['order'] + 1;
        } else {
            $input['order'] = 1;
        }

        // Calculate order
        foreach (
            $this->find(
                [
                    'rank' => $input['rank'],
                    'plugin_metademands_metademands_id' => $input["plugin_metademands_metademands_id"],
                ],
                ['order'],
            ) as $fields_id => $values
        ) {
            if ($fields_id == $input['id']) {
                $values['order'] = $input['order'];
            }
            if ($values['order'] >= $input['order'] && $values['id'] != $input['id']) {
                $new_order[$fields_id] = $values['order'] + 1;
            } else {
                $new_order[$fields_id] = $values['order'];
            }
        }
        asort($new_order);// sort by value

        // Update the new order on each fields of the rank
        $count = 1;// reinit orders with a counter
        $previous = [];
        foreach ($new_order as $fields_id => $order) {
            $previous[$count] = $fields_id;
            $myfield = new self();
            $myfield->getFromDB($fields_id);
            // Update order
            $myfield->fields['order'] = $count;
            // Update previous fields_id
            if (isset($previous[$count - 1])) {
                $myfield->fields['plugin_metademands_fields_id'] = $previous[$count - 1];
            } else {
                $myfield->fields['plugin_metademands_fields_id'] = 0;
            }
            $myfield->updateInDB(['order', 'plugin_metademands_fields_id']);
            $count++;
        }
    }


    /**
     * Returns the translation of the field
     *
     * @param  $item
     * @param  $field
     *
     * @return
     * @global  $DB
     *
     */
    public static function displayField($id, $field, $lang = '')
    {
        $res = '';

        $tr = new FieldTranslation();
        if ($tr->getFromDBByCrit([
            'items_id' => $id,
            'itemtype' => self::getType(),
            'key'      => $field,
            'language' => $_SESSION['glpilanguage'],
        ])) {
            $res = $tr->getTranslation() ?? '';
        }

        if ($lang !== '' && $lang !== $_SESSION['glpilanguage']) {
            $tr2 = new FieldTranslation();
            if ($tr2->getFromDBByCrit([
                'items_id' => $id,
                'itemtype' => self::getType(),
                'key'      => $field,
                'language' => $lang,
            ])) {
                $val2 = $tr2->getTranslation() ?? '';
                if ($val2 !== '') {
                    $res .= ' / ' . $val2;
                }
            }
        }

        return $res;
    }


    /**
     * Returns the translation of the field
     *
     * @param  $item
     * @param  $field
     *
     * @return
     * @global  $DB
     *
     */
    public static function displayCustomvaluesField($id, $field, $type = "name", $lang = '')
    {
        $field_custom = new FieldCustomvalue();
        $field_custom->getFromDB($field);
        if ($type === "name") {
            $key = "custom" . $field_custom->fields['rank'];
        } elseif ($type === "comment") {
            $key = "commentcustom" . $field_custom->fields['rank'];
        } else {
            $key = $field;
        }

        return self::displayField($id, $key, $lang);
    }

    /**
     * @return array
     */
    /**
     * @return array
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        $forbidden[] = 'merge';
        $forbidden[] = 'add_transfer_list';
        $forbidden[] = 'amend_comment';

        return $forbidden;
    }

    /**
     * @return array[]
     */
    public static function getGlpiObject()
    {
        $optgroup = [
            __("Assets") => [
                Computer::class => Computer::getTypeName(2),
                Monitor::class => Monitor::getTypeName(2),
                Software::class => Software::getTypeName(2),
                NetworkEquipment::class => NetworkEquipment::getTypeName(2),
                Peripheral::class => Peripheral::getTypeName(2),
                Printer::class => Printer::getTypeName(2),
                CartridgeItem::class => CartridgeItem::getTypeName(2),
                ConsumableItem::class => ConsumableItem::getTypeName(2),
                Phone::class => Phone::getTypeName(2),
                Line::class => Line::getTypeName(2),
            ],
            __("Assistance") => [
                \Ticket::class => \Ticket::getTypeName(2),
                Problem::class => Problem::getTypeName(2),
                TicketRecurrent::class => TicketRecurrent::getTypeName(2),
            ],
            __("Management") => [
                Budget::class => Budget::getTypeName(2),
                Supplier::class => Supplier::getTypeName(2),
                Contact::class => Contact::getTypeName(2),
                Contract::class => Contract::getTypeName(2),
                Document::class => Document::getTypeName(2),
                Project::class => Project::getTypeName(2),
                Appliance::class => Appliance::getTypeName(2),
            ],
            __("Tools") => [
                Reminder::class => __("Notes"),
                RSSFeed::class => __("RSS feed"),
            ],
            __("Administration") => [
                User::class => User::getTypeName(2),
                \Group::class => \Group::getTypeName(2),
                Entity::class => Entity::getTypeName(2),
                Profile::class => Profile::getTypeName(2),
            ],
        ];
        if (class_exists(PassiveDCEquipment::class)) {
            // Does not exists in GLPI 9.4
            $optgroup[__("Assets")][PassiveDCEquipment::class] = PassiveDCEquipment::getTypeName(2);
        }
        //TODO replace by GLPI
        //        $plugin = new Plugin();
        //        if ($plugin->isActivated("genericobject")) {
        //            foreach (PluginGenericobjectType::getTypes() as $id => $objecttype) {
        //                $itemtype = $objecttype['itemtype'];
        //                if (class_exists($itemtype)) {
        //                    $item = new $itemtype();
        //                    $optgroup[__("Assets")][$item::class] = $item::getTypeName(2);
        //                }
        //            }
        //        }

        return $optgroup;
    }

    public static function getDeviceName($value)
    {
        global $DB, $CFG_GLPI;
        $userID = Session::getLoginUserID();
        $entity_restrict = $_SESSION['glpiactiveentities'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]
            & pow(2, \Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => \Dropdown::EMPTY_VALUE];
            $devices = [];

            // My items
            foreach ($CFG_GLPI["assignable_types"] as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                    && \Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                        'FROM' => $itemtable,
                        'WHERE' => [
                            'users_id' => $userID,
                        ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                        'ORDER' => $item->getNameField(),
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'glpi_groups_users.groups_id',
                        'glpi_groups.name',
                    ],
                    'FROM' => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_groups' => [
                            'ON' => [
                                'glpi_groups_users' => 'groups_id',
                                'glpi_groups' => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_groups_users.users_id' => $userID,
                    ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true),
                ]);

                $devices = [];
                $groups = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups = array_merge($groups, $a_groups);
                    }

                    $itemtypes = $CFG_GLPI["linkgroup_types"];
                    //                    if (count($limit) > 0) {
                    //                        $itemtypes = $limit;
                    //                    }
                    foreach ($itemtypes as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                            && \Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria = [
                                'FROM' => $itemtable,
                                'WHERE' => [
                                    'groups_id' => $groups,
                                ] + getEntitiesRestrictCriteria(
                                    $itemtable,
                                    '',
                                    $entity_restrict,
                                    $item->maybeRecursive(),
                                ),
                                'ORDER' => 'name',
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups', 'metademands')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect(
                    $CFG_GLPI['software_types'],
                    $_SESSION["glpiactiveprofile"]["helpdesk_item_type"],
                );
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                            'SELECT' => [
                                'glpi_softwareversions.name AS version',
                                'glpi_softwares.name AS name',
                                'glpi_softwares.id',
                            ],
                            'DISTINCT' => true,
                            'FROM' => 'glpi_items_softwareversions',
                            'LEFT JOIN' => [
                                'glpi_softwareversions' => [
                                    'ON' => [
                                        'glpi_items_softwareversions' => 'softwareversions_id',
                                        'glpi_softwareversions' => 'id',
                                    ],
                                ],
                                'glpi_softwares' => [
                                    'ON' => [
                                        'glpi_softwareversions' => 'softwares_id',
                                        'glpi_softwares' => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                'glpi_items_softwareversions.itemtype' => $itemtype,
                                'glpi_softwares.is_helpdesk_visible' => 1,
                            ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                            'ORDERBY' => 'glpi_softwares.name',
                        ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"],
                                        ),
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer'])
                && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                        && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                            'SELECT' => "$itemtable.*",
                            'DISTINCT' => true,
                            'FROM' => 'glpi_assets_assets_peripheralassets',
                            'LEFT JOIN' => [
                                $itemtable => [
                                    'ON' => [
                                        'glpi_assets_assets_peripheralassets' => 'itemtype_peripheral',
                                        $itemtable => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                'glpi_assets_assets_peripheralassets.itemtype_asset' => $itemtype,
                                'glpi_assets_assets_peripheralassets.items_id_asset' => $already_add['Computer'],
                            ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                            'ORDERBY' => "$itemtable.name",
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }
        }
        $array = explode('_', $value);
        $itemType = $array[0];
        $item_id = $array[1] ?? 0;

        $return = "";
        if (getItemForItemtype($itemType)) {
            $item = new $itemType();
            $item->getFromDB($item_id);
            $return = $itemType . " - " . $item->fields['name'] . " (" . $item_id . ")";
        }
    }

    /**
     * Make a select box for Ticket my devices
     *
     * @param integer $userID User ID for my device section (default 0)
     * @param integer $entity_restrict restrict to a specific entity (default -1)
     * @param int $itemtype of selected item (default 0)
     * @param integer $items_id of selected item (default 0)
     * @param array $options array of possible options:
     *    - used     : ID of the requester user
     *    - multiple : allow multiple choice
     *
     * @return void
     */
    public static function dropdownMyDevices(
        $userID = 0,
        $entity_restrict = -1,
        $itemtype = 0,
        $items_id = 0,
        $options = [],
        $limit = [],
        $display = true
    ) {
        global $DB, $CFG_GLPI;

        $params = [
            'tickets_id' => 0,
            'used' => [],
            'multiple' => false,
            'name' => 'my_items',
            'value' => 0,
            'rand' => mt_rand(),
        ];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        if ($userID == 0) {
            $userID = Session::getLoginUserID();
        }

        $rand = $params['rand'];
        $already_add = $params['used'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(2, \Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => \Dropdown::EMPTY_VALUE];
            $devices = [];

            $itemtypes = $CFG_GLPI["assignable_types"];
            if (count($limit) > 0) {
                $itemtypes = $limit;
            }

            // My items
            foreach ($itemtypes as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                    && \Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                        'FROM' => $itemtable,
                        'WHERE' => [
                            'users_id' => $userID,
                        ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                        'ORDER' => $item->getNameField(),
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'glpi_groups_users.groups_id',
                        'glpi_groups.name',
                    ],
                    'FROM' => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_groups' => [
                            'ON' => [
                                'glpi_groups_users' => 'groups_id',
                                'glpi_groups' => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_groups_users.users_id' => $userID,
                    ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true),
                ]);

                $devices = [];
                $groups = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups = array_merge($groups, $a_groups);
                    }

                    foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                            && \Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria = [
                                'FROM' => $itemtable,
                                'LEFT JOIN' => [
                                    'glpi_groups_items' => [
                                        'ON' => [
                                            'glpi_groups_items' => 'items_id',
                                            $itemtable => 'id',
                                            [
                                                'AND' => [
                                                    'glpi_groups_items.itemtype' => $itemtype,
                                                    'glpi_groups_items.type' => Group_Item::GROUP_TYPE_NORMAL,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'WHERE' => [
                                    'glpi_groups_items.groups_id' => $groups,
                                ] + getEntitiesRestrictCriteria(
                                    $itemtable,
                                    '',
                                    $entity_restrict,
                                    $item->maybeRecursive(),
                                ),
                                'ORDER' => 'name',
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $itemtypes)
                && in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect(
                    $CFG_GLPI['software_types'],
                    $_SESSION["glpiactiveprofile"]["helpdesk_item_type"],
                );
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                            'SELECT' => [
                                'glpi_softwareversions.name AS version',
                                'glpi_softwares.name AS name',
                                'glpi_softwares.id',
                            ],
                            'DISTINCT' => true,
                            'FROM' => 'glpi_items_softwareversions',
                            'LEFT JOIN' => [
                                'glpi_softwareversions' => [
                                    'ON' => [
                                        'glpi_items_softwareversions' => 'softwareversions_id',
                                        'glpi_softwareversions' => 'id',
                                    ],
                                ],
                                'glpi_softwares' => [
                                    'ON' => [
                                        'glpi_softwareversions' => 'softwares_id',
                                        'glpi_softwares' => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                'glpi_items_softwareversions.itemtype' => $itemtype,
                                'glpi_softwares.is_helpdesk_visible' => 1,
                            ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                            'ORDERBY' => 'glpi_softwares.name',
                        ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"],
                                        ),
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer']) && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (count($limit) > 0) {
                        if (!in_array($itemtype, $limit)) {
                            continue;
                        }
                    }

                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                        && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                            'SELECT' => "$itemtable.*",
                            'DISTINCT' => true,
                            'FROM' => 'glpi_assets_assets_peripheralassets',
                            'LEFT JOIN' => [
                                $itemtable => [
                                    'ON' => [
                                        'glpi_assets_assets_peripheralassets' => 'itemtype_peripheral',
                                        $itemtable => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                'glpi_assets_assets_peripheralassets.itemtype_asset' => $itemtype,
                                'glpi_assets_assets_peripheralassets.items_id_asset' => $already_add['Computer'],
                            ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                            'ORDERBY' => "$itemtable.name",
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }

            $return = "<span id='show_items_id_requester'>";
            $required = $params['required'] ?? 0;
            $return .= \Dropdown::showFromArray(
                $params['name'],
                $my_devices,
                ['rand' => $rand, 'display' => false, 'value' => $params['value'], 'required' => $required],
            );
            $return .= "</span>";

            if ($display) {
                echo $return;
            } else {
                return $return;
            }
            // Auto update summary of active or just solved tickets
            //         $params = ['my_items' => '__VALUE__'];
            //
            //         Ajax::updateItemOnSelectEvent("dropdown_my_items$rand", "item_ticket_selection_information$rand",
            //                                       $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php",
            //                                       $params);
        }
    }

    public function getProfileJoinCriteria()
    {
        return [
            'INNER JOIN' => [
                Profile_User::getTable() => [
                    'ON' => [
                        Profile_User::getTable() => 'users_id',
                        User::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => getEntitiesRestrictCriteria(
                Profile_User::getTable(),
                'entities_id',
                $_SESSION['glpiactiveentities'],
                true,
            ),
        ];
    }

    /**
     * Get request criteria to select uniques users
     *
     * @return array
     * @since 9.4
     *
     */
    final public function getDistinctUserCriteria()
    {
        return [
            'SELECT' => [
                User::getTable() . '.id AS users_id',
                User::getTable() . '.language AS language',
            ],
            'DISTINCT' => true,
        ];
    }


    public function post_addItem()
    {
        $pluginField = new Pluginfields();
        $input = [];
        if (isset($this->input['plugin_fields_fields_id'])) {
            $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
            $input['plugin_metademands_fields_id'] = $this->fields['id'];
            $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
            $pluginField->add($input);
        }
    }

    public function post_updateItem($history = 1)
    {
        $pluginField = new Pluginfields();
        if (isset($this->input['plugin_fields_fields_id'])) {
            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->fields['id']])) {
                $input = [];
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['id'];
                $input['id'] = $pluginField->fields['id'];
                $pluginField->update($input);
            } else {
                $input = [];
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['id'];
                $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
                $pluginField->add($input);
            }
        }
    }

    public static function searchForm($item, $cond)
    {
        echo self::getSearchForm($item, $cond);
    }

    /**
     * Build the filter form shown above the field list.
     *
     * @param       $item the Metademand the tab is displayed for
     * @param array $cond current filter, as stored in the session
     *
     * @return string
     */
    private static function getSearchForm($item, $cond)
    {
        global $DB;

        $params = $cond ?? [];

        $p['type'] = '';
        $p['item'] = '';
        $p['rank'] = 0;
        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $iterator = $DB->request([
            'SELECT' => ['MAX' => 'rank AS maxrank'],
            'FROM' => 'glpi_plugin_metademands_fields',
            'WHERE' => [
                'plugin_metademands_metademands_id' => $item->getID(),
            ],
        ]);

        $max = Field::MAX_FIELDS;
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $max = $data['maxrank'];
            }
        }

        // The rand has to be generated here: with 'display' => false the dropdown helpers
        // return their markup instead of the rand, which the AJAX observer below needs.
        $mrand = mt_rand();

        $show_item = in_array($p['type'], self::$field_withobjects);
        $item_dropdown_html = '';
        if ($show_item) {
            // Belt and braces: the plugin branch of dropdownFieldItems() may write to the
            // output buffer rather than honour 'display'.
            ob_start();
            $returned = self::dropdownFieldItems($p['type'], ['value' => $p["item"],
                'with_empty_value' => true,
                'display' => false,
            ]);
            $item_dropdown_html = ob_get_clean() . (is_string($returned) ? $returned : '');
        }

        return TemplateRenderer::getInstance()->render('@metademands/field_search_form.html.twig', [
            'form_action' => PLUGIN_METADEMANDS_WEBDIR . '/front/field.php',
            'block_dropdown_html' => \Dropdown::showNumber('block', [
                'value' => $p['rank'],
                'min' => 1,
                'max' => $max,
                'toadd' => [0 => \Dropdown::EMPTY_VALUE],
                'display' => false,
            ]),
            'type_dropdown_html' => self::dropdownFieldTypes(
                self::$field_types,
                [
                    'value' => $p['type'],
                    'metademands_id' => $item->getID(),
                    'on_change' => 'plugin_metademands_reloaditem();',
                    'rand' => $mrand,
                    'display' => false,
                ],
            ),
            'show_item' => $show_item,
            'item_dropdown_html' => $item_dropdown_html,
            'reload_script_html' => Html::scriptBlock(
                'function plugin_metademands_reloaditem() {'
                . Ajax::updateItemJsCode(
                    'plugin_metademands_item',
                    PLUGIN_METADEMANDS_WEBDIR . '/ajax/reloaditem.php',
                    ['action' => 'reloaditem', 'type' => '__VALUE__'],
                    'dropdown_type' . $mrand,
                    false,
                )
                . '};',
            ),
            'hidden_html' => Html::hidden('plugin_metademands_metademands_id', ['value' => $item->getID()]),
            'submit_html' => Html::submit(_sx('button', 'Search'), ['name' => 'search',
                'class' => 'btn btn-primary',
            ]),
            'close_form_html' => Html::closeForm(false),
        ]);
    }


    /**
     * @param array $params
     */
    public function reorder(array $params)
    {
        if (isset($params['old_order'])
            && isset($params['new_order'])) {
            $crit = [
                'order' => $params['old_order'],
                'rank' => $params['rank'],
                'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id'],
            ];

            $itemMove = new self();
            $itemMove->getFromDBByCrit($crit);

            if (isset($itemMove->fields["id"])) {
                // Reorganization of all fields
                if ($params['old_order'] < $params['new_order']) {
                    $toUpdateList = $this->find([
                        '`order`' => ['>', $params['old_order']],
                        'order' => ['<=', $params['new_order']],
                        'rank' => $params['rank'],
                        'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id'],
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id' => $toUpdate['id'],
                            'order' => $toUpdate['order'] - 1,
                        ]);
                    }
                } else {
                    $toUpdateList = $this->find([
                        '`order`' => ['<', $params['old_order']],
                        'order' => ['>=', $params['new_order']],
                        'rank' => $params['rank'],
                        'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id'],
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id' => $toUpdate['id'],
                            'order' => $toUpdate['order'] + 1,
                        ]);
                    }
                }

                if (isset($itemMove->fields["id"])
                    && $itemMove->fields['id'] > 0) {
                    $this->update([
                        'id' => $itemMove->fields['id'],
                        'order' => $params['new_order'],
                    ]);
                }
            }
        }
    }

    public static function isSequentialFromOne(array $arr)
    {
        if (empty($arr) || $arr[0] !== 1) {
            return false; // Vérifie que le tableau n'est pas vide et commence bien par 0
        }

        for ($i = 1; $i < count($arr); $i++) {
            if ($arr[$i] - $arr[$i - 1] !== 1) {
                return false; // Vérifie que la progression est bien de +1
            }
        }
        return true;
    }

    public static function fixOrders(array $data)
    {
        // Extraire les clés du tableau
        $keys = array_keys($data);

        // Réinitialiser le rank à partir de 0
        foreach ($keys as $index => $key) {
            $data[$key]['order'] = $index + 1;
        }

        return $data;
    }

    public function listTranslationsHandlers(): array
    {
        $key = sprintf('%s_%d', static::getType(), $this->getID());
        $category = $this->fields['name'] ?? '';
        $handlers = [];

        $handlers[$key][] = new TranslationHandler(
            item: $this,
            key: 'name',
            name: __('Name'),
            value: $this->fields['name'],
            category: $category,
        );

        $handlers[$key][] = new TranslationHandler(
            item: $this,
            key: 'label2',
            name: __('Additional label', 'metademands'),
            value: $this->fields['label2'],
            is_rich_text: true,
            category: $category,
        );

        $handlers[$key][] = new TranslationHandler(
            item: $this,
            key: 'comment',
            name: __('Comments'),
            value: $this->fields['comment'],
            is_rich_text: true,
            category: $category,
        );

        $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;
        $item_field = $this->fields['item'] ?? '';

        if (
            isset($this->fields['type'])
            && (
                in_array($this->fields['type'], $allowed_customvalues_types)
                || in_array($item_field, $allowed_customvalues_items)
            )
            && !in_array($item_field, ['urgency', 'priority', 'impact'])
        ) {
            $customs = (new FieldCustomvalue())->find(
                ['plugin_metademands_fields_id' => $this->getID()],
                'rank',
            );
            foreach ($customs as $custom) {
                $rank = $custom['rank'];
                $handlers[$key][] = new TranslationHandler(
                    item: $this,
                    key: 'custom' . $rank,
                    name: $custom['name'],
                    value: $custom['name'],
                    category: $category,
                );
                $handlers[$key][] = new TranslationHandler(
                    item: $this,
                    key: 'commentcustom' . $rank,
                    name: __('Comment') . ' ' . $custom['name'],
                    value: null,
                    category: $category,
                );
            }
        }

        if (isset($this->fields['type']) && $this->fields['type'] === 'freetable') {
            $cols = (new Freetablefield())->find(
                ['plugin_metademands_fields_id' => $this->getID()],
                'rank',
            );
            foreach ($cols as $col) {
                $handlers[$key][] = new TranslationHandler(
                    item: $this,
                    key: 'freetablecol' . $col['rank'],
                    name: $col['name'],
                    value: $col['name'],
                    category: $category,
                );
            }
        }

        return $handlers;
    }
}
