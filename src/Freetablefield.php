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
use Glpi\RichText\RichText;
use Html;
use Migration;
use Session;

/**
 * Class Freetablefield
 */
class Freetablefield extends CommonDBChild
{
    public static string $itemtype = Field::class;
    public static string $items_id = 'plugin_metademands_fields_id';
    public bool $dohistory = true;

    public static string $rightname = 'plugin_metademands';

    public const TYPE_TEXT = 1;
    public const TYPE_SELECT = 2;
    public const TYPE_NUMBER = 3;
    public const TYPE_READONLY = 4;
    public const TYPE_DATE = 5;
    public const TYPE_TIME = 6;

    /** @var array<int, array[]> Rows grouped by plugin_metademands_fields_id (sorted by rank) */
    private static array $rows_cache = [];

    /**
     * Batch-load Freetablefield rows for the given field IDs into the static cache.
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
            'FROM'    => self::getTable(),
            'WHERE'   => ['plugin_metademands_fields_id' => $uncached],
            'ORDERBY' => ['rank'],
        ]) as $row) {
            self::$rows_cache[(int) $row['plugin_metademands_fields_id']][$row['id']] = $row;
        }
    }

    /**
     * Return all cached freetable rows for this field (empty array = none, false = not preloaded).
     *
     * @return array[]|false
     */
    public static function getFromStaticCache(int $field_id)
    {
        return array_key_exists($field_id, self::$rows_cache) ? self::$rows_cache[$field_id] : false;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Free table field', 'Free table fields', $nb, 'metademands');
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
                        `plugin_metademands_fields_id` int {$default_key_sign} NOT NULL           DEFAULT '0',
                        `internal_name`                VARCHAR(255) NOT NULL           DEFAULT '0',
                        `type`                         VARCHAR(255)                    DEFAULT NULL,
                        `name`                         VARCHAR(255) NOT NULL           DEFAULT '0',
                        `dropdown_values`              text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `is_mandatory`                 int          NOT NULL           DEFAULT '0',
                        `comment`                      text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `rank`                         int          NOT NULL           DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
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
        if ($item instanceof Field
            && isset($item->fields['type'])
            && $item->fields['type'] == "freetable") {
            $nb = self::getNumberOfFieldsForItem($item);
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    /**
     * Return the number of parameters for an item
     *
     * @param Field $item
     *
     * @return int number of parameters for this item
     */
    public static function getNumberOfFieldsForItem($item)
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
        // The tab is only registered on Field (see getTabNameForItem), but the parent
        // signature is typed on CommonGLPI, which knows nothing about getID().
        if (!$item instanceof Field) {
            return false;
        }

        $field_custom = new self();
        $results = $field_custom->find(["plugin_metademands_fields_id" => $item->getID()]);
        if (!empty($results)) {
            $first = reset($results);
            $field_custom->showFieldsForm($first['id'], ['parent' => $item]);
        } else {
            $field_custom->showFieldsForm(-1, ['parent' => $item]);
        }

        return true;
    }


    /**
     * @return array
     */
    public static function getTypeFields($with_empty_values = false)
    {
        if ($with_empty_values) {
            $types[0] = \Dropdown::EMPTY_VALUE;
        }
        $types[self::TYPE_TEXT] = __('Text', 'metademands');
        $types[self::TYPE_SELECT] = __('Dropdown', 'metademands');
        $types[self::TYPE_NUMBER] = __('Number', 'metademands');
        $types[self::TYPE_READONLY] = __('Readonly', 'metademands');
        $types[self::TYPE_DATE] = __('Date', 'metademands');
        $types[self::TYPE_TIME] = _n('Hour', 'Hours', 1);
        return $types;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     */
    public function showFieldsForm($ID = -1, $options = [])
    {
        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }
        Html::requireJs('tinymce');

        $metademand = new Metademand();
        $metademand_fields = new Field();
        $metademand_params = new FieldParameter();

        $item = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, UPDATE);
            $metademand_fields->getFromDB($item->getID());
            $metademand_params->getFromDBByCrit(
                ["plugin_metademands_fields_id" => $item->getID()],
            );
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
        } else {
            $metademand_fields->getFromDB($item->getID());
            $metademand_params->getFromDBByCrit(
                ["plugin_metademands_fields_id" => $item->getID()],
            );
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
            // Create item
            $options['plugin_metademands_fields_id'] = $options['parent']->getField('id');
            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);

        $params = Field::getAllParamsFromField($metademand_fields);

        $show_info    = ($ID > 0);
        $type_name    = '';
        $example_html = '';
        if ($show_info) {
            $type_name = Field::getFieldTypesName($params["type"]);
            // getFieldInput() echoes the widget internally but the parent_field case returns
            // a string: capture both, otherwise the example is printed at the top of the page
            // instead of landing in the "Field informations" table.
            ob_start();
            $example_ret  = Field::getFieldInput([], $params, false, 0, 0, false, "");
            $example_html = ob_get_clean();
            if (is_string($example_ret)) {
                $example_html .= $example_ret;
            }
        }

        TemplateRenderer::getInstance()->display('@metademands/forms/freetablefield_fields_form.html.twig', [
            'fields_id'        => $item->getID(),
            'type'             => $metademand_fields->fields['type'],
            'item'             => $metademand_fields->fields['item'],
            'params'           => $params,
            'show_info'        => $show_info,
            'type_name'        => $type_name,
            'example_html'     => $example_html,
        ]);

        return true;
    }


    /**
     * View custom values for items or types
     *
     * @param array $params
     * @return void
     */
    public static function showFreetableFields($params = [])
    {
        if ($params['type'] != "freetable") {
            return;
        }

        TemplateRenderer::getInstance()->display('@metademands/forms/freetable_fields_wrapper.html.twig', [
            'title'        => _n('Free table field', 'Free table fields', 2, 'metademands'),
            'tooltip'      => __('(6 fields maximum)', 'metademands'),
            'params'       => $params,
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
                'plugin_metademands_fields_id' => $params['field_id'],
                'rank' => $params['old_order'],
            ];

            $itemMove = new self();
            $itemMove->getFromDBByCrit($crit);

            if (isset($itemMove->fields["id"])) {
                // Reorganization of the rows of this free table only: the rank column is
                // shared by every free table of the instance, so the moved row's parent
                // field bounds the renumbering, like Field::reorder does on its meta-demand.
                if ($params['old_order'] < $params['new_order']) {
                    $toUpdateList = $this->find([
                        'plugin_metademands_fields_id' => $itemMove->fields['plugin_metademands_fields_id'],
                        ['rank' => ['>', $params['old_order']]],
                        ['rank' => ['<=', $params['new_order']]],
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id' => $toUpdate['id'],
                            'rank' => $toUpdate['rank'] - 1,
                        ]);
                    }
                } else {
                    $toUpdateList = $this->find([
                        'plugin_metademands_fields_id' => $itemMove->fields['plugin_metademands_fields_id'],
                        ['rank' => ['<', $params['old_order']]],
                        ['rank' => ['>=', $params['new_order']]],
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id' => $toUpdate['id'],
                            'rank' => $toUpdate['rank'] + 1,
                        ]);
                    }
                }

                if ($itemMove->fields['id'] > 0) {
                    $this->update([
                        'id' => $itemMove->fields['id'],
                        'rank' => $params['new_order'],
                    ]);
                }
            }
        }
    }


    /**
     * @param $valueId
     * @param $display_comment
     * @param $display_default
     */
    public static function addNewValue($rank, $fields_id)
    {
        TemplateRenderer::getInstance()->display('@metademands/forms/freetablefield_add_value.html.twig', [
            'form_action'  => self::getFormURL(),
            'rank'         => (int) $rank,
            'fields_id'    => (int) $fields_id,
            'type_choices' => self::getTypeFields(true),
        ]);
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        if (!isset($input['name']) || $input['name'] === ''
        ) {
            Session::addMessageAfterRedirect(
                __("You can't add a field without name", "metademands"),
                false,
                ERROR,
            );
            return false;
        }
        if (empty($input['type']) || $input['type'] == 0
        ) {
            Session::addMessageAfterRedirect(
                __("You can't add a field without type", "metademands"),
                false,
                ERROR,
            );
            return false;
        }
        if (!isset($input['plugin_metademands_fields_id'])
        ) {
            return false;
        }


        return $input;
    }
}
