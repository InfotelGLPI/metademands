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
 the Free Software Foundation; either version 2 of the License, or
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

use CommonDBChild;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Metademands\Fields\Dropdownmeta;
use Html;
use Migration;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class FieldCustomvalue
 */
class FieldCustomvalue extends CommonDBChild
{
    public static $itemtype = Field::class;
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    public static $rightname = 'plugin_metademands';

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

        return $input;
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

        ob_start();
        self::showFieldCustomValues($params);
        $field_custom_values_html = ob_get_clean();

        $field_example_html = '';
        if ($ID > 0) {
            ob_start();
            echo Field::getFieldInput([], $params, false, 0, 0, false, "");
            $field_example_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display('@metademands/field_customvalue_form.html.twig', [
            'field_custom_values_html' => $field_custom_values_html,
            'is_new'                   => $ID <= 0,
            'field_type_name'          => $ID > 0 ? Field::getFieldTypesName($params['type']) : '',
            'field_example_html'       => $field_example_html,
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
        $fix_ranks_html    = '';

        if (in_array($params['type'], $allowed_customvalues_types)
            || in_array($params['item'], $allowed_customvalues_items)) {
            $ranks = [];
            foreach ($params['custom_values'] as $key => $value) {
                $ranks[] = $params['item'] != 'Appliance' ? $value['rank'] : $key;
            }
            if (count($ranks) > 0) {
                $has_duplicates    = count($ranks) > count(array_unique($ranks));
                $is_not_sequential = !self::isSequentialFromZero($ranks) && $params["item"] != "Appliance";
                if ($is_not_sequential) {
                    ob_start();
                    Html::showSimpleForm(
                        self::getFormURL(),
                        'fixranks',
                        _x('button', 'Do you want to fix them ? Warning you must check your options after!', 'metademands'),
                        ['plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"]],
                        'ti-settings',
                        "class='btn btn-warning'"
                    );
                    $fix_ranks_html = ob_get_clean();
                }
            }
        }

        ob_start();
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
        $custom_values_html = ob_get_clean();

        echo TemplateRenderer::getInstance()->render('@metademands/field_customvalue_values.html.twig', [
            'show_header'        => $show_header,
            'has_duplicates'     => $has_duplicates,
            'is_not_sequential'  => $is_not_sequential,
            'fix_ranks_html'     => $fix_ranks_html,
            'custom_values_html' => $custom_values_html,
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
     * @param      $count
     * @param bool $display_comment
     * @param bool $display_default
     */
    public static function initCustomValue($count, $display_comment = false, $display_default = false, $plugin_metademands_fields_id = 0, $display_icon = false)
    {

        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(
            ['root_doc' => PLUGIN_METADEMANDS_WEBDIR]
        ) . ");";

        echo Html::hidden('display_comment', ['id' => 'display_comment', 'value' => $display_comment]);
        echo Html::hidden('count_custom_values', ['id' => 'count_custom_values', 'value' => $count]);
        echo Html::hidden('display_default', ['id' => 'display_default', 'value' => $display_default]);
        echo Html::hidden('display_icon', ['id' => 'display_icon', 'value' => $display_icon]);

        echo "&nbsp;<i class='ti ti-square-plus btn btn-sm btn-success' style='cursor:pointer;'
            onclick='$script metademandWizard.metademands_add_custom_values(\"show_custom_fields\", $plugin_metademands_fields_id);'
            title='" . _sx("button", "Add") . "'/></i>&nbsp;";

    }


    /**
     * @param      $count
     * @param bool $display_comment
     * @param bool $display_default
     */
    public static function importCustomValue($params)
    {

        echo "<tr class='tab_bg_1'>";

        echo "<td align='right'>";
        echo "<a href='javascript:void(0);' class='btn btn-success' onclick='formToggle(\"importFrm\");'>";
        echo __('Reset and import custom values', 'metademands') . "</a>";
        echo "</td>";

        echo "<td align='left'  colspan='4'>";
        echo "<div class='col-md-12' id='importFrm' style='display: none;'>";
        echo "<form name='form' method='post' action='" . PLUGIN_METADEMANDS_WEBDIR . "/front/importcustomvalues.php' method='post' enctype='multipart/form-data'>";
        echo "<input type='file' name='importFrm' id='importFrm'>&nbsp;";
        echo Html::hidden('plugin_metademands_fields_id', ['value' => $params["plugin_metademands_fields_id"]]);
        echo Html::submit("", ['name'  => 'importreplacecsv',
            'class' => 'btn btn-success',
            'icon'  => 'ti ti-upload',
            'confirm' => __('Are you sure ? Custom values will be deleted !', 'metademands')]);
        $warning = __('Please respect this format : name; display by default(0|1); comment; - sorted by display order', 'metademands');
        Html::showToolTip($warning);
        Html::closeForm();
        echo "</div>";
        echo Html::scriptBlock("function formToggle(ID) {
                var element = document.getElementById(ID);
                if (element.style.display === 'none') {
                    element.style.display = 'block';
                } else {
                    element.style.display = 'none';
                }
            };");
        echo "</td>";
        echo "</tr>";
    }

    /**
     * @param $valueId
     * @param $display_comment
     * @param $display_default
     */
    public static function addNewValue($rank, $display_comment, $display_default, $fields_id, $display_icon = false)
    {
        $default_html = '';
        if ($display_default) {
            ob_start();
            \Dropdown::showYesNo("default_values[$rank]", 0);
            $default_html = ob_get_clean();
        }

        $icon_html = '';
        if ($display_icon) {
            $icon_selector_id = 'icon_' . mt_rand();
            ob_start();
            echo Html::select(
                "icon[$rank]",
                ['' => ''],
                ['id' => $icon_selector_id, 'selected' => '', 'style' => 'width:175px;']
            );
            echo Html::script('js/modules/Form/WebIconSelector.js');
            echo Html::scriptBlock("$(function() {
                import('/js/modules/Form/WebIconSelector.js').then((m) => {
                    var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
                    icon_selector.init();
                });
            });");
            echo "&nbsp;<input type='checkbox' name='_blank_picture[{$rank}]'>&nbsp;" . __('Clear');
            $icon_html = ob_get_clean();
        }

        TemplateRenderer::getInstance()->display(
            '@metademands/fields/field_customvalue_add.html.twig',
            [
                'form_target'     => self::getFormURL(),
                'rank'            => $rank,
                'fields_id'       => $fields_id,
                'display_comment' => (bool) $display_comment,
                'display_default' => (bool) $display_default,
                'default_html'    => $default_html,
                'display_icon'    => (bool) $display_icon,
                'icon_html'       => $icon_html,
            ]
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
                ERROR
            );
            return false;
        }
        if (!isset($input['plugin_metademands_fields_id'])
        ) {
            return false;
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
