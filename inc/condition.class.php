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
 * Class PluginMetademandsPredicate
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

    const SHOW_CONDITION_NULL = 7;
    const SHOW_CONDITION_REGEX = 8;

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
        return self::createTabEntry(self::getTypeName());

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
                self::SHOW_CONDITION_NULL => __('Is empty', 'metademands')
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
                self::SHOW_CONDITION_NULL => __('Is empty', 'metademands')
            ];
        } else if (in_array($type, $number_types)) {
            $enumConditions = [
                self::SHOW_CONDITION_EQ => '=',
                self::SHOW_CONDITION_NE => '≠',
                self::SHOW_CONDITION_LT => '<',
                self::SHOW_CONDITION_GT => '>',
                self::SHOW_CONDITION_LE => '≤',
                self::SHOW_CONDITION_GE => '≥',
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
                self::SHOW_CONDITION_NULL => __('Is empty', 'metademands')

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
            case self::SHOW_CONDITION_NULL:
                $return = __('Is empty', 'metademands');
                break;
            case self::SHOW_CONDITION_REGEX:
                $return = "Regex";
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
            self::SHOW_LOGIC_AND => 'AND',
            self::SHOW_LOGIC_OR => 'OR',
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
            if ($item->fields['show_rule'] == 1) {
                echo "<br><div class='alert alert-important alert-info center'>";
                echo __("Soumission button always displayed", 'metademands');
                echo "</div>";
            }
            echo "<form name = 'form' method='post' action='" . Toolbox::getItemTypeFormURL('PluginMetademandsMetademand') . "'>";
            echo Html::hidden('id', ['value' => $item->fields['id']]);
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th colspan='2'> " . __('Rule', 'metademands') . " </th>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Display rule', 'metademands') . "</td>";
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
                _sx("button", "Save") . "\" class='submit'>";
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
                echo "<th>" . __('Value to check', 'metademands') . " <span style='color : red'> *</span></th>";
                echo "<th>" . __('Pool', 'metademands') . " <span style='color : red'> *</span>";
                echo "<h6 style='color: royalblue'>" . __('Order of execution and grouping of conditions', 'metademands') . "</h6>";
                echo "</th>";
                echo "<th></th>";

                $conditions = self::find(['plugin_metademands_metademands_id' => $item->fields['id']]);
                $field = new PluginMetademandsField();
                $params = [
                    'plugin_metademands_metademands_id' => $item->fields['id'],
                    'type' => self::$field_types_available
                ];
                $fields = $field->find(
                    [
                        'type' => self::$field_types_available
                    ]
                );
                $dropdown_fields = [];
                foreach ($fields as $f) {
                    $dropdown_fields[$f['id']] = $f['name'];
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
                echo Html::submit(_sx('button', 'Add'), ['name' => 'add_condition', 'class' => 'btn btn-primary']);
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
        $allConditions = [];
        $allConditions = $dbu->getAllDataFromTable('glpi_plugin_metademands_conditions', ['plugin_metademands_metademands_id' => $item->fields['id'], 'ORDER' => 'order ASC']);
        if (count($allConditions) > 0) {
            html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $params = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($params);

            echo "<div class ='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='3'>" . __("List of conditions", 'metademands') . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th> " . __('ID', 'metademands') . " </th>";
            echo "<th> " . __('Logical operator', 'metademands') . " </th>";
            echo "<th>" . __('Field', 'metademands') . "</th>";
            echo "<th>" . __('Type') . "</th>";
            echo "<th>" . __('Equality operator', 'metademands') . "</th>";
            echo "<th>" . __('Value to check', 'metademands') . "</th>";
            echo "<th>" . __('Order', 'metademands') . "</th>";

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
                        Ajax::updateItemJsCode(
                            "viewstepbybloc" . $item->getType() . $item->getID() . "$rand",
                            $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                            $params
                        );
                        echo "};";
                        echo "</script>\n";
                        echo($condition['id']);
                        echo "</td>";
                        echo "<td>";
                        echo self::showLogic($condition['show_logic']);
                        echo "</td>";
                        echo "<td $onhover>";
                        if (Session::haveRight('plugin_metademands', UPDATE)) {
                            $fieldURL = $field->getLinkURL();
                            echo "<a href='$fieldURL' style='color:royalblue;'>";
                        }
                        echo Dropdown::getDropdownName(PluginMetademandsField::getTable(), $condition['plugin_metademands_fields_id']);
                        if (Session::haveRight('plugin_metademands', UPDATE)) {
                            $fieldURL = $field->getLinkURL();
                            echo "</a> ";
                        }
                        echo "</td>";
                        echo "<td>";
                        if (class_exists($condition['item'])) {
                            echo $condition['item']::getTypeName();
                        } else {
                            echo $condition['item'];
                        }
                        echo "</td>";
                        echo "<td>";
                        echo self::showCondition($condition['show_condition']);
                        echo "</td>";
                        echo "<td>";
                        self::displayCheckValue($condition['id']);
                        echo "</td>";
                        echo "<td>";
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
            echo "<br><div class='alert alert-important alert-info center'>";
            echo __("No conditions", 'metademands');
            echo "</div>";
        }
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
                echo Glpi\RichText\RichText::getTextFromHtml($condition->fields['check_value']);
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
        }

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
     * @param int $metademands_id
     * Create condtions array to check all fields conditions
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

//        if($show_condition != self::SHOW_CONDITION_NULL && empty($value)) {
//            return 0;
//        }

        if($show_condition == self::SHOW_CONDITION_NULL && empty($value)){
            return 1;
        }

        if($show_condition == self::SHOW_CONDITION_NULL && !empty($value)){
            return 0;
        }

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
                        $return = true;
                    }
                    break;

                case self::SHOW_CONDITION_GT:
                    if ($value > $check_value) {
                        $return = true;
                    }
                    break;

                case self::SHOW_CONDITION_LE:
                    if ($value <= $check_value) {
                        $return = true;
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

//                case self::SHOW_CONDITION_NULL:
//                    if (is_null($value)) {
//                        $return = true;
//                    }
//                    break;
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

}

