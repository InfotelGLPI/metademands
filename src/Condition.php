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
use CommonDBChild;
use CommonGLPI;
use CommonITILObject;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Fields\Yesno;
use Html;
use ITILCategory;
use Migration;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Condition
 */
class Condition extends CommonDBChild
{
    /* DEFINES CONST */
    //logical operator
    public const SHOW_LOGIC_AND = 1;
    public const SHOW_LOGIC_OR = 2;

    //operators
    public const SHOW_CONDITION_EQ = 1;
    public const SHOW_CONDITION_NE = 2;
    public const SHOW_CONDITION_LT = 3;
    public const SHOW_CONDITION_GT = 4;
    public const SHOW_CONDITION_LE = 5;
    public const SHOW_CONDITION_GE = 6;
    public const SHOW_CONDITION_REGEX = 7;

    public const SHOW_CONDITION_EMPTY = 8;

    public const SHOW_CONDITION_NOTEMPTY = 9;

    public const SHOW_RULE_ALWAYS = 1;
    public const SHOW_RULE_HIDDEN = 2;
    public const SHOW_RULE_SHOWN = 3;

    public static $rightname = 'plugin_metademands';

    public static $itemtype = Metademand::class;
    public static $items_id = 'plugin_metademands_metademands_id';

    public static $field_types_available = [
        '',
        'dropdown',
        'dropdown_object',
        'dropdown_meta',
        'dropdown_multiple',
        'dropdown_ldap',
        'text',
        'tel',
        'email',
        'url',
        'checkbox',
        'textarea',
        'date',
        'datetime',
        'number',
        'range',
        'yesno',
        'radio',
    ];


    public static function getTypeName($nb = 0)
    {
        return _n('Conditional display', 'Conditional displays', $nb, 'metademands');
    }


    public static function getIcon()
    {
        return "ti ti-sort-descending-2";
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
                        `plugin_metademands_fields_id`      int {$default_key_sign} NOT NULL DEFAULT '0',
                        `plugin_metademands_metademands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `items_id`                          int {$default_key_sign} NOT NULL DEFAULT '0',
                        `item`                              varchar(255)          DEFAULT NULL,
                        `check_value`                       varchar(255) NULL     DEFAULT NULL,
                        `show_logic`                        int(11)      NOT NULL DEFAULT '1',
                        `show_condition`                    int(11)      NOT NULL DEFAULT '0',
                        `order`                             int(11)      NOT NULL DEFAULT '0',
                        `type`                              varchar(255)          DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `plugin_metademands_fields_id` (`plugin_metademands_fields_id`),
                        KEY `plugin_metademands_metademands_id` (`plugin_metademands_metademands_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        //version 3.3.4
        $migration->changeField($table, 'order', 'order', "int(11) NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);
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
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
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

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $condition = new self();

        if ($item->getType() == Metademand::class) {
            $condition->showForMetademand($item);
        }

        return true;
    }


    /**
     * Get operators to check value by field type
     * @return array
     */
    public static function getEnumShowCondition($type): array
    {
        $enumConditions = [];
        $dropdown_types = ['dropdown', 'dropdown_object', 'dropdown_meta', 'dropdown_multiple'];
        $special_types = ['yesno', 'radio', 'checkbox'];
        $text_types = ['text', 'tel', 'email', 'url', 'textarea', ''];
        $number_types = ['date', 'datetime', 'number', 'range'];

        if (in_array($type, $dropdown_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
            ];
        } elseif (in_array($type, $special_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands'),
            ];
        } elseif (in_array($type, $text_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_REGEX => __('Regex', 'metademands'),
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands'),
            ];
        } elseif (in_array($type, $number_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_LT => '<',
                self::SHOW_CONDITION_GT => '>',
                self::SHOW_CONDITION_LE => '≤',
                self::SHOW_CONDITION_GE => '≥',
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands'),
            ];
        } elseif ($type == 0) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_LT => '<',
                self::SHOW_CONDITION_GT => '>',
                self::SHOW_CONDITION_LE => '≤',
                self::SHOW_CONDITION_GE => '≥',
                self::SHOW_CONDITION_REGEX => __('Regex', 'metademands'),
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands'),
            ];
        }

        return $enumConditions;
    }


    /**
     * Display conditions operators
     * @param int $showCondition
     *
     * @return string
     */
    public static function showCondition($showCondition): string
    {
        $return = '';
        switch ($showCondition) {
            case self::SHOW_CONDITION_EQ:
                $return = "=";
                break;
            case self::SHOW_CONDITION_NE:
                $return = "≠";
                break;
            case self::SHOW_CONDITION_LT:
                $return = "<";
                break;
            case self::SHOW_CONDITION_GT:
                $return = ">";
                break;
            case self::SHOW_CONDITION_LE:
                $return = "≤";
                break;
            case self::SHOW_CONDITION_GE:
                $return = "≥";
                break;
            case self::SHOW_CONDITION_REGEX:
                $return = __('Regex', 'metademands');
                break;
            case self::SHOW_CONDITION_EMPTY:
                $return = __('Empty', 'metademands');
                break;
            case self::SHOW_CONDITION_NOTEMPTY:
                $return = __('Not empty', 'metademands');
                break;
        }

        return $return;
    }

    /**
     * Get logical operators to create conditions
     *
     * @return array
     */
    public static function getEnumShowLogic(): array
    {
        return [
            self::SHOW_LOGIC_AND => __('AND', 'metademands'),
            self::SHOW_LOGIC_OR => __('OR', 'metademands'),
        ];
    }


    /**
     * Display logical operator
     * @param int $showLogic
     *
     * @return string
     */
    public static function showLogic($showLogic): string
    {
        $return = "";
        switch ($showLogic) {
            case self::SHOW_LOGIC_AND:
                $return = "AND";
                break;
            case self::SHOW_LOGIC_OR:
                $return = "OR";
                break;
        }
        return $return;
    }


    /**
     * Get rules for conditions
     *
     * @return array
     */
    public static function getEnumShowRule(): array
    {
        return [
            self::SHOW_RULE_ALWAYS => __('Always displayed', 'metademands'),
            self::SHOW_RULE_HIDDEN => __('Hidden unless', 'metademands'),
            self::SHOW_RULE_SHOWN => __('Displayed unless', 'metademands'),
        ];
    }


    /**
     * Display logical operator
     * @param int $showRule
     *
     * @return string
     */
    public static function showRule($showRule): string
    {
        $return = "";
        switch ($showRule) {
            case self::SHOW_RULE_ALWAYS:
                $return = __('Always displayed', 'metademands');
                break;
            case self::SHOW_RULE_HIDDEN:
                $return = __('Hidden unless', 'metademands');
                break;
            case self::SHOW_RULE_SHOWN:
                $return = __('Displayed unless', 'metademands');
                break;
        }

        return $return;
    }


    public function showForMetademand($item)
    {
        $canedit = $item->can($item->fields['id'], UPDATE);
        if (!$canedit) {
            return;
        }

        $rand = mt_rand();

        ob_start();
        \Dropdown::showFromArray('show_rule', self::getEnumShowRule(), [
            'value' => $item->fields['show_rule'],
        ]);
        $show_rule_html = ob_get_clean();

        $field  = new Field();
        $fields = $field->find([
            'type'                              => self::$field_types_available,
            'plugin_metademands_metademands_id' => $item->fields['id'],
        ]);
        $dropdown_fields = [];
        foreach ($fields as $f) {
            $dropdown_fields[$f['id']] = stripslashes($f['name']) . ' (' . $f['id'] . ') ';
        }

        ob_start();
        \Dropdown::showFromArray('show_logic', self::getEnumShowLogic());
        $show_logic_html = ob_get_clean();

        ob_start();
        \Dropdown::showFromArray('plugin_metademands_fields_id', $dropdown_fields, [
            'rand'                => $rand,
            'display_emptychoice' => true,
        ]);
        $fields_dropdown_html = ob_get_clean();

        ob_start();
        \Dropdown::showNumber('order');
        $order_dropdown_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/condition_for_metademand.html.twig', [
            'rule_action'         => Toolbox::getItemTypeFormURL(Metademand::class),
            'condition_action'    => Toolbox::getItemTypeFormURL(Condition::class),
            'metademand_id'       => $item->fields['id'],
            'show_rule'           => $item->fields['show_rule'],
            'show_rule_always'    => self::SHOW_RULE_ALWAYS,
            'show_rule_html'      => $show_rule_html,
            'show_logic_html'     => $show_logic_html,
            'fields_dropdown_html' => $fields_dropdown_html,
            'order_dropdown_html'  => $order_dropdown_html,
            'rand'                => $rand,
            'plugin_web_dir'      => PLUGIN_METADEMANDS_WEBDIR,
        ]);

        self::listConditions($item);
    }


    public static function listConditions($item)
    {
        global $CFG_GLPI;

        $cond = new Condition();
        $field = new Field();
        $rand = mt_rand();
        $canedit = $item->can($item->fields['id'], UPDATE);
        $container = 'massMetaCondition' . $rand;
        $view_container_id = "viewcondition" . $item->getID() . $rand;
        $can_link_field = Session::haveRight('plugin_metademands', UPDATE);

        $self = new self();
        $allConditions = $self->find(
            ['plugin_metademands_metademands_id' => $item->fields['id']],
            ['order', 'id'],
        );

        $rows = [];
        $scripts_html = '';
        foreach ($allConditions as $condition) {
            $cond->getFromDB($condition['id']);
            if (!$field->getFromDB($condition['plugin_metademands_fields_id'])) {
                // The referenced field is gone: drop the orphan condition
                $cond->delete(['id' => $condition['id']]);
                continue;
            }

            $edit_function = null;
            $checkbox_html = '';
            if ($canedit) {
                $edit_function = 'viewEditcondition' . $condition['id'] . $rand;
                $checkbox_html = Html::getMassiveActionCheckBox(__CLASS__, $condition['id']);
                $scripts_html .= Html::scriptBlock(
                    'function ' . $edit_function . '() {'
                    . Ajax::updateItemJsCode(
                        $view_container_id,
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        [
                            'type' => __CLASS__,
                            'parenttype' => get_class($item),
                            $item->getForeignKeyField() => $item->getID(),
                            'id' => $condition['id'],
                        ],
                        "",
                        false,
                    )
                    . '};',
                );
            }

            // displayCheckValue() writes to the output buffer instead of returning
            ob_start();
            self::displayCheckValue($condition['id']);
            $check_value_html = ob_get_clean();

            $rows[] = [
                'id' => $condition['id'],
                'edit_function' => $edit_function,
                'checkbox_html' => $checkbox_html,
                'logic' => self::showLogic($condition['show_logic']),
                'field_label' => \Dropdown::getDropdownName(
                    Field::getTable(),
                    $condition['plugin_metademands_fields_id'],
                ) . " (" . $condition['plugin_metademands_fields_id'] . ") ",
                'field_url' => $can_link_field ? $field->getLinkURL() : null,
                'type_label' => Field::getFieldTypesName($condition['type']),
                'condition_label' => self::showCondition($condition['show_condition']),
                'check_value_html' => $check_value_html,
                'order' => $condition['order'],
            ];
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
            $check_all_html = Html::getCheckAllAsCheckbox($container);
            // Built after the rows on purpose: showMassiveActions() empties
            // $_SESSION['glpimassiveactionselected'] when it is not the top one, and the
            // row checkboxes read that selection to restore their checked state.
            $massiveactionparams['ontop'] = false;
            $ma_bottom_html = Html::showMassiveActions($massiveactionparams);
            $close_form_html = Html::closeForm(false);
        }

        echo TemplateRenderer::getInstance()->render('@metademands/condition_list.html.twig', [
            'canedit' => $canedit,
            'view_container_id' => $view_container_id,
            'rows' => $rows,
            'scripts_html' => $scripts_html,
            'ma_open_html' => $ma_open_html,
            'ma_top_html' => $ma_top_html,
            'ma_bottom_html' => $ma_bottom_html,
            'close_form_html' => $close_form_html,
            'check_all_html' => $check_all_html,
        ]);
    }


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
            'field' => 'check_value',
            'name' => __('Value to check', 'metademands'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'order',
            'name' => __('Pool', 'metademands'),
            'massiveaction' => true,
            'datatype' => 'number',
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'show_logic',
            'name' => __('Logical operator', 'metademands'),
            'massiveaction' => false,
            'datatype' => 'specific',
        ];

        return $tab;
    }

    public static function displayCheckValue($ID)
    {
        $condition = new self();
        $condition->getFromDB($ID);
        $type = $condition->fields['type'];
        $itemType = $condition->fields['item'];
        $field = new Field();
        $field->getFromDB($condition->fields['plugin_metademands_fields_id']);
        $params = Field::getAllParamsFromField($field);

        switch ($type) {
            case 'dropdown_multiple':
            case 'dropdown':
            case 'dropdown_object':
                $item = new $itemType();
                $item->getFromDB($condition->fields['items_id']);
                $url = $item->getLinkURL();
                echo "<a href='$url' style='color:royalblue;'>" . htmlspecialchars((string) $item->fields['name'], ENT_QUOTES, 'UTF-8') . " (" . $item->fields['id'] . ") </a>";
                break;

            case 'text':
            case 'textarea':
            case 'tel':
            case 'email':
            case 'url':
            case 'number':
            case 'range':
                if (empty($condition->fields['check_value'])) {
                    echo "";
                } else {
                    echo RichText::getTextFromHtml($condition->fields['check_value']);
                }
                break;

            case 'date':
                $option = [
                    'value' => $condition->fields['check_value'],
                    'canedit' => false,
                    'display' => true,
                ];
                Html::showDateField('value_to_check', $option);
                break;
            case 'datetime':
                $option = [
                    'value' => $condition->fields['check_value'],
                    'canedit' => false,
                    'display' => true,
                ];
                Html::showDateTimeField('value_to_check', $option);
                break;
            case 'radio':
            case 'checkbox':
                $choices = [];
                foreach ($params['custom_values'] as $key => $val) {
                    $choices[$val['id']] = $val['name'];
                }
                echo $choices[$condition->fields['check_value']];
                break;

            case 'yesno':
                $param = [
                    'value' => $condition->fields['check_value'],
                ];
                echo Yesno::getFieldValue($param);
                break;

            case 'dropdown_meta':
                switch ($field->fields['item']) {
                    case 'other':
                        $choices = [];
                        foreach ($params['custom_values'] as $key => $val) {
                            $choices[$val['id']] = $val['name'];
                        }
                        echo $choices[$condition->fields['check_value']];
                        break;
                    case 'ITILCategory_Metademands':
                        echo ITILCategory::getFriendlyNameById($condition->fields['check_value']);
                        break;
                    case 'mydevices':
                        echo Field::getDeviceName($condition->fields['check_value']);
                        break;
                    case 'urgency':
                        echo CommonITILObject::getUrgencyName($condition->fields['check_value']);
                        break;
                    case 'impact':
                        echo CommonITILObject::getImpactName($condition->fields['check_value']);
                        break;
                    case 'priority':
                        echo CommonITILObject::getPriorityName($condition->fields['check_value']);
                        break;
                }
        }
    }


    /**
     * @param int $metademands_id
     * Create conditions array to check all fields conditions
     * @return array
     */
    public static function conditionsTab(int $metademands_id): array
    {
        $tab = [];
        $dbu = new DbUtils();
        $criterias = [
            'plugin_metademands_metademands_id' => $metademands_id,
            'ORDER' => 'order ASC, id ASC',
        ];
        $field = new Field();
        $conditions = $dbu->getAllDataFromTable('glpi_plugin_metademands_conditions', $criterias);
        if (count($conditions) > 0) {
            foreach ($conditions as $cond) {
                if ($field->getFromDB($cond['plugin_metademands_fields_id'])) {
                    $tab[$cond['id']] = [
                        'conditions_id' => $cond['id'],
                        'type' => $field->fields['type'],
                        'check_value' => $cond['check_value'],
                        'item' => $cond['item'],
                        'items_id' => $cond['items_id'],
                        'show_logic' => $cond['show_logic'],
                        'show_condition' => $cond['show_condition'],
                        'plugin_metademands_fields_id' => $cond['plugin_metademands_fields_id'],
                        'fields_id' => $cond['plugin_metademands_fields_id'],
                        'order' => $cond['order'],
                    ];
                }
            }
        }

        return $tab;
    }

    public static function showPhpLogic($int)
    {
        $return = '';
        if ($int == self::SHOW_LOGIC_OR) {
            $return = '||';
        } else {
            $return = '&&';
        }
        return $return;
    }

    public static function verifyCondition($condition): bool
    {
        $return = 0;
        $check_value = $condition['check_value'];
        $items_id = $condition['items_id'];
        $value = $condition['value'];
        $show_condition = $condition['show_condition'];
        if (!is_array($value)) {
            switch ($show_condition) {
                case self::SHOW_CONDITION_EQ:
                    if ($items_id == 0) {
                        if ($value == $check_value) {
                            $return = true;
                        }
                    } else {
                        if ($value == $items_id) {
                            $return = true;
                        }
                    }
                    break;

                case self::SHOW_CONDITION_NE:
                    if ($items_id == 0) {
                        if ($value != $check_value) {
                            $return = true;
                        }
                    } else {
                        if ($value != $items_id) {
                            $return = true;
                        }
                    }
                    break;

                case self::SHOW_CONDITION_LT:
                    if ($value < $check_value) {
                        if ($value != '') {
                            $return = true;
                        }
                    }
                    break;

                case self::SHOW_CONDITION_GT:
                    if ($value > $check_value) {
                        $return = true;
                    }
                    break;

                case self::SHOW_CONDITION_LE:
                    if ($value <= $check_value) {
                        if ($value != '') {
                            $return = true;
                        }
                    }
                    break;

                case self::SHOW_CONDITION_GE:
                    if ($value >= $check_value) {
                        $return = true;
                    }
                    break;

                case self::SHOW_CONDITION_REGEX:
                    if (preg_match($check_value, $value)) {
                        $return = true;
                    }
                    break;
                case self::SHOW_CONDITION_EMPTY:
                    if (!empty($value)) {
                        return false;
                    } else {
                        return true;
                    }
                    break;
                case self::SHOW_CONDITION_NOTEMPTY:
                    if (!empty($value)) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
            }
        } else {            // For checkbox and multiple choice dropdown field
            switch ($show_condition) {
                case self::SHOW_CONDITION_EQ:
                    if ($items_id == 0) {
                        if (in_array($check_value, $value)) {
                            $return = true;
                        }
                    } else {
                        if (in_array($items_id, $value)) {
                            $return = true;
                        }
                    }
                    break;
                case self::SHOW_CONDITION_NE:
                    if ($items_id == 0) {
                        if (!in_array($check_value, $value)) {
                            $return = true;
                        }
                    } else {
                        if (!in_array($items_id, $value)) {
                            $return = true;
                        }
                    }
                    break;
            }
        }

        return $return;
    }


    /**
     * Display field option form
     *
     * @param int $ID field (default -1)
     * @param     $options   array
     *
     * @return bool
     */
    public function showForm($ID = -1, $options = [])
    {
        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }
        if ($ID > 0) {
            $this->check($ID, UPDATE);
        } else {
            $options['itemtype'] = get_class($item);
            $options['plugin_metademands_metademands_id'] = $item->getID();

            // Create item
            $this->check(-1, CREATE, $options);
        }
        $this->initForm($ID, $options);
        $rand = mt_rand();

        $field  = new Field();
        $fields = $field->find([
            'type'                              => self::$field_types_available,
            'plugin_metademands_metademands_id' => $item->fields['id'],
        ]);
        $dropdown_fields = [];
        foreach ($fields as $f) {
            $dropdown_fields[$f['id']] = stripslashes($f['name']) . ' (' . $f['id'] . ') ';
        }

        ob_start();
        \Dropdown::showFromArray('show_logic', self::getEnumShowLogic(), [
            'value' => $this->fields['show_logic'],
        ]);
        $show_logic_html = ob_get_clean();

        ob_start();
        \Dropdown::showFromArray('plugin_metademands_fields_id', $dropdown_fields, [
            'rand'                => $rand,
            'display_emptychoice' => true,
            'value'               => $this->fields['plugin_metademands_fields_id'],
        ]);
        $fields_dropdown_html = ob_get_clean();

        $type_field_html = Field::getFieldTypesName($this->fields['type']);

        ob_start();
        \Dropdown::showFromArray('show_condition', Condition::getEnumShowCondition($this->fields['type']), [
            'display_emptychoice' => false,
            'value'               => $this->fields['show_condition'],
            'rand'                => $rand,
        ]);
        $condition_dropdown_html = ob_get_clean();

        ob_start();
        self::showCheckValue($this->fields['plugin_metademands_fields_id'], $ID);
        $check_value_html = ob_get_clean();

        ob_start();
        \Dropdown::showNumber('order', ['value' => $this->fields['order']]);
        $order_dropdown_html = ob_get_clean();

        TemplateRenderer::getInstance()->display('@metademands/condition_form.html.twig', [
            'action'                  => Toolbox::getItemTypeFormURL(Condition::class),
            'metademand_id'           => $item->fields['id'],
            'condition_id'            => $ID,
            'show_logic_html'         => $show_logic_html,
            'fields_dropdown_html'    => $fields_dropdown_html,
            'type_field_html'         => $type_field_html,
            'condition_dropdown_html' => $condition_dropdown_html,
            'check_value_html'        => $check_value_html,
            'order_dropdown_html'     => $order_dropdown_html,
            'rand'                    => $rand,
            'plugin_web_dir'          => PLUGIN_METADEMANDS_WEBDIR,
        ]);

        return true;
    }

    public static function showCheckValue($fields_id, $ID = -1)
    {
        $field = new Field();
        if ($ID > 0) {
            $condition = new self();
            $condition->getFromDB($ID);
        }
        $metademand = new Metademand();
        if ($field->getFromDB($fields_id)) {
            $metademand->getFromDB($field->fields['plugin_metademands_metademands_id']);
            $name = 'check_value';
            $item = $field->fields['item'];
            $type = $field->fields['type'];
            $options = [
                'name' => $name,
                'right' => 'all',
                'entity' => $_SESSION['glpiactive_entity'],
                'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
            ];

            $params = Field::getAllParamsFromField($field);

            if ($item != ''
                && ($type == 'dropdown'
                    || $type == 'dropdown_object'
                    || $type == 'dropdown_multiple'
                    || $type == 'dropdown_meta')) {
                if ($type == 'dropdown_meta') {
                    switch ($item) {
                        case 'other':
                            $choices = [];
                            foreach ($params['custom_values'] as $key => $val) {
                                $choices[$val['id']] = $val['name'];
                            }
                            \Dropdown::showFromArray(
                                $options['name'],
                                $choices,
                                [
                                    'width' => '100%',
                                    'value' => $condition->fields['check_value'] ?? 0,
                                ],
                            );
                            break;
                        case 'ITILCategory_Metademands':

                            $values = json_decode($metademand->fields['itilcategories_id']);
                            $params = [
                                'name' => $name,
                                'right' => 'all',
                                'class' => 'form-select itilmeta',
                                'condition' => ['id' => $values],
                                'value' => $condition->fields['check_value'] ?? 0,
                            ];
                            ITILCategory::dropdown($params);
                            break;
                        case 'mydevices':
                            $default_values = $params['default_values'];
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0,
                            ];
                            Field::dropdownMyDevices(
                                Session::getLoginUserID(),
                                $_SESSION['glpiactiveentities'],
                                0,
                                0,
                                $params,
                                $default_values,
                            );
                            break;
                        case 'urgency':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0,
                            ];
                            \Ticket::dropdownUrgency($params);
                            break;
                        case 'impact':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0,
                            ];
                            \Ticket::dropdownImpact($params);
                            break;
                        case 'priority':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0,
                            ];
                            \Ticket::dropdownPriority($params);
                            break;
                    }
                } else {
                    if ($ID > 0) {
                        $options['value'] = $condition->fields['items_id'];
                    }
                    $item::dropdown($options);
                    echo Html::hidden('check_item', ['value' => 'check_item']);
                }
            } else {
                switch ($type) {
                    default:
                        $option = [
                            'required' => true,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo Html::input(
                            "$name",
                            $option,
                        );
                        break;
                    case 'number':
                        $option = [
                            'type' => 'number',
                            'required' => true,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo Html::input(
                            "$name",
                            $option,
                        );
                        break;
                    case 'range':
                        $option = [
                            'type' => 'range',
                            'required' => true,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo Html::input(
                            "$name",
                            $option,
                        );
                        break;
                    case 'radio':
                    case 'checkbox':
                        $options = [
                            'display_emptychoice' => false,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        $choices = [];
                        foreach ($params['custom_values'] as $key => $val) {
                            $choices[$val['id']] = $val['name'];
                        }
                        \Dropdown::showFromArray(
                            "$name",
                            $choices,
                            $options,
                        );
                        break;
                    case 'date':
                        $option = [
                            'size' => 60,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo "<span style='width: 50%!important;display: -webkit-box;'>";
                        Html::showDateField(
                            "$name",
                            $option,
                        );
                        echo "</span>";
                        break;
                    case 'datetime':
                        $option = [
                            'size' => 60,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo "<span style='width: 50%!important;display: -webkit-box;'>";
                        Html::showDateTimeField(
                            "$name",
                            $option,
                        );
                        echo "</span>";
                        break;

                    case 'yesno':
                        $option = [
                            'display_emptychoice' => false,
                            'width' => '70px',
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        //                        $choice[0] = \Dropdown::EMPTY_VALUE;
                        $choice[1] = __('No');
                        $choice[2] = __('Yes');
                        \Dropdown::showFromArray(
                            $name,
                            $choice,
                            $option,
                        );
                        break;
                }
            }
        }
    }
}
