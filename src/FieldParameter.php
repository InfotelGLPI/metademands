<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2026 by the Metademands Development Team.

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

use ChangeTemplate;
use CommonDBChild;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Migration;
use Plugin;
use PluginFieldsContainer;
use PluginFieldsField;
use ProblemTemplate;
use Search;
use Session;
use TicketTemplate;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class FieldParameter
 */
class FieldParameter extends CommonDBChild
{
    public static $itemtype = Field::class;
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    public static $rightname = 'plugin_metademands';

    public static $allowed_parameters_types = [
        'title',
        'title-block',
        'informations',
        'text',
        'tel',
        'email',
        'url',
        'textarea',
        'dropdown_meta',
        'dropdown_object',
        'dropdown_ldap',
        'dropdown',
        'dropdown_multiple',
        'checkbox',
        'yesno',
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
    ];
    public static $allowed_parameters_items = ['User', 'Group'];

    /** @var array<int, array|null> Row indexed by plugin_metademands_fields_id, null = absent */
    private static array $row_cache = [];

    /**
     * Batch-load FieldParameter rows for the given field IDs into the static cache.
     * Subsequent calls to getFromStaticCache() will not hit the database.
     */
    public static function preloadForFields(array $field_ids): void
    {
        global $DB;

        if (empty($field_ids)) {
            return;
        }
        $uncached = array_diff(array_map('intval', $field_ids), array_keys(self::$row_cache));
        if (empty($uncached)) {
            return;
        }
        foreach ($uncached as $id) {
            self::$row_cache[$id] = null;
        }
        foreach ($DB->request([
            'FROM'  => 'glpi_plugin_metademands_fieldparameters',
            'WHERE' => ['plugin_metademands_fields_id' => $uncached],
        ]) as $row) {
            self::$row_cache[(int) $row['plugin_metademands_fields_id']] = $row;
        }
    }

    /**
     * Return the cached row for this field, or null if none exists.
     * Returns false when the cache has not been warmed yet (unknown).
     *
     * @return array|null|false  array = found, null = not found, false = not preloaded
     */
    public static function getFromStaticCache(int $field_id)
    {
        return array_key_exists($field_id, self::$row_cache) ? self::$row_cache[$field_id] : false;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Parameter', 'Parameters', $nb, 'metademands');
    }


    public static function getIcon()
    {
        return "ti ti-settings-bolt";
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
                        `plugin_metademands_fields_id`        int {$default_key_sign} NOT NULL  DEFAULT '0',
                        `custom`                              text COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
                        `default`                             text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `hide_title`                          tinyint      NOT NULL           DEFAULT '0',
                        `is_mandatory`                        int          NOT NULL           DEFAULT '0',
                        `max_upload`                          int          NOT NULL           DEFAULT 0,
                        `regex`                               varchar(255) NOT NULL           DEFAULT '',
                        `color`                               varchar(255)                    DEFAULT NULL,
                        `row_display`                         tinyint                         DEFAULT 0,
                        `is_basket`                           tinyint                         DEFAULT 0,
                        `display_type`                        int                             DEFAULT 0,
                        `used_by_ticket`                      int          NOT NULL           DEFAULT '0',
                        `used_by_child`                       tinyint                         DEFAULT 0,
                        `link_to_user`                        int                             DEFAULT 0,
                        `default_use_id_requester`            int {$default_key_sign}         DEFAULT 0,
                        `default_use_id_requester_supervisor` int {$default_key_sign}         DEFAULT 0,
                        `use_future_date`                     tinyint                         DEFAULT 0,
                        `use_date_now`                        tinyint                         DEFAULT 0,
                        `additional_number_day`               int                             DEFAULT 0,
                        `informations_to_display`             varchar(255) NOT NULL           DEFAULT '[]',
                        `use_richtext`                        tinyint      NOT NULL           DEFAULT '1',
                        `icon`                                varchar(255)                    DEFAULT NULL,
                        `readonly`                            tinyint                         DEFAULT 0,
                        `hidden`                              tinyint                         DEFAULT 0,
                        `authldaps_id`                        int {$default_key_sign}         DEFAULT 0,
                        `ldap_attribute`                      int                             DEFAULT 0,
                        `ldap_filter`                         varchar(255) NOT NULL           DEFAULT '',
                        `root_items_id`                       int {$default_key_sign}         DEFAULT 0,
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.3.8
        if (!$DB->fieldExists($table, "default_use_id_requester_supervisor")) {
            $migration->addField($table, "default_use_id_requester_supervisor", "int {$default_key_sign}  DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        //version 3.4.0
        if (!$DB->fieldExists($table, "authldaps_id")) {
            $migration->addField($table, "authldaps_id", "int {$default_key_sign} DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "ldap_attribute")) {
            $migration->addField($table, "ldap_attribute", "int DEFAULT 0");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "ldap_filter")) {
            $migration->addField($table, "ldap_filter", "varchar(255) NOT NULL DEFAULT ''");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "root_items_id")) {
            $migration->addField($table, "root_items_id", "int {$default_key_sign} DEFAULT 0");
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
     * @param int $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = self::getNumberOfParametersForItem($item);
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
    }


    /**
     * Return the number of parameters for an item
     *
     * @param item
     *
     * @return int number of parameters for this item
     */
    public static function getNumberOfParametersForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            $dbu->getTableForItemType(__CLASS__),
            ["plugin_metademands_fields_id" => $item->getID()]
        );
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
        $field_parameter = new self();
        if ($field_parameter->getFromDBByCrit(["plugin_metademands_fields_id" => $item->getID()])) {
            $field_parameter->showParameterForm($field_parameter->getID(), ['parent' => $item]);
        } else {
            $field_parameter->showParameterForm(-1, ['parent' => $item]);
        }

        return true;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showParameterForm($ID = -1, $options = [])
    {
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }

        $metademand        = new Metademand();
        $metademand_fields = new Field();
        $item              = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, UPDATE);
            $metademand_fields->getFromDB($item->getID());
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
        } else {
            $metademand_fields->getFromDB($item->getID());
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
            $options['plugin_metademands_fields_id'] = $options['parent']->getField('id');
            $this->check(-1, CREATE, $options);
        }

        $params = Field::getAllParamsFromField($metademand_fields);

        ob_start();
        self::showFieldParameters($params);
        $field_parameters_html = ob_get_clean();

        $field_example_html = '';
        if ($ID > 0) {
            ob_start();
            echo Field::getFieldInput([], $params, false, 0, 0, false, "");
            $field_example_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@metademands/field_parameter_form.html.twig', [
            'form_action'           => \Toolbox::getItemTypeFormURL(FieldParameter::class),
            'field_parent_id'       => $item->getID(),
            'field_type'            => $metademand_fields->fields['type'],
            'field_item'            => $metademand_fields->fields['item'],
            'field_id'              => $ID > 0 ? $ID : 0,
            'is_new'                => $ID <= 0,
            'field_parameters_html' => $field_parameters_html,
            'is_existing'           => $ID > 0,
            'field_type_name'       => $ID > 0 ? Field::getFieldTypesName($params['type']) : '',
            'field_example_html'    => $field_example_html,
        ]);

        return true;
    }


    /**
     * View options for items or types
     *
     * @param array $options
     *
     * @return void
     * @throws \GlpitestSQLError
     */
    public static function showFieldParameters($options)
    {
        global $PLUGIN_HOOKS;

        $params['value'] = 0;

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        $allowed_parameters_types = self::$allowed_parameters_types;
        $allowed_parameters_items = self::$allowed_parameters_items;
        $allowed_options_types = FieldOption::$allowed_options_types;
        $new_fields = [];

        //        if (Plugin::isPluginActive('ldapfields')) {
        //            $ldapfields_containers = new PluginLdapfieldsContainer();
        //            $ldapfields = $ldapfields_containers->find(['type' => 'dropdown', 'is_active' => true]);
        //            if (count($ldapfields) > 0) {
        //                foreach ($ldapfields as $ldapfield) {
        //                    array_push($allowed_options_types, $ldapfield['name']);
        //                }
        //            }
        //        }

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                if (Plugin::isPluginActive($plug)) {
                    $new_fields = self::addPluginFieldItems($plug);
                    if (is_array($new_fields) && count($new_fields) > 0) {
                        $allowed_parameters_types = array_merge($allowed_parameters_types, $new_fields);
                    }
                }
            }
        }

        if (in_array($params['type'], $allowed_parameters_types)
            || in_array($params['item'], $allowed_parameters_items)) {

            echo self::showGlobalParameters($params);

            $class = Field::getClassFromType($params['type']);

            switch ($params["type"]) {
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
                case 'dropdown_ldap':
                case 'dropdown':
                case 'dropdown_multiple':
                case 'date':
                case 'time':
                case 'datetime':
                case 'date_interval':
                case 'datetime_interval':
                case 'upload':
                case 'signature':
                case 'yesno':
                case 'radio':
                case 'checkbox':
                    ob_start();
                    $class::showFieldParameters($params);
                    $specific_html = ob_get_clean();
                    if (!empty(trim($specific_html))) {
                        echo '<div class="mt-2"><table class="tab_cadre w-100"><tbody>' . $specific_html . '</tbody></table></div>';
                    }
                    break;
                case 'number':
                case 'range':
                case 'freetable':
                case 'basket':
                case 'link':
                case 'parent_field':
                    break;
                default:
                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        ob_start();
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            if (Plugin::isPluginActive($plug)) {
                                self::showPluginCustomvalues($plug, $params);
                            }
                        }
                        $plugin_html = ob_get_clean();
                        if (!empty(trim($plugin_html))) {
                            echo '<div class="mt-2"><table class="tab_cadre w-100"><tbody>' . $plugin_html . '</tbody></table></div>';
                        }
                    }
                    break;
            }
        }
    }


    public static function showGlobalParameters($params): string
    {
        global $PLUGIN_HOOKS;

        $type = $params['type'] ?? '';

        $show_mandatory  = !in_array($type, ['title', 'title-block', 'informations', 'link']);
        $show_hide_title = $type !== 'title-block';
        $show_row_display = !in_array($type, ['title', 'title-block']);
        $show_is_basket  = $show_row_display && ($params['is_order'] ?? 0) == 1;

        ob_start();
        if ($show_mandatory) {
            \Dropdown::showYesNo("is_mandatory", $params["is_mandatory"]);
        }
        $mandatory_html = ob_get_clean();

        ob_start();
        if ($show_hide_title) {
            \Dropdown::showYesNo('hide_title', ($params['hide_title']));
        }
        $hide_title_html = ob_get_clean();

        ob_start();
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select('icon', [$params['icon'] => $params['icon']], [
            'id'       => $icon_selector_id,
            'selected' => $params['icon'],
            'style'    => 'width:175px;',
        ]);
        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock("$(function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
                var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
                icon_selector.init();
            });
        });");
        echo "&nbsp;<input type='checkbox' name='_blank_picture'>&nbsp;" . __('Clear');
        $icon_html = ob_get_clean();

        ob_start();
        if ($show_row_display) {
            \Dropdown::showYesNo('row_display', ($params['row_display']));
        }
        $row_display_html = ob_get_clean();

        ob_start();
        if ($show_is_basket) {
            $basket_value = ($params['id'] ?? 0) > 0 ? $params["is_basket"] : 1;
            \Dropdown::showYesNo("is_basket", $basket_value);
        }
        $is_basket_html = ob_get_clean();

        $excluded_for_ticket = [
            'title', 'title-block', 'informations', 'text', 'tel', 'email', 'url',
            'checkbox', 'yesno', 'radio', 'number', 'range', 'basket', 'link', 'freetable',
        ];
        $show_used_by_ticket = !in_array($type, $excluded_for_ticket);
        $used_by_ticket_html = '';

        if ($show_used_by_ticket) {
            $ticket_fields[0] = \Dropdown::EMPTY_VALUE;
            $objectclass      = $params['object_to_create'];
            $searchOption     = Search::getOptions($objectclass);
            $allowed_fields   = [];

            if ($objectclass == 'Ticket') {
                $tt = new TicketTemplate();
            } elseif ($objectclass == 'Problem') {
                $tt = new ProblemTemplate();
            } elseif ($objectclass == 'Change') {
                $tt = new ChangeTemplate();
            }
            if (in_array($objectclass, ['Ticket', 'Problem', 'Change'])) {
                $allowed_fields = $tt->getAllowedFields(true, true);
            }

            if (isset($PLUGIN_HOOKS['metademands'])) {
                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                    if (Plugin::isPluginActive($plug)) {
                        $new_fields = self::addPluginAllowedFieldItems($plug);
                        if (is_array($new_fields) && count($new_fields) > 0) {
                            $allowed_fields = array_merge($allowed_fields, $new_fields);
                            unset($allowed_fields[-1]);
                        }
                    }
                }
            }

            unset($allowed_fields[-2]);

            $granted_fields = [];

            if (($type == "dropdown_object" && $params["item"] == "User")
                || ($type == "dropdown_multiple" && $params["item"] == "User")) {
                $allowed_fields[59] = __('Approver');
                $granted_fields = [4, 66, 59];
            }
            if ($type == "dropdown_object" && $params["item"] == "Group") {
                $granted_fields = [71, 65];
            }
            if ($objectclass == 'Problem' && $type == "textarea") {
                $granted_fields = [60, 61, 62];
            }
            if ($objectclass == 'Change' && $type == "textarea") {
                $granted_fields = [60, 61, 62, 63, 67];
            }
            if ($type == "dropdown_object" && $params["item"] == "Entity") {
                $allowed_fields[80] = 'entities_id';
                $granted_fields = [80];
            }
            if ($type == "dropdown" && $params["item"] == "Location") {
                $granted_fields = ['locations_id'];
            }
            if ($type == "dropdown" && $params["item"] == "RequestType") {
                $granted_fields = ['requesttypes_id'];
            }
            if ($type == "dropdown_meta"
                && in_array($params["item"], ["urgency", "impact", "priority"])) {
                $granted_fields = [$params["item"]];
            }
            if ($type == "dropdown_meta" && $params["item"] == "ITILCategory_Metademands") {
                $granted_fields = ['itilcategories_id'];
            }
            if ($type == "date" || $type == "datetime") {
                $granted_fields = ['time_to_resolve'];
            }
            if (($type == "dropdown_meta" && $params["item"] == "mydevices")
                || ($type == "dropdown_multiple" && $params["item"] == "Appliance")
                || ($type == "dropdown_object"
                    && \Ticket::isPossibleToAssignType($params["item"])
                    && !in_array($params["item"], ["User", "Group"]))) {
                $granted_fields = [13];
            }

            if (isset($PLUGIN_HOOKS['metademands'])) {
                foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                    $g_fields = self::getPluginGrantedFields($plug, $params);
                    if (Plugin::isPluginActive($plug) && is_array($g_fields)) {
                        $granted_fields = array_merge($granted_fields, $g_fields);
                    }
                }
            }

            foreach ($allowed_fields as $id => $value) {
                if ((isset($searchOption[$id]['linkfield'])
                        && in_array($searchOption[$id]['linkfield'], $granted_fields))
                    || in_array($id, $granted_fields)) {
                    if (isset($searchOption[$id]['name'])) {
                        $ticket_fields[$id] = $searchOption[$id]['name'];
                    }
                }
            }

            ob_start();
            \Dropdown::showFromArray('used_by_ticket', $ticket_fields, ['value' => $params["used_by_ticket"]]);
            $used_by_ticket_html = ob_get_clean();
        }

        $excluded_for_plugin = ['title', 'title-block', 'informations', 'link', 'freetable'];
        $show_plugin_fields  = !in_array($type, $excluded_for_plugin) && Plugin::isPluginActive('fields');
        $plugin_fields_html  = '';

        if ($show_plugin_fields) {
            ob_start();

            $arrayAvailableContainer = [];
            $fieldsContainer         = new PluginFieldsContainer();
            foreach ($fieldsContainer->find() as $container) {
                $typesContainer = json_decode($container['itemtypes']);
                if (is_array($typesContainer) && in_array($params["object_to_create"], $typesContainer)) {
                    $arrayAvailableContainer[] = $container['id'];
                }
            }

            $pluginfield = new Pluginfields();
            $opt         = ['display_emptychoice' => true];
            if ($pluginfield->getFromDBByCrit(['plugin_metademands_fields_id' => $params["id"]])) {
                $opt["value"] = $pluginfield->fields["plugin_fields_fields_id"];
            }
            $condition = count($arrayAvailableContainer) > 0
                ? ['plugin_fields_containers_id' => $arrayAvailableContainer]
                : [];

            $field        = new PluginFieldsField();
            $datas        = [];
            foreach ($field->find($condition) as $fields_value) {
                $datas[$fields_value['id']] = $fields_value['label'];
            }

            \Dropdown::showFromArray('plugin_fields_fields_id', $datas, $opt);
            echo Html::hidden('plugin_metademands_metademands_id', ['value' => $params["plugin_metademands_metademands_id"]]);

            $plugin_fields_html = ob_get_clean();
        }

        return TemplateRenderer::getInstance()->render('@metademands/field_parameter_global.html.twig', [
            'show_mandatory'      => $show_mandatory,
            'mandatory_html'      => $mandatory_html,
            'show_hide_title'     => $show_hide_title,
            'hide_title_html'     => $hide_title_html,
            'icon_html'           => $icon_html,
            'show_row_display'    => $show_row_display,
            'row_display_html'    => $row_display_html,
            'show_is_basket'      => $show_is_basket,
            'is_basket_html'      => $is_basket_html,
            'show_used_by_ticket' => $show_used_by_ticket,
            'used_by_ticket_html' => $used_by_ticket_html,
            'show_plugin_fields'  => $show_plugin_fields,
            'plugin_fields_html'  => $plugin_fields_html,
        ]);
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function showPluginCustomvalues($plug, $params)
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
                if ($item && is_callable([$item, 'showCustomvalues'])) {
                    $item->showCustomvalues($params);
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
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        if (empty($input['color'])) {
            $input['color'] = "#000000";
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
        if (isset($input["_blank_picture"])) {
            $input['icon'] = 'NULL';
        }

        return $input;
    }

    public function post_addItem()
    {
        $pluginField = new Pluginfields();
        $input = [];
        if (isset($this->input['plugin_fields_fields_id'])) {
            if (!$pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->input['plugin_metademands_fields_id']])) {
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['plugin_metademands_fields_id'];
                $input['plugin_metademands_metademands_id'] = $this->input['plugin_metademands_metademands_id'];
                $pluginField->add($input);
            }
        }
    }

    public function post_updateItem($history = 1)
    {

        $pluginField = new Pluginfields();
        if (isset($this->input['plugin_fields_fields_id'])) {
            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->input['plugin_metademands_fields_id']])) {
                $input = [];
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['plugin_metademands_fields_id'];
                $input['id'] = $pluginField->fields['id'];
                $pluginField->update($input);
            } else {
                $input = [];
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['plugin_metademands_fields_id'];
                $input['plugin_metademands_metademands_id'] = $this->input['plugin_metademands_metademands_id'];
                $pluginField->add($input);
            }
        }
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

        $id = $input['id'] ?? 0;
        foreach ($input as $key => $value) {
            if ($key === 'informations_to_display'
                && (isset($input['type'])
                    && in_array($input['type'], ['dropdown_multiple', 'dropdown_object'])
                    && $input['item'] === 'User')) {
                $temp = json_decode($value);
                if (empty($temp)) {
                    $msg[] = __("Informations to display in ticket and PDF", "metademands");
                    $checkKo = true;
                }
            }

            $_SESSION['glpi_plugin_metademands_fields'][$id][$key] = $value;
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(
                sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)),
                false,
                ERROR
            );
            return false;
        }
        return true;
    }


    /**
     * @param $input
     *
     * @return string
     */
    public static function _serialize($input)
    {
        if ($input != null || $input == []) {
            if (is_array($input)) {
                foreach ($input as &$value) {
                    if ($value != null) {
                        $clean = $value;
                        if ($clean != null) {
                            $value = urlencode($clean);
                        }
                    }
                }

                return json_encode($input);
            }
        }
    }

    public static function _serializeArray($input)
    {
        if ($input != null || $input == []) {
            $data_temp = [];
            if (is_array($input)) {
                foreach ($input as $k => $v) {
                    $data_temp[urlencode($k)] = self::_serializeArray($v);
                }
                return $data_temp;
            } else {
                return urlencode($input);
            }
        }
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

    public static function _unserializeArray($input)
    {
        if (!empty($input)) {
            $data_temp = [];
            if (is_array($input)) {
                foreach ($input as $k => $v) {
                    $data_temp[json_decode($k, true)] = self::_unserializeArray($v);
                }
                return $data_temp;
            } else {
                return json_decode($input, true);
            }
        }

        return $input;
    }


    //
    //    public function post_addItem()
    //    {
    //        $pluginField = new Pluginfields();
    //        $input = [];
    //        if (isset($this->input['plugin_fields_fields_id'])) {
    //            $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
    //            $input['plugin_metademands_fields_id'] = $this->fields['id'];
    //            $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
    //            $pluginField->add($input);
    //        }
    //    }
    //
    //    public function post_updateItem($history = 1)
    //    {
    //        $pluginField = new Pluginfields();
    //        if (isset($this->input['plugin_fields_fields_id'])) {
    //            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->fields['id']])) {
    //                $input = [];
    //                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
    //                $input['plugin_metademands_fields_id'] = $this->fields['id'];
    //                $input['id'] = $pluginField->fields['id'];
    //                $pluginField->update($input);
    //            } else {
    //                $input = [];
    //                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
    //                $input['plugin_metademands_fields_id'] = $this->fields['id'];
    //                $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
    //                $pluginField->add($input);
    //            }
    //        }
    //    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginAllowedFieldItems($plug)
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
                if ($item && is_callable([$item, 'addAllowedFieldItems'])) {
                    return $item->addAllowedFieldItems();
                }
            }
        }
    }

    private static function getPluginGrantedFields($plug, $params)
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
                if ($item && is_callable([$item, 'getGrantedFields'])) {
                    return $item->getGrantedFields($params);
                }
            }
        }
    }

    static function migrateFieldsParameters($migration)
    {
        global $DB;

        $table_fields = "glpi_plugin_metademands_fields";

        ini_set("memory_limit", "-1");
        ini_set("max_execution_time", 0);
        $metademand_fields = new Field();
        $fields = $metademand_fields->find();

        $metademand_fieldparams = new FieldParameter();

        if (count($fields) > 0) {
            foreach ($fields as $k => $field) {
                if (!$metademand_fieldparams->getFromDBByCrit(['plugin_metademands_fields_id' => $field['id']])) {
                    $input = [
                        'plugin_metademands_fields_id' => $field['id'],
                        'row_display' => $field['row_display'],
                        'hide_title' => $field['hide_title'],
                        'is_basket' => $field['is_basket'],
                        'color' => $field['color'],
                        'icon' => $field['icon'],
                        'is_mandatory' => $field['is_mandatory'],
                        'used_by_ticket' => $field['used_by_ticket'],
                        'used_by_child' => $field['used_by_child'],
                        'use_richtext' => $field['use_richtext'],
                        'default_use_id_requester' => $field['default_use_id_requester'],
                        'default_use_id_requester_supervisor' => $field['default_use_id_requester_supervisor'],
                        'readonly' => $field['readonly'],
                        'custom_values' => $field['custom_values'],
                        'comment_values' => $field['comment_values'],
                        'default_values' => $field['default_values'],
                        'max_upload' => $field['max_upload'],
                        'regex' => $field['regex'],
                        'use_future_date' => $field['use_future_date'],
                        'use_date_now' => $field['use_date_now'],
                        'additional_number_day' => $field['additional_number_day'],
                        'display_type' => $field['display_type'],
                        'informations_to_display' => $field['informations_to_display'],
                        'link_to_user' => $field["link_to_user"],
                        'hidden' => $field["hidden"],
                        'item' => $field['item'],
                        'type' => $field['type'],
                    ];

                    if (in_array($input['type'], ['dropdown_multiple', 'dropdown_object'])
                        && $input['item'] === 'User') {
                        $temp =  FieldParameter::_unserialize($input['informations_to_display']);
                        if (empty($temp)) {
                            $input['informations_to_display'] = FieldParameter::_serialize(['full_name']);
                        }
                    }

                    $metademand_fieldparams->add($input);
                }
            }
        }

        $metademand_fields = new Field();
        $fields = $metademand_fields->find();

        $metademand_fieldcustom = new FieldCustomvalue();
        $metademand_params = new FieldParameter();

        $old_new_custom_values = [];
        if (count($fields) > 0) {
            foreach ($fields as $key => $field) {
                $allowed_customvalues_types = FieldCustomvalue::$allowed_customvalues_types;
                $allowed_customvalues_items = FieldCustomvalue::$allowed_customvalues_items;

                if (isset($field['type'])
                    && in_array($field['type'], $allowed_customvalues_types)
                    || in_array($field['item'], $allowed_customvalues_items)) {
                    $custom_values = FieldParameter::_unserialize($field['custom_values']);
                    $default_values = FieldParameter::_unserialize($field['default_values']);
                    $comment_values = FieldParameter::_unserialize($field['comment_values']);

                    $inputs = [];
                    $rank = 0;
                    if (is_array($custom_values) && count($custom_values) > 0) {
                        foreach ($custom_values as $k => $name) {
                            $inputs[$k]['plugin_metademands_fields_id'] = $field['id'];
                            if (isset($custom_values[$k])) {
                                $inputs[$k]['name'] = $custom_values[$k];
                            }
                            if (isset($default_values[$k])) {
                                $inputs[$k]['is_default'] = $default_values[$k];
                            }
                            if (isset($comment_values[$k])) {
                                $inputs[$k]['comment'] = $comment_values[$k];
                            }
                            $inputs[$k]['old_check_value'] = $k;
                            $inputs[$k]['old_translation_name'] = "custom" . $k;
                            $inputs[$k]['rank'] = $rank;
                            $rank++;
                        }
                    }

                    foreach ($inputs as $key2 => $input) {
                        if (!empty($input['name'])) {
                            $newid = $metademand_fieldcustom->add($input);
                        }
                        $metademand_params->getFromDBByCrit(["plugin_metademands_fields_id" => $field['id']]);
                        $metademand_params->update([
                            "id" => $metademand_params->fields['id'],
                            "custom_values" => null,
                            "default_values" => null,
                            "comment_values" => null,
                        ]);

                        $metademand_options = new FieldOption();
                        $fieldoptions = $metademand_options->find(
                            ["plugin_metademands_fields_id" => $field['id'], "check_value" => $input['old_check_value']]
                        );
                        if (count($fieldoptions) > 0) {
                            foreach ($fieldoptions as $ko => $fieldoption) {
                                $metademand_options->update(["id" => $fieldoption['id'], "check_value" => $newid]);
                            }
                        }

                        $metademand_conditions = new Condition();
                        $fieldconditions = $metademand_conditions->find(
                            ["plugin_metademands_fields_id" => $field['id'], "check_value" => $input['old_check_value']]
                        );
                        if (count($fieldconditions) > 0) {
                            foreach ($fieldconditions as $ko => $fieldcondition) {
                                $metademand_conditions->update(["id" => $fieldcondition['id'], "check_value" => $newid]);
                            }
                        }

                        $metademand_translations = new FieldTranslation();
                        $fieldtranslations = $metademand_translations->find(
                            ["items_id" => $field['id'], "key" => $input['old_translation_name']]
                        );
                        if (count($fieldtranslations) > 0) {
                            foreach ($fieldtranslations as $k => $fieldtranslation) {
                                $new_value = "custom" . $input['rank'];
                                $metademand_translations->update(
                                    ["id" => $fieldtranslation['id'], "key" => $new_value]
                                );
                            }
                        }

                        $old_new_custom_values[$field['id']][$input['old_check_value']] =  $newid;
                    }
                }
            }
        }

        if (count($old_new_custom_values) > 0) {
            foreach ($old_new_custom_values as $fieldid => $oldandnews) {
                $metademand_formvalues = new Form_Value();
                $fieldformvalues = $metademand_formvalues->find(
                    ["plugin_metademands_fields_id" => $fieldid]
                );

                if (count($fieldformvalues) > 0) {
                    foreach ($fieldformvalues as $key => $fieldformvalue) {
                        $old_values = json_decode($fieldformvalue['value'], true);
                        $new_values = [];
                        if (is_array($old_values)) {
                            foreach ($old_values as $k => $old_value) {
                                if (in_array($old_value, array_keys($oldandnews))) {
                                    $new_value = $oldandnews[$old_value];
                                    $new_values[] = $new_value;
                                }
                            }
                            $new_values = json_encode($new_values, JSON_UNESCAPED_UNICODE);
                            $metademand_formvalues->update(
                                ["id" => $fieldformvalue['id'], "value" => $new_values]
                            );
                        } else {
                            $new_value = 0;
                            if ($old_values > 0 && isset($oldandnews[$old_values])) {
                                $new_value = $oldandnews[$old_values];
                            } else {
                                if (isset($oldandnews[1])) {
                                    $new_value = $oldandnews[1];
                                }
                            }
                            if ($new_value > 0) {
                                $metademand_formvalues->update(
                                    ["id" => $fieldformvalue['id'], "value" => $new_value]
                                );
                            }
                        }
                    }
                }

                $metademand_draftvalues = new Draft_Value();
                $fielddraftvalues = $metademand_draftvalues->find(
                    ["plugin_metademands_fields_id" => $fieldid]
                );
                if (count($fielddraftvalues) > 0) {
                    foreach ($fielddraftvalues as $key => $fielddraftvalue) {
                        $old_values = json_decode($fielddraftvalue['value'], true);
                        $new_values = [];
                        if (is_array($old_values)) {
                            foreach ($old_values as $k => $old_value) {
                                if (in_array($old_value, array_keys($oldandnews))) {
                                    $new_value = $oldandnews[$old_value];
                                    $new_values[] = $new_value;
                                }
                            }
                            $new_values = json_encode($new_values, JSON_UNESCAPED_UNICODE);
                            $metademand_draftvalues->update(
                                ["id" => $fielddraftvalue['id'], "value" => $new_values]
                            );
                        } else {
                            $new_value = 0;
                            if ($old_values > 0 && isset($oldandnews[$old_values])) {
                                $new_value = $oldandnews[$old_values];
                            } else {
                                if (isset($oldandnews[1])) {
                                    $new_value = $oldandnews[1];
                                }
                            }
                            if ($new_value > 0) {
                                $metademand_formvalues->update(
                                    ["id" => $fieldformvalue['id'], "value" => $new_value]
                                );
                            }
                        }
                    }
                }
            }
        }

        $migration->dropField($table_fields, "custom_values");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "default_values");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "comment_values");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "hide_title");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "is_mandatory");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "max_upload");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "regex");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "color");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "row_display");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "is_basket");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "display_type");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "used_by_ticket");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "used_by_child");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "link_to_user");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "default_use_id_requester");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "default_use_id_requester_supervisor");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "use_future_date");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "additional_number_day");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "informations_to_display");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "use_richtext");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "icon");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "readonly");
        $migration->migrationOneTable($table_fields);
        $migration->dropField($table_fields, "hidden");
        $migration->migrationOneTable($table_fields);

        $migration->changeField("glpi_plugin_metademands_fieldparameters", 'custom_values', 'custom', "TEXT COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;");
        $migration->migrationOneTable("glpi_plugin_metademands_fieldparameters");

        $migration->changeField("glpi_plugin_metademands_fieldparameters", 'default_values', 'default', "TEXT COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;");
        $migration->migrationOneTable("glpi_plugin_metademands_fieldparameters");

        $migration->dropField("glpi_plugin_metademands_fieldparameters", "comment_values");
        $migration->migrationOneTable("glpi_plugin_metademands_fieldparameters");

        $migration->executeMigration();

        $query = $DB->buildDelete(
            "glpi_plugin_metademands_drafts_values",
            [
                'plugin_metademands_drafts_id' => 0,
            ],
        );
        $DB->doQuery($query);

        $query = $DB->buildDelete(
            "glpi_plugin_metademands_forms_values",
            [
                'plugin_metademands_forms_id' => 0,
            ],
        );
        $DB->doQuery($query);

        foreach ($DB->request([
            'SELECT'    => [
                'profiles_id',
            ],
            'FROM'      => 'glpi_profilerights',
            'WHERE'     => [
                'name'   => ['LIKE', '%plugin_metademands%'],
                'rights' => ['>', 10]
            ],
        ]) as $prof) {
            $rights = ['plugin_metademands_validatemeta' => 1];
            Profile::addDefaultProfileInfos($prof['profiles_id'], $rights);
        }
    }
}
