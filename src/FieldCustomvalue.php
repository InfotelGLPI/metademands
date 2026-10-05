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

use CommonDBChild;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use Html;
use Migration;
use Session;

/**
 * Class FieldCustomvalue
 */
class FieldCustomvalue extends CommonDBChild
{
    public static string $itemtype = Field::class;
    public static string $items_id = 'plugin_metademands_fields_id';
    public bool $dohistory = true;

    public static string $rightname = 'plugin_metademands';

    public static $allowed_custom_types = [
        'yesno',
        'link',
        'number',
        'range',
        'basket',
    ];

    public static $blacklisted_custom_types = [
        'dropdown_object',
    ];

    public static $blacklisted_custom_items = [
        'ITILCategory_Metademands',
    ];

    public static $allowed_customvalues_types = [
        'checkbox',
        'radio',
        'dropdown_meta',
    ];

    public static $allowed_customvalues_items = ['other', 'Appliance', 'Group'];


    /** @var array<int, array[]|null> Rows grouped by plugin_metademands_fields_id (sorted by rank) */
    private static array $rows_cache = [];

    /**
     * Batch-load FieldCustomvalue rows for the given field IDs into the static cache.
     */
    public static function preloadForFields(array $field_ids): void
    {
        global $DB;

        if (empty($field_ids)) {
            return;
        }
        $uncached = array_diff(array_map('intval', $field_ids), array_keys(self::$rows_cache));
        if (empty($uncached)) {
            return;
        }
        foreach ($uncached as $id) {
            self::$rows_cache[$id] = [];
        }
        foreach ($DB->request([
            'FROM'    => 'glpi_plugin_metademands_fieldcustomvalues',
            'WHERE'   => ['plugin_metademands_fields_id' => $uncached],
            'ORDERBY' => ['rank'],
        ]) as $row) {
            self::$rows_cache[(int) $row['plugin_metademands_fields_id']][$row['id']] = $row;
        }
    }

    /**
     * Return all cached custom-value rows for this field (empty array = none, false = not preloaded).
     *
     * @return array[]|false
     */
    public static function getFromStaticCache(int $field_id)
    {
        return array_key_exists($field_id, self::$rows_cache) ? self::$rows_cache[$field_id] : false;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Custom value', 'Custom values', $nb, 'metademands');
    }


    public static function getIcon()
    {
        return Metademand::getIcon();
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
                        `plugin_metademands_fields_id` int {$default_key_sign}    NOT NULL           DEFAULT '0',
                        `name`                         VARCHAR(255) NOT NULL           DEFAULT '0',
                        `is_default`                   int          NOT NULL           DEFAULT '0',
                        `comment`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `rank`                         int          NOT NULL           DEFAULT '0',
                        `icon`                         VARCHAR(255)                    DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.4.0
        if (!$DB->fieldExists($table, "icon")) {
            $migration->addField($table, "icon", "varchar(255) DEFAULT NULL");
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
     * @param int        $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $allowed_customvalues_types = self::$allowed_customvalues_types;
        $allowed_custom_types = self::$allowed_custom_types;
        $allowed_customvalues_items = self::$allowed_customvalues_items;
        $blacklisted_custom_types = self::$blacklisted_custom_types;
        $blacklisted_custom_items = self::$blacklisted_custom_items;
        if (isset($item->fields['type'])
            && (in_array($item->fields['type'], $allowed_customvalues_types)
            || in_array($item->fields['type'], $allowed_custom_types)
            || in_array($item->fields['item'], $allowed_customvalues_items))
        && !in_array($item->fields['type'], $blacklisted_custom_types)
            && !in_array($item->fields['item'], $blacklisted_custom_items)) {
            $nb = self::getNumberOfCustomValuesForItem($item);
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }



    /**
     * Return the number of parameters for an item
     *
     * @param item
     *
     * @return int number of parameters for this item
     */
    public static function getNumberOfCustomValuesForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            $dbu->getTableForItemType(__CLASS__),
            ["plugin_metademands_fields_id" => $item->getID()],
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
        $field_custom = new self();
        if ($field_custom->find(["plugin_metademands_fields_id" => $item->getID()])) {
            $field_custom->showCustomValuesForm($field_custom->getID(), ['parent' => $item]);
        } else {
            $field_custom->showCustomValuesForm(-1, ['parent' => $item]);
        }

        return true;
    }


    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForUpdate($input)
    {

        if (isset($input["_blank_picture"])) {
            $input['icon'] = 'NULL';
        }

        // Defense in depth against stored XSS: the icon is echoed into an HTML class attribute by the
        // field renderers (Checkbox, Radio, Dropdown, Dropdownmeta, Information). Reject anything that is
        // not a plain icon class token, using the same allow-list as the change_icon massive action.
        if (isset($input['icon']) && $input['icon'] !== '' && $input['icon'] !== 'NULL'
            && !preg_match('/^[a-zA-Z0-9 _-]+$/', (string) $input['icon'])) {
            $input['icon'] = '';
        }

        // Defense in depth: the option label is plain text echoed by the field renderers; strip any markup
        // at save so a stored value can never carry an XSS payload (comment stays rich, sanitized at render).
        if (isset($input['name'])) {
            $input['name'] = strip_tags((string) $input['name']);
        }

        // The parent runs checkAttachedItemChangesAllowed(): moving the row to another
        // parent requires CREATE on the new one and PURGE on the old one.
        return parent::prepareInputForUpdate($input);
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showCustomValuesForm($ID = -1, $options = [])
    {
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }

        $metademand        = new Metademand();
        $metademand_fields = new Field();
        $metademand_params = new FieldParameter();
        $item              = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, UPDATE);
            $metademand_fields->getFromDB($item->getID());
            $metademand_params->getFromDBByCrit(["plugin_metademands_fields_id" => $item->getID()]);
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
        } else {
            $metademand_fields->getFromDB($item->getID());
            $metademand_params->getFromDBByCrit(["plugin_metademands_fields_id" => $item->getID()]);
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
            $options['plugin_metademands_fields_id'] = $options['parent']->getField('id');
            $this->check(-1, CREATE, $options);
        }

        $params = Field::getAllParamsFromField($metademand_fields);

        $field_example_html = '';
        if ($ID > 0) {
            ob_start();
            echo Field::getFieldInput([], $params, false, 0, 0, false, "");
            $field_example_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@metademands/field_customvalue_form.html.twig', [
            'params'                        => $params,
            'is_new'                        => $ID <= 0,
            'field_type_name'               => $ID > 0 ? Field::getFieldTypesName($params['type']) : '',
            'field_example_html'            => $field_example_html,
            'form_target'                   => self::getFormURL(),
            'fields_type'                   => $params['type'] ?? '',
            'fields_item'                   => $params['item'] ?? '',
            'plugin_metademands_fields_id'  => $metademand_fields->getID(),
        ]);

        return true;
    }


    /**
     * View custom values for items or types
     *
     * @param array $values
     * @param array $comment
     * @param array $default
     * @param array $options
     *
     * @return void
     */
    public static function showFieldCustomValues($params = []): void
    {
        $allowed_customvalues_types = self::$allowed_customvalues_types;
        $allowed_custom_types       = self::$allowed_custom_types;
        $allowed_customvalues_items = self::$allowed_customvalues_items;

        if (!(in_array($params['type'], $allowed_customvalues_types)
            || in_array($params['type'], $allowed_custom_types)
            || in_array($params['item'], $allowed_customvalues_items))) {
            return;
        }

        $show_header = $params['type'] != "dropdown_multiple" && ($params['item'] ?? '') != 'User';

        if ($params["type"] == "dropdown_multiple" && empty($params["item"])) {
            $params["item"] = "other";
        }
        if ($params["type"] == "radio") {
            $params["item"] = "radio";
        }
        if ($params["type"] == "checkbox") {
            $params["item"] = "checkbox";
        }

        $has_duplicates    = false;
        $is_not_sequential = false;

        if (in_array($params['type'], $allowed_customvalues_types)
            || in_array($params['item'], $allowed_customvalues_items)) {
            $ranks = [];
            foreach ($params['custom_values'] as $key => $value) {
                $ranks[] = $params['item'] != 'Appliance' ? $value['rank'] : $key;
            }
            if (count($ranks) > 0) {
                $has_duplicates    = count($ranks) > count(array_unique($ranks));
                $is_not_sequential = !self::isSequentialFromZero($ranks) && $params["item"] != "Appliance";
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/field_customvalue_values.html.twig', [
            'show_header'       => $show_header,
            'has_duplicates'    => $has_duplicates,
            'is_not_sequential' => $is_not_sequential,
            'form_url'          => self::getFormURL(),
            'fields_id'         => $params["plugin_metademands_fields_id"] ?? 0,
            'params'            => $params,
        ]);
    }

    /**
     * Print the custom value rows of a field: the field classes print their own rows
     * (field_customvalue_values.html.twig).
     *
     * @param array $params field parameters, as normalised by showFieldCustomValues()
     *
     * @return void
     */
    public static function showCustomValueRows($params): void
    {
        if ($params["type"] != "dropdown_multiple") {
            switch ($params['item']) {
                case 'impact':
                case 'urgency':
                case 'priority':
                case 'mydevices':
                case 'other':
                    Dropdownmeta::showFieldCustomValues($params);
                    break;
            }
        }
        $class = Field::getClassFromType($params['type']);
        switch ($params['type']) {
            case 'dropdown_multiple':
            case 'checkbox':
            case 'radio':
            case 'yesno':
            case 'number':
            case 'range':
            case 'link':
            case 'basket':
                $class::showFieldCustomValues($params);
                break;
        }
    }


    /**
     * @param array $params
     */
    public function reorder(array $params)
    {

        if (isset($params['old_order'])
            && isset($params['new_order'])) {
            $crit = [
                'plugin_metademands_fields_id' => $params['field_id'],
                'rank' => $params['old_order'],
            ];

            $itemMove = new self();
            $itemMove->getFromDBByCrit($crit);

            if (isset($itemMove->fields["id"])) {
                // Reorganization of all fields
                if ($params['old_order'] < $params['new_order']) {
                    $toUpdateList = $this->find([
                        'plugin_metademands_fields_id' => $params['field_id'],
                        '`rank`' => ['>', $params['old_order']],
                        'rank'   => ['<=', $params['new_order']],
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id'      => $toUpdate['id'],
                            'rank' => $toUpdate['rank'] - 1,
                        ]);
                    }
                } else {
                    $toUpdateList = $this->find([
                        'plugin_metademands_fields_id' => $params['field_id'],
                        '`rank`' => ['<', $params['old_order']],
                        'rank'   => ['>=', $params['new_order']],
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id'      => $toUpdate['id'],
                            'rank' => $toUpdate['rank'] + 1,
                        ]);
                    }
                }

                if (isset($itemMove->fields["id"]) && $itemMove->fields['id'] > 0) {
                    $this->update([
                        'id'      => $itemMove->fields['id'],
                        'rank' => $params['new_order'],
                    ]);
                }
            }
        }
    }


    /**
     * Context of field_customvalue_list.html.twig: the free values of a field as data,
     * their widgets, add and import buttons are rendered by the template.
     *
     * @param array $params       parameters of the field: custom_values,
     *                            plugin_metademands_fields_id, type and item
     * @param bool  $show_comment offer the comment of each existing value
     * @param bool  $add_comment  offer the comment of a new value
     * @param bool  $show_icon    offer the icon of each value
     * @param int   $maxrank      rank counted from when the field has no value yet
     *
     * @return array<string, mixed>
     */
    public static function getListContext(
        array $params,
        bool $show_comment,
        bool $add_comment,
        bool $show_icon,
        int $maxrank = -1
    ): array {
        $rows = [];
        $custom_values = $params['custom_values'] ?? [];
        if (is_array($custom_values)) {
            foreach ($custom_values as $key => $value) {
                $rows[] = [
                    'id'         => $key,
                    'rank'       => $value['rank'],
                    'name'       => $value['name'],
                    'comment'    => $value['comment'] ?? '',
                    'is_default' => $value['is_default'],
                    'icon'       => (string) ($value['icon'] ?? ''),
                ];
                $maxrank = (int) $value['rank'];
            }
        }

        return [
            'rows'          => $rows,
            'form_target'   => self::getFormURL(),
            'fields_id'     => $params['plugin_metademands_fields_id'] ?? '',
            'type'          => $params['type'] ?? '',
            'item'          => $params['item'] ?? '',
            'show_comment'  => $show_comment,
            'show_icon'     => $show_icon,
            'add'           => [
                'count'           => $maxrank,
                'display_comment' => $add_comment,
                'display_icon'    => $show_icon,
            ],
            'root_doc'      => PLUGIN_METADEMANDS_WEBDIR,
            'import_action' => PLUGIN_METADEMANDS_WEBDIR . '/front/importcustomvalues.php',
            'specific'      => null,
            'reorder_url'   => PLUGIN_METADEMANDS_WEBDIR . '/ajax/reorder.php',
        ];
    }

    /**
     * @param array<string, mixed> $context built by getListContext()
     */
    public static function showList(array $context): void
    {
        TemplateRenderer::getInstance()->display('@metademands/fields/field_customvalue_list.html.twig', $context);
    }

    /**
     * @param $valueId
     * @param $display_comment
     * @param $display_default
     */
    public static function addNewValue($rank, $display_comment, $display_default, $fields_id, $display_icon = false)
    {
        TemplateRenderer::getInstance()->display(
            '@metademands/fields/field_customvalue_add.html.twig',
            [
                'form_target'     => self::getFormURL(),
                'rank'            => $rank,
                'fields_id'       => $fields_id,
                'display_comment' => (bool) $display_comment,
                'display_default' => (bool) $display_default,
                'display_icon'    => (bool) $display_icon,
            ],
        );
    }


    /**
     * @param        $action
     * @param        $btname
     * @param        $btlabel
     * @param array $fields
     * @param string $btimage
     * @param string $btoption
     * @param string $confirm
     *
     * @return string
     */
    public static function showSimpleForm(
        $action,
        $btname,
        $btlabel,
        array $fields = [],
        $btimage = '',
        $btoption = '',
        $confirm = ''
    ) {
        return Html::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
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
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        if (empty($input['name'])
        ) {
            Session::addMessageAfterRedirect(
                __("You can't add a custom value without name", "metademands"),
                false,
                ERROR,
            );
            return false;
        }
        if (!isset($input['plugin_metademands_fields_id'])
        ) {
            return false;
        }

        // Defense in depth against stored XSS: the icon is echoed into an HTML class attribute by the
        // field renderers (Checkbox, Radio, Dropdown, Dropdownmeta, Information). Reject anything that is
        // not a plain icon class token, using the same allow-list as the change_icon massive action.
        if (isset($input['icon']) && $input['icon'] !== ''
            && !preg_match('/^[a-zA-Z0-9 _-]+$/', (string) $input['icon'])) {
            $input['icon'] = '';
        }

        // Defense in depth: the option label is plain text echoed by the field renderers; strip any markup
        // at save so a stored value can never carry an XSS payload (comment stays rich, sanitized at render).
        if (isset($input['name'])) {
            $input['name'] = strip_tags((string) $input['name']);
        }

        return $input;
    }

    public static function isSequentialFromZero(array $arr)
    {
        if (empty($arr) || $arr[0] !== 0) {
            return false; // V�rifie que le tableau n'est pas vide et commence bien par 0
        }

        for ($i = 1; $i < count($arr); $i++) {
            if ($arr[$i] - $arr[$i - 1] !== 1) {
                return false; // V�rifie que la progression est bien de +1
            }
        }
        return true;
    }

    public static function fixRanks(array $data)
    {
        // Extraire les cl�s du tableau
        $keys = array_keys($data);

        // R�initialiser le rank � partir de 0
        foreach ($keys as $index => $key) {
            $data[$key]['rank'] = $index;
        }

        return $data;
    }
}
