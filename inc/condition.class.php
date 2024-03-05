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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMetademandsCondition
 */
class PluginMetademandsCondition extends CommonDBChild
{

    /* DEFINES CONST */
    //logical operator
    const SHOW_LOGIC_AND = 1;
    const SHOW_LOGIC_OR = 2;

    //operators
    const SHOW_CONDITION_EQ = 1;
    const SHOW_CONDITION_NE = 2;
    const SHOW_CONDITION_LT = 3;
    const SHOW_CONDITION_GT = 4;
    const SHOW_CONDITION_LE = 5;
    const SHOW_CONDITION_GE = 6;
    const SHOW_CONDITION_REGEX = 7;

    const SHOW_CONDITION_EMPTY = 8;

    const SHOW_CONDITION_NOTEMPTY = 9;

    const SHOW_RULE_ALWAYS = 1;
    const SHOW_RULE_HIDDEN = 2;
    const SHOW_RULE_SHOWN = 3;

    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

    public static $field_types_available = ['', 'dropdown', 'dropdown_object', 'dropdown_meta', 'dropdown_multiple', 'text', 'checkbox', 'textarea',
        'date', 'datetime', 'number', 'yesno', 'radio'];


    public static function getTypeName($nb = 0)
    {
        return _n('Conditional display', 'Conditional displays', $nb, 'metademands');
    }


    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()]
                    )
                );
            }
            return self::getTypeName();
        }
        return '';

    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $condition = new self();

        if ($item->getType() == 'PluginMetademandsMetademand') {
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
        $text_types = ['text', 'textarea', ''];
        $number_types = ['date', 'datetime', 'number'];

        if (in_array($type, $dropdown_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
            ];
        } else if (in_array($type, $special_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
            ];
        } else if (in_array($type, $text_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_REGEX => __('Regex', 'metademands'),
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands')
            ];
        } else if (in_array($type, $number_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_LT => '<',
                self::SHOW_CONDITION_GT => '>',
                self::SHOW_CONDITION_LE => '≤',
                self::SHOW_CONDITION_GE => '≥',
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands')
            ];
        } else if ($type == 0) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_LT => '<',
                self::SHOW_CONDITION_GT => '>',
                self::SHOW_CONDITION_LE => '≤',
                self::SHOW_CONDITION_GE => '≥',
                self::SHOW_CONDITION_REGEX => __('Regex', 'metademands'),
                self::SHOW_CONDITION_EMPTY => __('Empty', 'metademands'),
                self::SHOW_CONDITION_NOTEMPTY => __('Not empty', 'metademands')
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
                $return = "Regex";
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
        if ($canedit) {
            echo "<form name = 'form' method='post' action='" . Toolbox::getItemTypeFormURL('PluginMetademandsMetademand') . "'>";
            echo Html::hidden('id', ['value' => $item->fields['id']]);
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th colspan='2'> " . __('Rule', 'metademands') . " </th>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Submit button display', 'metademands') . "</td>";
            echo "<td>";
            $options = [
                'value' => $item->fields['show_rule']
            ];
            Dropdown::showFromArray(
                'show_rule',
                self::getEnumShowRule(),
                $options
            );
            echo "</td>";
            echo "<tr>";
            echo "<td colspan = '2' class='tab_bg_2 center' colspan='4'>";
            echo "<input class='btn btn-primary' type='submit' name='apply_rule' value=\"" .
                _sx("button", __('Apply', 'metademands')) . "\" class='submit'>";
            echo "</td>";
            echo "<tr>";
            echo "</table>";
            Html::closeForm();
            $rand = mt_rand();

            if ($item->fields['show_rule'] != self::SHOW_RULE_ALWAYS) {


                echo "<form name = 'form' method='post' action='" . Toolbox::getItemTypeFormURL('PluginMetademandsCondition') . "'>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr>";
                echo "<th> " . __('Logical operator', 'metademands') . " </th>";
                echo "<th>" . __('Field', 'metademands') . " <span style='color : red'> *</span></th>";
                echo "<th>" . __('Field type', 'metademands') . "</th>";
                echo "<th>" . __('Equality operator', 'metademands') . " <span style='color : red'> *</span></th>";
                echo "<th>" . __('Value to check', 'metademands') . "</th>";
                echo "<th>" . __('Pool', 'metademands') . " <span style='color : red'> *</span>";
                echo "<h6 style='color: royalblue'>" . __('Order of execution and grouping of conditions', 'metademands') . "</h6>";
                echo "</th>";
                echo "<th></th>";


                $field = new PluginMetademandsField();

                $fields = $field->find(
                    [
                        'type' => self::$field_types_available,
                        'plugin_metademands_metademands_id' => $item->fields['id'],
                    ]
                );
                $dropdown_fields = [];
                foreach ($fields as $f) {
                    $dropdown_fields[$f['id']] = $f['name'] . " (" . $f['id'] . ") ";
                }
                echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
                echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
                echo "<tr>";
                echo "<td>";
                Dropdown::showFromArray(
                    'show_logic',
                    self::getEnumShowLogic(),

                );
                echo "</td>";

                echo "<td>";

                Dropdown::showFromArray(
                    'plugin_metademands_fields_id',
                    $dropdown_fields,
                    ['rand' => $rand,
                        'display_emptychoice' => true]
                );
                echo "</td>";
                echo "<td>";
                Ajax::updateItemOnSelectEvent(
                    "dropdown_plugin_metademands_fields_id$rand",
                    "show_type_field$rand",
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_type_field.php",
                    [
                        'fields_id' => '__VALUE__',
                        'rand' => $rand
                    ]
                );
                echo "<span id = 'show_type_field$rand'>";
                echo "</span>";
                echo "</td>";

                echo "<td>";
                Ajax::updateItemOnSelectEvent(
                    "dropdown_plugin_metademands_fields_id$rand",
                    "show_dropdown_condition_$rand",
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_conditions.php",
                    [
                        'fields_id' => '__VALUE__',
                        'rand' => $rand
                    ]
                );
                echo "<span id = 'show_dropdown_condition_$rand'>";

                echo "</span>";
                echo "</td>";
                echo "<td>";
                Ajax::updateItemOnSelectEvent(
                    "dropdown_plugin_metademands_fields_id$rand",
                    "show_value_to_check_$rand",
                    PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_check_value.php",
                    [
                        'fields_id' => '__VALUE__',
                        'rand' => $rand
                    ]
                );

                echo "<span id = 'show_value_to_check_$rand'>";

                echo "</span>";
                echo "</td>";
                echo "<td>";
                Dropdown::showNumber('order');
                echo "</td>";
                echo "<td>";
                echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
                echo "</td>";
                echo "</tr>";
                echo "</table>";
                Html::closeForm();
            }
            self::listConditions($item);

        }

    }


    static function listConditions($item)
    {
        global $CFG_GLPI;
        $cond = new PluginMetademandsCondition();
        $dbu = new DbUtils();
        $field = new PluginMetademandsField();
        $rand = mt_rand();
        $canedit = $item->can($item->fields['id'], UPDATE);

        if ($canedit) {
            echo "<div id='viewcondition" . $item->getType() . $item->getID() . "$rand'></div>\n";
        }
        $self = new self();
        $allConditions = [];
        $allConditions = $self->find(['plugin_metademands_metademands_id' => $item->fields['id']], ['order','id']);
        if (count($allConditions) > 0) {
            html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $params = ['item' => __CLASS__,
                'container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($params);


            echo "<div class ='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='3'>" . __("List of conditions", 'metademands') . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th> " . __('ID') . " </th>";
            echo "<th> " . __('Logical operator', 'metademands') . " </th>";
            echo "<th>" . __('Field', 'metademands') . "</th>";
            echo "<th>" . __('Type') . "</th>";
            echo "<th>" . __('Equality operator', 'metademands') . "</th>";
            echo "<th>" . __('Value to check', 'metademands') . "</th>";
            echo "<th>" . __('Pool', 'metademands') . "</th>";

            foreach ($allConditions as $condition) {
                $cond->getFromDB($condition['id']);
                if ($field->getFromDB($condition['plugin_metademands_fields_id'])) {
                    $onhover = '';
                    if ($canedit) {
                        $onhover = "style='cursor:pointer'
                           onClick=\"viewEditcondition" . $item->getType() . $condition['id'] . "$rand();\"";
                    }

                    echo "<tr class = 'tab_bg_1'>";
                    if ($canedit) {
                        echo "<td class='center'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $condition["id"]);
                        echo "</td>";
                    }

                    echo "<td $onhover>";
                    if ($canedit) {
                        echo "\n<script type='text/javascript' >\n";
                        echo "function viewEditcondition" . $item->getType() . $condition['id'] . "$rand() {\n";
                        $params = ['type' => __CLASS__,
                            'parenttype' => get_class($item),
                            $item->getForeignKeyField() => $item->getID(),
                            'id' => $condition["id"]];
                        Ajax::updateItemJsCode("viewcondition" . $item->getType() . $item->getID() . "$rand",
                            $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                            $params);
                        echo "};";
                        echo "</script>\n";

                        echo($condition['id']);
                        echo "</td>";

                        echo "<td $onhover>";
                        echo self::showLogic($condition['show_logic']);
                        echo "</td>";
                        echo "<td $onhover>";
                        if (Session::haveRight('plugin_metademands', UPDATE)) {
                            $fieldURL = $field->getLinkURL();
                            echo "<a href='$fieldURL' style='color:royalblue;'>";
                        }
                        echo Dropdown::getDropdownName(PluginMetademandsField::getTable(), $condition['plugin_metademands_fields_id']) . " (" . $condition['plugin_metademands_fields_id'] . ") ";
                        if (Session::haveRight('plugin_metademands', UPDATE)) {
                            echo "</a> ";
                        }
                        echo "</td>";

                        echo "<td $onhover>";
                        echo PluginMetademandsField::getFieldTypesName($condition['type']);
                        echo "</td>";

                        echo "<td  $onhover>";
                        echo self::showCondition($condition['show_condition']);
                        echo "</td>";

                        echo "<td $onhover>";
                        self::displayCheckValue($condition['id']);
                        echo "</td>";

                        echo "<td $onhover>";
                        echo $condition['order'];
                        echo "</td>";
                        echo "</tr>";


                    }
                } else {
                    $input = [
                        'id' => $condition['id'],
                    ];
                    $cond->delete($input);
                }
            }

        } else {
            echo "<br><div class='alert alert-info center'>";
            echo __("No conditions founded", 'metademands');
            echo "</div>";
        }
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(1)
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'check_value',
            'name' => __('Value to check', 'metademands'),
            'datatype' => 'text'
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'order',
            'name' => __('Pool', 'metademands'),
            'massiveaction' => true,
            'datatype' => 'number'
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'show_logic',
            'name' => __('Logical operator', 'metademands'),
            'massiveaction' => false,
            'datatype' => 'specific'
        ];

        return $tab;
    }
    static function displayCheckValue($ID)
    {
        $condition = new self();
        $condition->getFromDB($ID);
        $type = $condition->fields['type'];
        $itemType = $condition->fields['item'];
        $field = new PluginMetademandsField();
        $field->getFromDB($condition->fields['plugin_metademands_fields_id']);

        switch ($type) {
            case 'dropdown_multiple':
            case 'dropdown' :
            case 'dropdown_object':
                $item = new $itemType();
                $item->getFromDB($condition->fields['items_id']);
                $url = $item->getLinkURL();
                echo "<a href='$url' style='color:royalblue;'>" . $item->fields['name'] . " (" . $item->fields['id'] . ") </a>";
                break;

            case 'text':
            case 'textarea':
            case 'number':
            if (empty($condition->fields['check_value'])) {
                echo "";
            } else {
                echo Glpi\RichText\RichText::getTextFromHtml($condition->fields['check_value']);
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
            case 'datetime' :
                $option = [
                    'value' => $condition->fields['check_value'],
                    'canedit' => false,
                    'display' => true,
                ];
                Html::showDateTimeField('value_to_check', $option);
                break;
            case 'radio':
            case 'checkbox':
                $choices = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                echo $choices[$condition->fields['check_value']];
                break;

            case 'yesno':
                $param = [
                    'value' => $condition->fields['check_value']
                ];
                echo PluginMetademandsYesno::getFieldValue($param);
                break;

            case 'dropdown_meta':
                switch ($field->fields['item']) {
                    case 'other':
                        $choices = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                        echo $choices[$condition->fields['check_value']];
                        break;
                    case 'ITILCategory_Metademands':
                        echo ITILCategory::getFriendlyNameById($condition->fields['check_value']);
                        break;
                    case 'mydevices':
                        echo PluginMetademandsField::getDeviceName($condition->fields['check_value']);
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
    static function conditionsTab(int $metademands_id): array
    {
        $tab = [];
        $dbu = new DbUtils();
        $criterias = [
            'plugin_metademands_metademands_id' => $metademands_id,
            'ORDER' => 'order ASC, id ASC'
        ];
        $field = new PluginMetademandsField();
        $conditions = $dbu->getAllDataFromTable('glpi_plugin_metademands_conditions', $criterias);
        if (count($conditions) > 0) {
            foreach ($conditions as $cond) {
                $field->getFromDB($cond['plugin_metademands_fields_id']);
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
                    'order' => $cond['order']
                ];
            }
        }

        return $tab;
    }

    static function showPhpLogic($int)
    {
        $return = '';
        if ($int == self::SHOW_LOGIC_OR) {
            $return = '||';
        } else {
            $return = '&&';
        }
        return $return;
    }

    static function verifyCondition($condition): bool
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
                        if($value != ''){
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
                        if($value != ''){
                            $return = true;
                        }
                    }
                    break;

                case self::SHOW_CONDITION_GE:
                    if ($value >= $check_value) {
                        $return = true;
                    }
                    break;

                case self::SHOW_CONDITION_REGEX :
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
    function showForm($ID = -1, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }
        if ($ID > 0) {
            $this->check($ID, UPDATE);
        } else {
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();

            // Create item
            $this->check(-1, CREATE, $options);
        }
        $this->initForm($ID, $options);
        $rand = mt_rand();
        $field = new PluginMetademandsField();

        $fields = $field->find(
            [
                'type' => self::$field_types_available,
                'plugin_metademands_metademands_id' => $item->fields['id'],
            ]
        );
        $dropdown_fields = [];
        foreach ($fields as $f) {
            $dropdown_fields[$f['id']] = $f['name'] . " (" . $f['id'] . ") ";
        }
        echo "<form name = 'form' method='post' action='" . Toolbox::getItemTypeFormURL('PluginMetademandsCondition') . "'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr>";
        echo "<th> " . __('Logical operator', 'metademands') . " </th>";
        echo "<th>" . __('Field', 'metademands') . " <span style='color : red'> *</span></th>";
        echo "<th>" . __('Field type', 'metademands') . "</th>";
        echo "<th>" . __('Equality operator', 'metademands') . " <span style='color : red'> *</span></th>";
        echo "<th>" . __('Value to check', 'metademands') . "</th>";
        echo "<th>" . __('Pool', 'metademands') . " <span style='color : red'> *</span>";
        echo "<h6 style='color: royalblue'>" . __('Order of execution and grouping of conditions', 'metademands') . "</h6>";
        echo "</th>";
        echo "<th></th>";
        echo "</tr>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
        echo Html::hidden('id', ['value' => $ID]);
        echo "<tr>";
        echo "<td>";
        Dropdown::showFromArray(
            'show_logic',
            self::getEnumShowLogic(),
            ['value' => $this->fields['show_logic']]
        );
        echo "</td>";

        echo "<td>";

        Dropdown::showFromArray(
            'plugin_metademands_fields_id',
            $dropdown_fields,
            [
                'rand' => $rand,
                'display_emptychoice' => true,
                'value' => $this->fields['plugin_metademands_fields_id']
            ]
        );
        echo "</td>";
        echo "<td>";
        Ajax::updateItemOnSelectEvent(
            "dropdown_plugin_metademands_fields_id$rand",
            "show_type_field$rand",
            PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_type_field.php",
            [
                'fields_id' => '__VALUE__',
                'rand' => $rand
            ]
        );
        echo "<span id = 'show_type_field$rand'>";
        echo PluginMetademandsField::getFieldTypesName($this->fields['type']);
        echo "</span>";
        echo "</td>";

        echo "<td>";
        Ajax::updateItemOnSelectEvent(
            "dropdown_plugin_metademands_fields_id$rand",
            "show_dropdown_condition_$rand",
            PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_conditions.php",
            [
                'fields_id' => '__VALUE__',
                'rand' => $rand
            ]
        );
        echo "<span id = 'show_dropdown_condition_$rand'>";
        $options = [
            'display_emptychoice' => false,
            'value' => $this->fields['show_condition'],
            'rand' => $rand,
        ];
        Dropdown::showFromArray(
            'show_condition',
            PluginMetademandsCondition::getEnumShowCondition($this->fields['type']),
            $options
        );
        echo "</span>";
        echo "</td>";
        echo "<td>";
        Ajax::updateItemOnSelectEvent(
            "dropdown_plugin_metademands_fields_id$rand",
            "show_value_to_check_$rand",
            PLUGIN_METADEMANDS_WEBDIR . "/ajax/show_check_value.php",
            [
                'fields_id' => '__VALUE__',
                'rand' => $rand
            ]
        );

        echo "<span id = 'show_value_to_check_$rand'>";
        self::showCheckValue($this->fields['plugin_metademands_fields_id'], $ID);
        echo "</span>";
        echo "</td>";
        echo "<td>";
        $option = [
            'value' => $this->fields['order']
        ];
        Dropdown::showNumber('order', $option);
        echo "</td>";
        echo "<td>";
        echo Html::submit(_sx('button', __('Update')), ['name' => 'update', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        return true;
    }

    static function showCheckValue($fields_id, $ID = -1){
        $field = new PluginMetademandsField();
        if($ID > 0){
            $condition = new self();
            $condition->getFromDB($ID);
        }
        $metademand = new PluginMetademandsMetademand();
        if ($field->getFromDB($fields_id)) {
            $metademand->getFromDB($field->fields['plugin_metademands_metademands_id']);
            $name = 'check_value';
            $item = $field->fields['item'];
            $type = $field->fields['type'];
            $options = [
                'name' => $name,
                'right' => 'all',
                'entity' => $_SESSION['glpiactive_entity'],
                'entity_sons' => $_SESSION['glpiactive_entity_recursive']
            ];

            if ($item != ''
                && ($type == 'dropdown'
                    || $type == 'dropdown_object'
                    || $type == 'dropdown_multiple'
                    || $type == 'dropdown_meta')) {
                if ($type == 'dropdown_meta') {
                    switch ($item) {
                        case 'other':
                            $choices = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                            Dropdown::showFromArray(
                                $options['name'],
                                $choices,
                                ['width' => '100%',
                                  'value' => $condition->fields['check_value'] ?? 0
                                ]
                            );
                            break;
                        case 'ITILCategory_Metademands':

                            $values = json_decode($metademand->fields['itilcategories_id']);
                            $params = [
                                'name' => $name,
                                'right' => 'all',
                                'class' => 'form-select itilmeta',
                                'condition' => ['id' => $values],
                                'value' => $condition->fields['check_value'] ?? 0
                            ];
                            ITILCategory::dropdown($params);
                            break;
                        case 'mydevices':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0
                            ];
                            PluginMetademandsField::dropdownMyDevices(Session::getLoginUserID(), $_SESSION['glpiactiveentities'], 0, 0, $params);
                            break;
                        case 'urgency':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0
                            ];
                            Ticket::dropdownUrgency($params);
                            break;
                        case 'impact':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0
                            ];
                            Ticket::dropdownImpact($params);
                            break;
                        case 'priority':
                            $params = [
                                'name' => $name,
                                'value' => $condition->fields['check_value'] ?? 0
                            ];
                            Ticket::dropdownPriority($params);
                            break;
                    }
                } else {
                    if ($ID > 0){
                        $options['value'] = $condition->fields['items_id'];
                    }
                    $item::dropdown($options);
                    echo Html::hidden('check_item', ['value' => 'check_item']);
                }
            } else {
                switch ($type) {
                    default :
                        $option = [
                            'required' => true
                        ];
                        if($ID > 0 ){
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo Html::input(
                            "$name",
                            $option
                        );
                        break;
                    case 'number' :
                        $option = [
                            'type' => 'number',
                            'required' => true,
                        ];
                        if($ID > 0 ){
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo Html::input(
                            "$name",
                            $option
                        );
                        break;
                    case 'radio':
                    case 'checkbox' :
                        $options = [
                            'display_emptychoice' => false,
                        ];
                        if ($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        $choices = PluginMetademandsField::_unserialize($field->fields['custom_values']);
                        Dropdown::showFromArray(
                            "$name",
                            $choices,
                            $options
                        );
                        break;
                    case 'date' :
                        $option = [
                            'size' => 60
                        ];
                        if($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo "<span style='width: 50%!important;display: -webkit-box;'>";
                        Html::showDateField(
                            "$name",
                            $option
                        );
                        echo "</span>";
                        break;
                    case 'datetime' :
                        $option = [
                            'size' => 60
                        ];
                        if($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        echo "<span style='width: 50%!important;display: -webkit-box;'>";
                        Html::showDateTimeField(
                            "$name",
                            $option
                        );
                        echo "</span>";
                        break;

                    case 'yesno' :
                        $option = [
                            'display_emptychoice' => false,
                            'width' => '70px',
                        ];
                        if($ID > 0) {
                            $option['value'] = $condition->fields['check_value'];
                        }
                        $choice[1] = __('No');
                        $choice[2] = __('Yes');
                        Dropdown::showFromArray($name, $choice, $option
                        );
                        break;
                }

            }
        }
    }

}

