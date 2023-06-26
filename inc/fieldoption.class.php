<?php

/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2003-2019 by the Metademands Development Team.

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
 * PluginMetademandsFieldOption Class
 *
 **/
class PluginMetademandsFieldOption extends CommonDBChild
{

    public static $itemtype = 'PluginMetademandsField';
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    static $rightname = 'plugin_metademands';

    public static $allowed_options_types = ['yesno',
        'checkbox', 'radio', 'dropdown_multiple', 'dropdown', 'dropdown_object',
        'parent_field', 'text', 'textarea'];
    public static $allowed_options_items = ['other', 'ITILCategory_Metademands'];

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    static function getTypeName($nb = 0)
    {
        return _n('Option', 'Options', $nb, 'metademands');
    }


    static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }


    static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

    /**
     * @return bool
     */
    static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }

    /**
     * Get the standard massive actions which are forbidden
     *
     * @return array an array of massive actions
     **@since version 0.84
     *
     * This should be overloaded in Class
     *
     */
    function getForbiddenStandardMassiveAction()
    {

        $forbidden = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $nb = self::getNumberOfOptionsForItem($item);
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
    }


    /**
     * Return the number of translations for an item
     *
     * @param item
     *
     * @return int number of translations for this item
     */
    static function getNumberOfOptionsForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable($dbu->getTableForItemType(__CLASS__),
            ["plugin_metademands_fields_id" => $item->getID()]);
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     **
     *
     * @return bool
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showOptions($item);

        return true;
    }


    /**
     * Display all options of a field
     *
     * @param $item a Dropdown item
     *
     * @return true;
     **/
    static function showOptions($item)
    {
        global $CFG_GLPI;

        $rand = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        $allowed_options_types = self::$allowed_options_types;
        $allowed_options_items = self::$allowed_options_items;
        $new_fields = [];

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                if (Plugin::isPluginActive($plug)) {
                    $new_fields = self::addPluginFieldItems($plug);
                    if (is_array($new_fields) && count($new_fields) > 0) {
                        $allowed_options_types = array_merge($allowed_options_types, $new_fields);
                    }
                }
            }
        }

        if (!in_array($item->fields['type'], $allowed_options_types)
            && !in_array($item->fields['item'], $allowed_options_items)) {
            echo __('No options are allowed for this field type', 'metademands');
            return false;
        }

        if ($canedit) {
            echo "<div id='viewoption" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addOption" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = ['type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id' => -1];
            Ajax::updateItemJsCode("viewoption" . $item->getType() . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params);
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
                "<a class='submit btn btn-primary' href='javascript:addOption" .
                $item->getType() . $item->getID() . "$rand();'>" . __('Add a new option', 'metademands') .
                "</a></div><br>";
        }


//        $field = new PluginMetademandsField();
//        $field->getFromDB($item->getID());

        $self = new self();

        $options = $self->find(['plugin_metademands_fields_id' => $item->getID()]);
        if (is_array($options) && count($options) > 0) {

            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='10'>" . __("List of options", 'metademands') . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("ID") . "</th>";
            echo "<th>" . __('Value to check', 'metademands') . "</th>";
            echo "<th>" . __('Link a task to the field', 'metademands') . "</th>";
            echo "<th>" . __('Link a field to the field', 'metademands') . "</th>";
            echo "<th>" . __('Link a hidden field', 'metademands') . "</th>";
            echo "<th>" . __('Link a hidden block', 'metademands') . "</th>";
            echo "<th>" . __('Childs blocks', 'metademands') . "</th>";
            echo "<th>" . __('Link a validation', 'metademands') . "</th>";
            echo "<th>" . __('Bind to the value of this checkbox', 'metademands') . "</th>";
            echo "</tr>";

            //
            foreach ($options as $data) {

                $data['item'] = $item->fields['item'];
                $data['type'] = $item->fields['type'];
                $data['custom_values'] = $item->fields['custom_values'];

                $onhover = '';
                if ($canedit) {
                    $onhover = "style='cursor:pointer'
                           onClick=\"viewEditOption" . $item->getType() . $data['id'] . "$rand();\"";
                }
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }

                echo "<td $onhover>";
                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditOption" . $item->getType() . $data['id'] . "$rand() {\n";
                    $params = ['type' => __CLASS__,
                        'parenttype' => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id' => $data["id"]];
                    Ajax::updateItemJsCode("viewoption" . $item->getType() . $item->getID() . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params);
                    echo "};";
                    echo "</script>\n";
                }
                echo $data['id'];
                echo "</td>";
                echo "<td $onhover>";
                echo self::getValueToCheck($data);
                echo "</td>";

                echo "<td $onhover>";
                $tasks = new PluginMetademandsTask();
                if ($tasks->getFromDB($data['plugin_metademands_tasks_id'])) {
                    if ($tasks->fields['type'] == PluginMetademandsTask::METADEMAND_TYPE) {
                        $metatask = new PluginMetademandsMetademandTask();
                        if ($metatask->getFromDBByCrit(["plugin_metademands_tasks_id" => $data['plugin_metademands_tasks_id']])) {
                            echo Dropdown::getDropdownName('glpi_plugin_metademands_metademands', $metatask->fields['plugin_metademands_metademands_id']);
                        }
                    } else {
                        echo $tasks->getName();
                    }

                }

                echo "</td>";

                echo "<td $onhover>";
                $fields = new PluginMetademandsField();
                $fields_data = $fields->find(['id' => $data['fields_link']]);
                foreach ($fields_data as $id => $value) {
                    echo $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));
                }
                echo "</td>";

                echo "<td $onhover>";
                $fields = new PluginMetademandsField();
                $fields_data = $fields->find(['id' => $data['hidden_link']]);

                foreach ($fields_data as $id => $value) {
                    echo $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));
                }
                echo "</td>";

                echo "<td $onhover>";
                if ($data['hidden_block'] > 0) {
                    echo $data['hidden_block'];
                }
                echo "</td>";

                echo "<td $onhover>";
                $blocks = json_decode($data["childs_blocks"], true);
                $i = 0;
                if (is_array($blocks)) {
                    $nb = count($blocks);
                    if ($nb > 0) {
                        foreach ($blocks as $block) {
                            if (is_array($block)) {
                                foreach ($block as $block_number) {
                                    $i++;
                                    echo $block_number;
                                    if ($i < $nb) {
                                        echo ", ";
                                    }
                                }
                            }
                        }
                    }
                }

                echo "</td>";

                echo "<td $onhover>";
                echo getUserName($data["users_id_validate"]);
                echo "</td>";

                echo "<td $onhover>";
                $fields = new PluginMetademandsField();
                if ($fields->getFromDB($data['checkbox_id'])) {
                    echo $fields->getName();
                    $arrayValues = PluginMetademandsField::_unserialize($fields->fields['custom_values']);
                    echo "<br>";
                    echo $arrayValues[$data["checkbox_value"]];
                }

                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }

        } else {
            echo "<div class='center first-bloc'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr  class='tab_bg_1'><td class='center'>" . __('No item to display') . "</td></tr>";
            echo "</table>";
            echo "</div>";
        }


//      $iterator = $DB->request([
//                                  'FROM'  => getTableForItemType(__CLASS__),
//                                  'WHERE' => [
//                                     'itemtype' => $item->getType(),
//                                     'items_id' => $item->getID(),
//                                     'field'    => ['<>', 'completename']
//                                  ],
//                                  'ORDER' => ['language ASC']
//                               ]);
//      if (count($iterator)) {


        return true;
    }

    public function canCreateItem()
    {

        return true;

    }

    /**
     * Display translation form
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

        $this->showFormHeader($options);

        $params = [
            'item' => $item->fields['item'],
            'type' => $item->fields['type'],
            'plugin_metademands_metademands_id' => $item->fields['plugin_metademands_metademands_id'],
            'plugin_metademands_fields_id' => $item->getID(),
            'plugin_metademands_tasks_id' => $this->fields['plugin_metademands_tasks_id'] ?? 0,
            'fields_link' => $this->fields['fields_link'] ?? 0,
            'hidden_link' => $this->fields['hidden_link'] ?? 0,
            'hidden_block' => $this->fields['hidden_block'] ?? 0,
            'custom_values' => $item->fields['custom_values'] ?? 0,
            'check_value' => $this->fields['check_value'] ?? 0,
            'users_id_validate' => $this->fields['users_id_validate'] ?? 0,
            'checkbox_id' => $this->fields['checkbox_id'] ?? 0,
            'checkbox_value' => $this->fields['checkbox_value'] ?? 0,
        ];


        if ($this->fields['childs_blocks'] != null) {
            $params['childs_blocks'] = json_decode($this->fields['childs_blocks'], true);
        } else {
            $params['childs_blocks'] = [];
        }

        //Hook to get values saves from plugin
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p = [];
                $p["plugin_metademands_fields_id"] = $item->getID();
                $p["plugin_metademands_metademands_id"] = $item->fields["plugin_metademands_metademands_id"];
                $p["nbOpt"] = $this->fields['id'];

                $new_params = self::getPluginParamsOptions($plug, $p);

                if (Plugin::isPluginActive($plug)
                    && is_array($new_params)) {

                    $params = array_merge($params, $new_params);
                }
            }
        }

        echo Html::hidden('plugin_metademands_fields_id', ['value' => $item->getID()]);


        switch ($params['type']) {
            case 'yesno':
                $data[1] = __('No');
                $data[2] = __('Yes');

                // Value to check
                echo "<tr>";
                echo "<td>";
                echo __('Value to check', 'metademands');
                echo "</td>";
                echo "<td>";
                self::showValueToCheck($this, $params);
                echo "</td>";

                echo self::showLinkHtml($item->getID(), $params, 1, 1, 1);

                break;

            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'radio':
            case 'checkbox':
            case 'dropdown_multiple':
                echo "<tr>";
                echo "<td>";
                echo __('Value to check', 'metademands');
                echo " ( " . Dropdown::EMPTY_VALUE . " = " . __('Not null value', 'metademands') . ")";
                echo "</td>";
                echo "<td>";
                self::showValueToCheck($this, $params);
                echo "</td>";

                echo self::showLinkHtml($item->getID(), $params, 1, 1, 1);

                break;
            case 'parent_field':
                echo "<tr>";
                echo "<td>";
                echo __('Field');
                echo "</td>";
                echo "<td>";
                self::showValueToCheck($this, $params);

                echo "</td></tr>";
                break;
            case 'text':
            case 'textarea':
                echo "<tr>";
                echo "<td>";
                echo __('If field empty', 'metademands');
                echo "</td>";
                echo "<td>";
                self::showValueToCheck($this, $params);
                echo "</td>";

                echo self::showLinkHtml($item->getID(), $params, 1, 0, 1);
                break;

            default:
                break;
        }

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Load data options saves from plugins
     *
     * @param $plug
     */
    public static function getPluginParamsOptions($plug, $params)
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
                if ($item && is_callable([$item, 'getParamsOptions'])) {
                    return $item->getParamsOptions($params);
                }
            }
        }
    }

    /**
     * @param $item
     * @param $params
     * @return void
     * @throws GlpitestSQLError
     */
    public static function showValueToCheck($item, $params)
    {

        $field = new self();
        $existing_options = $field->find(["plugin_metademands_fields_id" => $params["plugin_metademands_fields_id"]]);
        $already_used = [];
        if ($item->getID() == 0) {
            foreach ($existing_options as $existing_option) {
                $already_used[$existing_option["check_value"]] = $existing_option["check_value"];
            }
        }

        switch ($params['type']) {
            case 'textarea':
            case 'text':
            case 'yesno':
                $options[1] = __('No');
                $options[2] = __('Yes');
                Dropdown::showFromArray("check_value", $options, ['value' => $params['check_value'], 'used' => $already_used]);
                break;
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'dropdown_multiple':

                switch ($params["item"]) {
                    case 'ITILCategory_Metademands':
                        $metademand = new PluginMetademandsMetademand();
                        $metademand->getFromDB($params["plugin_metademands_metademands_id"]);
                        $values = json_decode($metademand->fields['itilcategories_id']);

                        $name = "check_value";
                        $opt = ['name' => $name,
                            'right' => 'all',
                            'value' => $params['check_value'],
                            'condition' => ["id" => $values],
                            'display' => true,
                            'used' => $already_used];
                        ITILCategory::dropdown($opt);

                        break;
                    case 'User':
                        $userrand = mt_rand();
                        $name = "check_value";
                        User::dropdown(['name' => $name,
                            'entity' => $_SESSION['glpiactiveentities'],
                            'right' => 'all',
                            'rand' => $userrand,
                            'value' => $params['check_value'],
                            'display' => true,
                            'used' => $already_used
                        ]);
                        break;
                    case 'Group':
                        $name = "check_value";
                        $cond = [];
                        if (!empty($params['custom_values'])) {
                            $options = $params['custom_values'];
                            foreach ($options as $type_group => $values) {
                                $cond[$type_group] = $values;
                            }
                        }
                        Group::dropdown(['name' => $name,
                            'entity' => $_SESSION['glpiactiveentities'],
                            'value' => $params['check_value'],
                            //                                            'readonly'  => true,
                            'condition' => $cond,
                            'display' => true,
                            'used' => $already_used
                        ]);
                        break;
                    default:
                        $dbu = new DbUtils();
                        if ($item = $dbu->getItemForItemtype($params["item"])
                            && $params['type'] != "dropdown_multiple") {
                            //               if ($params['value'] == 'group') {
                            //                  $name = "check_value";// TODO : HS POUR LES GROUPES CAR rajout un RAND dans le dropdownname
                            //               } else {
                            $name = "check_value";
                            //               }
                            $params['item']::Dropdown(["name" => $name,
                                "value" => $params['check_value'], 'used' => $already_used]);
                        } else {
                            if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                                $elements[0] = Dropdown::EMPTY_VALUE;
                                if (is_array(json_decode($params['custom_values'], true))) {
                                    $elements += json_decode($params['custom_values'], true);
                                }
                                foreach ($elements as $key => $val) {
                                    if ($key != 0) {
                                        $elements[$key] = $params["item"]::getFriendlyNameById($key);
                                    }
                                }
                            } else {
                                $elements[0] = Dropdown::EMPTY_VALUE;
                                if (is_array(json_decode($params['custom_values'], true))) {
                                    $elements += json_decode($params['custom_values'], true);
                                }
                                foreach ($elements as $key => $val) {
                                    $elements[$key] = urldecode($val);
                                }
                            }
                            Dropdown::showFromArray(
                                "check_value",
                                $elements,
                                ['value' => $params['check_value'], 'used' => $already_used]
                            );
                        }
                        break;
                }
                break;
            case 'checkbox':
            case 'radio':
                $elements[-1] = Dropdown::EMPTY_VALUE;
                if (is_array(json_decode($params['custom_values'], true))) {
                    $elements += json_decode($params['custom_values'], true);
                }
                foreach ($elements as $key => $val) {
                    $elements[$key] = urldecode($val);
                }
                Dropdown::showFromArray(
                    "check_value",
                    $elements,
                    ['value' => $params['check_value'], 'used' => $already_used]
                );
                break;

            case 'parent_field':

                //list of fields
                $fields = [];
                $metademand_parent = new PluginMetademandsMetademand();
                // list of parents
                $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask($params["plugin_metademands_metademands_id"]);
                $fieldclass = new PluginMetademandsField();
                foreach ($metademands_parent as $parent_id) {
                    if ($metademand_parent->getFromDB($parent_id)) {
                        $name_metademand = $metademand_parent->getName();

                        $condition = ['plugin_metademands_metademands_id' => $parent_id,
                            ['NOT' => ['type' => ['parent_field', 'upload']]]];
                        $datas_fields = $fieldclass->find($condition, ['rank', 'order']);
                        //formatting the name to display (Name of metademand - Father's Field Label - type)
                        foreach ($datas_fields as $data_field) {
                            $fields[$data_field['id']] = $name_metademand . " - " . $data_field['name'] . " - " . PluginMetademandsField::getFieldTypesName($data_field['type']);
                        }
                    }
                }
                Dropdown::showFromArray('parent_field_id', $fields);
                echo Html::hidden('check_value', ['value' => 0]);
                break;
        }
    }

    public static function getValueToCheck($params)
    {

        switch ($params['type']) {
            case 'textarea':
            case 'text':
            case 'yesno':
                $options[1] = __('No');
                $options[2] = __('Yes');
                echo $options[$params['check_value']] ?? "";
                break;
            case 'dropdown':
            case 'dropdown_object':
            case 'dropdown_meta':
            case 'dropdown_multiple':
                switch ($params["item"]) {
                    case 'ITILCategory_Metademands':
                        echo Dropdown::getDropdownName('glpi_itilcategories', $params['check_value']);
                        break;
                    case 'User':
                        echo getUserName($params['check_value']);
                        break;
                    case 'Group':
                        echo Dropdown::getDropdownName('glpi_groups', $params['check_value']);
                        break;
                    default:
                        $dbu = new DbUtils();
                        if ($item = $dbu->getItemForItemtype($params["item"])
                            && $params['type'] != "dropdown_multiple") {
                            echo Dropdown::getDropdownName(getTableForItemType($params["item"]), $params['check_value']);
                        } else {
                            if ($params["item"] != "other" && $params["type"] == "dropdown_multiple") {
                                $elements = [];
                                if (is_array(json_decode($params['custom_values'], true))) {
                                    $elements += json_decode($params['custom_values'], true);
                                }
                                foreach ($elements as $key => $val) {
                                    if ($key != 0) {
                                        $elements[$key] = $params["item"]::getFriendlyNameById($key);
                                    }
                                }
                                echo $elements[$params['check_value']];
                            } else {
                                $elements = [];
                                if (is_array(json_decode($params['custom_values'], true))) {
                                    $elements += json_decode($params['custom_values'], true);
                                }
                                foreach ($elements as $key => $val) {
                                    $elements[$key] = urldecode($val);
                                }
                                echo $elements[$params['check_value']] ?? "";
                            }
                        }
                        break;
                }
                break;
            case 'checkbox':
            case 'radio':
                $elements = [];
                if (is_array(json_decode($params['custom_values'], true))) {
                    $elements += json_decode($params['custom_values'], true);
                }
                foreach ($elements as $key => $val) {
                    $elements[$key] = urldecode($val);
                }
                echo $elements[$params['check_value']] ?? 0;

                break;

            case 'parent_field':

                $field = new PluginMetademandsField();
                if ($field->getFromDB($params['parent_field_id'])) {
                    if (empty(trim($field->fields['name']))) {
                        echo "ID - " . $params['parent_field_id'];
                    } else {
                        echo $field->fields['name'];
                    }
                }

                break;
        }
    }

    /**
     * @param     $metademands_id
     * @param     $params
     * @param     $opt
     * @param int $task
     * @param int $field
     * @param int $hidden
     *
     * @return string
     * @throws \GlpitestSQLError
     */

    public static function showLinkHtml($id, $params, $task = 1, $field = 1, $hidden = 0)
    {
        global $PLUGIN_HOOKS, $CFG_GLPI;

        $field_id = $params['plugin_metademands_fields_id'];
        $metademands_id = $params["plugin_metademands_metademands_id"];

        $field_class = new PluginMetademandsField();
        $field_class->getFromDB($field_id);

        // Show task link
        if ($task) {
            echo '<tr><td>';
            echo __('Link a task to the field', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the task is created', 'metademands') . '</span>';
            echo '</td><td>';
            PluginMetademandsTask::showAllTasksDropdown($metademands_id, $params['plugin_metademands_tasks_id']);
            echo "</td></tr>";
        }

        // Show field link
        if ($field) {
            echo "<tr><td>";
            echo __('Link a field to the field', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes mandatory', 'metademands') . '</span>';
            echo "</td>";
            echo "<td>";

            $fields = new PluginMetademandsField();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
            unset($fields_data[$id]);
            Toolbox::logInfo($id);
            Toolbox::logInfo($fields_data);
            $data = [Dropdown::EMPTY_VALUE];
            foreach ($fields_data as $id => $value) {
                if ($value['item'] != "ITILCategory_Metademands"
                    && $value['item'] != "informations") {
                    $data[$id] = $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));
                }
            }

            Dropdown::showFromArray('fields_link', $data, ['value' => $params['fields_link']]);
            echo "</td></tr>";
        }
        if ($hidden) {
            echo "<tr><td>";
            echo __('Link a hidden field', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the field becomes visible', 'metademands') . '</span>';
            echo "</td>";
            echo "<td>";

            $fields = new PluginMetademandsField();
            $fields_data = $fields->find(['plugin_metademands_metademands_id' => $metademands_id]);
            unset($fields_data[$id]);
            $data = [Dropdown::EMPTY_VALUE];
            foreach ($fields_data as $id => $value) {
                if ($value['item'] != "ITILCategory_Metademands") {
                    $data[$id] = $value['rank'] . " - " . urldecode(html_entity_decode($value['name']));
                }
            }
            Dropdown::showFromArray('hidden_link', $data, ['value' => $params['hidden_link']]);
            echo "</td></tr>";

            echo "<tr><td>";
            echo __('Link a hidden block', 'metademands');
            echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the block becomes visible', 'metademands') . '</span>';
            echo "</td>";
            echo "<td>";

            if (empty($params['hidden_block'])) {
                $params['hidden_block'] = 0;
            }
            Dropdown::showNumber('hidden_block', ['value' => $params['hidden_block'],
                'used' => [$field_class->getField('rank')],
                'min' => 1,
                'max' => PluginMetademandsField::MAX_FIELDS,
                'toadd' => [0 => Dropdown::EMPTY_VALUE]]);

            echo "</td></tr>";


            if ($field_class->getField("type") == "checkbox"
                || $field_class->getField("type") == "radio"
                || $field_class->getField("type") == "text"
                || $field_class->getField("type") == "textarea"
                || $field_class->getField("type") == "group"
                || $field_class->getField("type") == "dropdown"
                || $field_class->getField("type") == "dropdown_object"
                || $field_class->getField("type") == "dropdown_meta"
                || $field_class->getField("type") == "yesno") {
                echo "<tr><td>";
                echo __('Childs blocks', 'metademands');
                echo '</br><span class="metademands_wizard_comments">' . __('If child blocks exist, these blocks are hidden when you deselect the option configured', 'metademands') . '</span>';
                echo "</td>";
                echo "<td>";
                echo self::showChildsBlocksDropdown($metademands_id, $params['childs_blocks']);
                echo "</td></tr>";
            }
            if ($field_class->getField("type") == "checkbox") {
                echo "<tr><td>";
                echo __('Link a validation', 'metademands');
                echo '</br><span class="metademands_wizard_comments">' . __('If the value selected equals the value to check, the validation is sent to the user', 'metademands') . '</span>';
                echo "</td>";
                echo "<td>";
                $right = '';
                $metademand = new PluginMetademandsMetademand();
                $metademand->getFromDB($metademands_id);
                if ($metademand->getField('type') == Ticket::INCIDENT_TYPE) {
                    $right = 'validate_incident';
                } elseif ($metademand->getField('type') == Ticket::DEMAND_TYPE) {
                    $right = 'validate_request';
                }
                User::dropdown(['name' => 'users_id_validate',
                    'value' => $params['users_id_validate'],
                    'right' => $right]);
                echo "</td></tr>";
            }
            if ($field_class->getField("type") == "dropdown_multiple") {
                echo "<tr><td>";
                echo __('Bind to the value of this checkbox', 'metademands');
                echo '</br><span class="metademands_wizard_comments">' . __('If the selected value is equal to the value to check, the checkbox value is set', 'metademands') . '</span>';
                echo "</td>";
                echo "<td>";
                $fields = new PluginMetademandsField();
                $checkboxes = $fields->find(['plugin_metademands_metademands_id' => $metademands_id,
                    'type' => 'checkbox']);
                $dropdown_values = [];
                foreach ($checkboxes as $checkbox) {
                    $dropdown_values[$checkbox['id']] = $checkbox['name'];
                }
                $rand = mt_rand();
                $randcheck = Dropdown::showFromArray('checkbox_id', $dropdown_values, ['display_emptychoice' => true,
                    'value' => $params['checkbox_id']]);
                $paramsajax = ['checkbox_id_val' => '__VALUE__',
                    'metademands_id' => $metademands_id];

                Ajax::updateItemOnSelectEvent('dropdown_checkbox_id' . $randcheck,
                    "checkbox_value",
                    $CFG_GLPI["root_doc"] . PLUGIN_METADEMANDS_DIR_NOFULL . "/ajax/checkboxValues.php",
                    $paramsajax);

                $arrayValues = [];
                $arrayValues[0] = Dropdown::EMPTY_VALUE;
                if (!empty($params['checkbox_id'])) {
                    $fields->getFromDB($params['checkbox_id']);
                    $arrayValues = PluginMetademandsField::_unserialize($fields->fields['custom_values']);
                }
                echo "<span id='checkbox_value'>\n";
                $elements = $arrayValues ?? [];
                Dropdown::showFromArray('checkbox_value', $elements, [
                    'display_emptychoice' => false,
                    'value' => $params['checkbox_value']]);
                echo "</span>\n";

                echo "</td></tr>";
            }
        }

        //Hook to print new options from plugins
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $p = $params;
                $p["plugin_metademands_fields_id"] = $field_id;
                $p["plugin_metademands_metademands_id"] = $metademands_id;
                $p["hidden"] = $hidden;


                $new_res = self::getPluginShowOptions($plug, $p);
                if (Plugin::isPluginActive($plug)
                    && !empty($new_res)) {
                    echo $new_res;
                }
            }
        }
    }


    /**
     * show options fields from plugins
     *
     * @param $plug
     */
    public static function getPluginShowOptions($plug, $params)
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
                if ($item && is_callable([$item, 'showOptions'])) {
                    return $item->showOptions($params);
                }
            }
        }
    }


    /**
     * @param      $metademands_id
     * @param      $selected_value
     * @param bool $display
     * @param      $idF
     *
     * @return int|string
     */
    public static function showChildsBlocksDropdown($metademands_id, $selected_values)
    {
        $fields = new PluginMetademandsField();
        $fields = $fields->find(["plugin_metademands_metademands_id" => $metademands_id]);
        $blocks = [];
        foreach ($fields as $f) {
            if (!isset($blocks[$f['rank']])) {
                $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
            }
        }
        ksort($blocks);

//        Toolbox::logInfo($selected_values);
        $values = [];
        foreach ($selected_values as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $selected_value) {
                    $values[] = $selected_value;
                }
            } else {
                $values[] = $v;
            }
        }
//        Toolbox::logInfo($values);
        $name = "childs_blocks[]";
        Dropdown::showFromArray(
            $name,
            $blocks,
            [
                'values' => $values,
                'width' => '100%',
                'multiple' => true,
                'entity' => $_SESSION['glpiactiveentities']]
        );
    }


    public static function fieldsLinkScript($data)
    {

        if (isset($data['options'])) {
            $check_values = $data['options'];

            if (is_array($check_values)) {
                if (count($check_values) > 0) {
                    foreach ($check_values as $idc => $check_value) {

                        if (!empty($data['options'][$idc]['fields_link'])) {
                            $script = "";
                            $fields_link = $data['options'][$idc]['fields_link'];

                            $fields_link2 = $fields_link;
                            $rand = mt_rand();
//                        if (isset($check_value[$key])) {
                            $script .= "var metademandWizard$rand = $(document).metademandWizard();";
                            $script .= "metademandWizard$rand.metademand_setMandatoryField(
                                        'metademands_wizard_red" . $fields_link . "',
                                        'field[" . $data['id'] . "]',[";
                            if ($check_value > 0
                                || (($data['type'] == 'checkbox' || $data['type'] == 'radio')
                                    && $idc == 0)) {
                                $script .= $idc;
                            }
//TODO used ?
//                            foreach ($fields_link2 as $key2 => $fields2) {
//                                if ($key != $key2) {
//                                    if ($fields_link[$key] == $fields_link[$key2]) {
//                                        $script .= "," . $check_value[$key2];
//                                    }
//                                }
//                            }


                            $script .= "], '" . $data['item'] . "');";
//                        }

                            echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
//                            Toolbox::logInfo($script);
                        }
                    }
                }
            }
        }
    }

    public static function fieldsHiddenScript($data)
    {

        if (isset($data['options'])) {
            $check_values = $data['options'];

            if (is_array($check_values)) {
                if (count($check_values) > 0) {

                    switch ($data['type']) {
                        case 'yesno':
                            $script2 = "";
                            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $data['options'][$idc]['hidden_link'];
                                $val = Toolbox::addslashes_deep($idc);
                                $script .= "
                                               if($(this).val() == $val){
                                                 $('[id-field =\"field" . $hidden_link . "\"]').show();

                                               } else {
                                                $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                                " . PluginMetademandsField::getJStorersetFieldsByField($hidden_link) . "
                                               }
                                                ";
                                if ($idc == $data["custom_values"]) {
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != $idc) {
                                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    }
                                } else {
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                    }
                                }

                                $childs_blocks = [];
                                if (isset($data['options'])) {
                                    $opts = $data['options'];
                                    foreach ($opts as $optid => $opt) {
                                        if ($optid == $idc) {
                                            if (!empty($opt['childs_blocks'])) {
                                                $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                                            }
                                        }
                                    }
                                }

                                if (is_array($childs_blocks[0])) {
                                    if (count($childs_blocks[0]) > 0) {
                                        $script .= "
                                            if($(this).val() != $idc){";
                                        foreach ($childs_blocks[0] as $k => $v) {
                                            $script .= PluginMetademandsField::getJStorersetFields($v);
                                            $script .= "$('div[bloc-id=\"bloc$v\"]').hide();";
                                        }
                                        $script .= " }else{
                                             $('div[bloc-id=\"bloc$v\"]').show();
                                            }";
//                                            $script .= "};";

                                        foreach ($childs_blocks[0] as $k => $v) {
                                            if ($v > 0) {
                                                $hiddenblocks[] = $v;
                                                $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['hidden_blocks'] = $hiddenblocks;
                                            }
                                        }
                                    }
                                }
                            }
                            $script .= "});";

                            //Initialize id default value
                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];
                                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                    foreach ($default_values as $k => $v) {
                                        if ($v == 1) {
                                            if ($idc == $k) {
                                                $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                }
                            }
                            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');

                            break;
                        case 'dropdown_multiple':
                            if ($data["display_type"] == PluginMetademandsField::CLASSIC_DISPLAY) {
                                $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                                $script2 = "";

                                $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
                                $script .= "var tohide = {};";
//
                                foreach ($check_values as $idc => $check_value) {
                                    $hidden_link = $check_value['hidden_link'];
                                    $script .= "
                                                 if($hidden_link in tohide){
    
                                                 }else{
                                                    tohide[$hidden_link] = true;
                                                 }
                                                 ";
                                    //                                        }
                                    $script .= "
                                                $.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {
                                                ";
                                    //                                        foreach ($idc as $key => $fields) {
                                    //                                            if ($fields != 0) {
                                    if ($data["item"] == "other") {
                                        $val = Toolbox::addslashes_deep($custom_value[$idc]);
                                        $script .= "
                                                          if($(value).attr('title') == '$val'){
                                                             tohide[" . $hidden_link . "] = false;
                                                          }
                                                       ";
                                    } else {
                                        $script .= "
                                                          if($(value).attr('title') == '" . $data["item"]::getFriendlyNameById($hidden_link) . "'){
                                                             tohide[" . $hidden_link . "] = false;
                                                          }
                                                       ";
                                    }

                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                    }
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                        foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                            if ($fieldSession == $idc) {
                                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                    $script .= "});console.log('fieldshidden-dropdown_multiple');";
                                }


                                //dropdown_multiple
                                $script .= "$.each( tohide, function( key, value ) {
                                                if(value == true){
                                                  $('[id-field =\"field'+key+'\"]').hide();
                                                  $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {

                                                 switch(this.type) {
                                                        case 'password':
                                                        case 'text':
                                                        case 'textarea':
                                                        case 'file':
                                                        case 'date':
                                                        case 'number':
                                                        case 'tel':
                                                        case 'email':
                                                            jQuery(this).val('');
                                                            break;
                                                        case 'select-one':
                                                        case 'select-multiple':
                                                            jQuery(this).val('0').trigger('change');
                                                            jQuery(this).val('0');
                                                            break;
                                                        case 'checkbox':
                                                        case 'radio':
                                                             if(this.checked == true) {
                                                                    this.click();
                                                                    this.checked = false;
                                                                    break;
                                                                }
                                                    }
                                                    regex = /multiselectfield.*_to/g;
                                                    totest = this.id;
                                                    found = totest.match(regex);
                                                    if(found !== null) {
                                                      regex = /multiselectfield[0-9]*/;
                                                       found = totest.match(regex);
                                                       $('#'+found[0]+'_leftAll').click();
                                                    }
                                                });
                                                  $('[name =\"field['+key+']\"]').removeAttr('required');
                                                } else {
                                                   $('[id-field =\"field'+key+'\"]').show();
                                                }
                                             });";
                                $script .= "});";
//                                    }

                                //Initialize id default value
                                foreach ($check_values as $idc => $check_value) {
                                    $hidden_link = $check_value['hidden_link'];
                                    if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                        $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                        foreach ($default_values as $k => $v) {
                                            if ($v == 1) {
                                                if ($idc == $k) {
//                                                $idc = $idc[$k];
//                                                $idv = $hidden_block[$idc];
                                                    $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                                }
                                            }
                                        }
                                    }
                                }
                                echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                            } else {
                                $script = "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";
                                $script .= "var tohide = {};";
                                $script2 = "";
                                foreach ($check_values as $idc => $check_value) {
                                    $hidden_link = $check_value['hidden_link'];

                                    $script .= "
                                              if($hidden_link in tohide){
    
                                              }else{
                                                 tohide[$hidden_link] = true;
                                              }
                                              ";

                                    $script .= "
                                              $.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {
                                              ";
                                    $script .= "
                                                    if($(value).attr('value') == '$idc'){
                                                       tohide[" . $hidden_link . "] = false;
                                                    }
                                                 ";
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                    }
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                        foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                            if ($fieldSession == $idc) {
                                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                }

                                $script .= "});console.log('fieldshidden-dropdown_multiple-2');";
                                $script .= "$.each( tohide, function( key, value ) {
                                                    if(value == true){
                                                      $('[id-field =\"field'+key+'\"]').hide();
                                                      $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {

                                                     switch(this.type) {
                                                            case 'password':
                                                            case 'text':
                                                            case 'textarea':
                                                            case 'file':
                                                            case 'date':
                                                            case 'number':
                                                            case 'tel':
                                                            case 'email':
                                                                jQuery(this).val('');
                                                                break;
                                                            case 'select-one':
                                                            case 'select-multiple':
                                                                jQuery(this).val('0').trigger('change');
                                                                jQuery(this).val('0');
                                                                break;
                                                            case 'checkbox':
                                                            case 'radio':
                                                                 if(this.checked == true) {
                                                                        this.click();
                                                                        this.checked = false;
                                                                        break;
                                                                    }
                                                        }
                                                        regex = /multiselectfield.*_to/g;
                                                        totest = this.id;
                                                        found = totest.match(regex);
                                                        if(found !== null) {
                                                          regex = /multiselectfield[0-9]*/;
                                                           found = totest.match(regex);
                                                           $('#'+found[0]+'_leftAll').click();
                                                        }
                                                    });
                                                      $('[name =\"field['+key+']\"]').removeAttr('required');
                                                    }else{
                                                       $('[id-field =\"field'+key+'\"]').show();
                                                    }
                                                 });";
                                $script .= "});";
//                                    }
                                foreach ($check_values as $idc => $check_value) {
                                    $hidden_link = $check_value['hidden_link'];
                                    //Initialize id default value
                                    if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                        $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                        foreach ($default_values as $k => $v) {
                                            if ($v == 1) {
                                                if ($idc == $k) {
//                                                $idc = $idc[$k];
//                                                $idv = $hidden_block[$idc];
                                                    $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                                }
                                            }
                                        }
                                    }
                                }
                                echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                            }

                            break;
                        case 'checkbox':
                            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                            $script2 = "";
                            $script .= "var tohide = {};";
                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];
                                $script .= " if (this.checked){ ";
                                //                                        foreach ($hidden_link as $key => $fields) {
                                    $script .= " if($(this).val() == $idc || $idc == -1){
                                                     if($hidden_link in tohide){
        
                                                     }else{
                                                        tohide[$hidden_link] = true;
                                                     }
                                                     tohide[$hidden_link] = false;
                                                  }";

                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                        foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                            if ($fieldSession == $idc) {
                                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }

                                    //checkbox
                                    $script .= "$.each( tohide, function( key, value ) {
                                                        if(value == true){
                                                           $('[id-field =\"field'+key+'\"]').hide();
                                                           $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {
        
                                                         switch(this.type) {
                                                                case 'password':
                                                                case 'text':
                                                                case 'textarea':
                                                                case 'file':
                                                                case 'date':
                                                                case 'number':
                                                                case 'tel':
                                                                case 'email':
                                                                    jQuery(this).val('');
                                                                    break;
                                                                case 'select-one':
                                                                case 'select-multiple':
                                                                    jQuery(this).val('0').trigger('change');
                                                                    jQuery(this).val('0');
                                                                    break;
                                                                case 'checkbox':
                                                                case 'radio':
                                                                     if(this.checked == true) {
                                                                            this.click();
                                                                            this.checked = false;
                                                                            break;
                                                                        }
                                                            }
                                                            regex = /multiselectfield.*_to/g;
                                                            totest = this.id;
                                                            found = totest.match(regex);
                                                            if(found !== null) {
                                                              regex = /multiselectfield[0-9]*/;
                                                               found = totest.match(regex);
                                                               $('#'+found[0]+'_leftAll').click();
                                                            }
                                                        });
                                                           $('[name =\"field['+key+']\"]').removeAttr('required');
                                                        }else{
                                                           $('[id-field =\"field'+key+'\"]').show();
                                                        }
                                                     });";
                                $script .= "} else {";
                                //                                        foreach ($hidden_link as $key => $fields) {
                                            $script .= "if($(this).val() == $idc){
                                                                if($hidden_link in tohide){
                
                                                                }else{
                                                                   tohide[$hidden_link] = true;
                                                                }
                                                                $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                                                                $script .= "if($(value).val() == $idc
                                                                                          || $idc == -1 ){
                                                                                   tohide[$hidden_link] = false;
                                                                                }";
                                                                 $script .= "});
                                                        }";



                                            $script .= "$.each( tohide, function( key, value ) {
                                                            if(value == true){
                                                               $('[id-field =\"field'+key+'\"]').hide();
                                                               $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {
                
                                                             switch(this.type) {
                                                                    case 'password':
                                                                    case 'text':
                                                                    case 'textarea':
                                                                    case 'file':
                                                                    case 'date':
                                                                    case 'number':
                                                                    case 'tel':
                                                                    case 'email':
                                                                        jQuery(this).val('');
                                                                        break;
                                                                    case 'select-one':
                                                                    case 'select-multiple':
                                                                        jQuery(this).val('0').trigger('change');
                                                                        jQuery(this).val('0');
                                                                        break;
                                                                    case 'checkbox':
                                                                    case 'radio':
                                                                         if(this.checked == true) {
                                                                                this.click();
                                                                                this.checked = false;
                                                                                break;
                                                                            }
                                                                }
                                                                regex = /multiselectfield.*_to/g;
                                                                totest = this.id;
                                                                found = totest.match(regex);
                                                                if(found !== null) {
                                                                  regex = /multiselectfield[0-9]*/;
                                                                   found = totest.match(regex);
                                                                   $('#'+found[0]+'_leftAll').click();
                                                                }
                                                            });
                                                               $('[name =\"field['+key+']\"]').removeAttr('required');
                                                            }else{
                                                               $('[id-field =\"field'+key+'\"]').show();
                                                            }
                                                         });";
                                 $script .= "}";
//                                    }
                            }
                            $script .= "});console.log('fieldshidden-checkbox');";

                            foreach ($check_values as $idc => $check_value) {

                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                    && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                    foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                        if ($fieldSession == $idc) {
                                            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                        }
                                    }
                                }
                                $hidden_link = $check_value['hidden_link'];
                                //Initialize id default value
                                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                    foreach ($default_values as $k => $v) {
                                        if ($v == 1) {
                                            if ($idc == $k) {
                                                $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                }
                            }
                            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                            break;

                        case 'text':
                        case 'textarea':
                            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                            $script2 = "";

                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];

                                if (isset($idc) && $idc == 1) {
                                    $script .= "
                                                      if($(this).val().trim().length < 1){
                                                         $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                                          " . PluginMetademandsField::getJStorersetFieldsByField($hidden_link) . "
                                                      }else{
                                                         $('[id-field =\"field" . $hidden_link . "\"]').show();
                                                      }
                                                    ";
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != "") {
                                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                    }
                                } else {
                                    $script .= "
                                                      if($(this).val().trim().length < 1){
                                                            $('[id-field =\"field" . $hidden_link . "\"]').show();
                                                         }else{
                                                            $('[id-field =\"field" . $hidden_link . "\"]').hide();
                                                             " . PluginMetademandsField::getJStorersetFieldsByField($hidden_link) . "
                                                         }
                                                    ";
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                    if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                        && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == "") {
                                        $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                    }
                                }
                                //                                    }
                                //                                }
                                $script .= "});console.log('fieldshidden-text');";
                            }
                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];
                                //Initialize id default value
                                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                    foreach ($default_values as $k => $v) {
                                        if ($v == 1) {
                                            if ($idc == $k) {
                                                $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                }
                            }
                            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                            break;

                        case 'radio':
                            $script2 = "";
                            $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                            $script .= "var tohide = {};";

                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];
                                $script .= "
                                          if ($hidden_link in tohide) {

                                          } else {
                                             tohide[$hidden_link] = true;
                                          }
                                          if (parseInt($(this).val()) == $idc || $idc == -1) {
                                             tohide[$hidden_link] = false;
                                          }";
                            }
                            //radio
                            $script .= "
                                    $.each( tohide, function( key, value ) {
                                            if(value == true){
                                                 $('[id-field =\"field'+key+'\"]').hide();
                                                 $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {
                                                      switch(this.type) {
                                                            case 'password':
                                                            case 'text':
                                                            case 'textarea':
                                                            case 'file':
                                                            case 'date':
                                                            case 'number':
                                                            case 'tel':
                                                            case 'email':
                                                                jQuery(this).val('');
                                                                break;
                                                            case 'select-one':
                                                            case 'select-multiple':
                                                                jQuery(this).val('0').trigger('change');
                                                                jQuery(this).val('0');
                                                                break;
                                                            case 'checkbox':
                                                            case 'radio':
                                                                 if(this.checked == true) {
                                                                        this.click();
                                                                        this.checked = false;
                                                                        break;
                                                                    }
                                                            }
                                                            regex = /multiselectfield.*_to/g;
                                                            totest = this.id;
                                                            found = totest.match(regex);
                                                            if(found !== null) {
                                                              regex = /multiselectfield[0-9]*/;
                                                               found = totest.match(regex);
                                                               $('#'+found[0]+'_leftAll').click();
                                                      }
                                                 });
                                                 $('[name =\"field['+key+']\"]').removeAttr('required');
                                            } else {
                                               $('[id-field =\"field'+key+'\"]').show();
                                            }
                                     });";

                            $script .= "});console.log('fieldshidden-radio');";

                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];

                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                    && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc || $idc == -1)) {
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                }

                                //Initialize id default value
                                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                    foreach ($default_values as $k => $v) {
                                        if ($v == 1) {
                                            if ($idc == $k) {
                                                $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                }

                            }
                            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                            break;

                        case 'group':
                        case 'dropdown':
                        case 'dropdown_object':
                        case 'dropdown_meta':
                            if ($data['item'] == "ITILCategory_Metademands") {
                                $name = "field_plugin_servicecatalog_itilcategories_id";
                            } else {
                                $name = "field[" . $data["id"] . "]";
                            }
                            $script = "$('[name=\"$name\"]').change(function() {";


                            $script2 = "";
                            $script .= "var tohide = {};";
                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];
                                $script .= "
                                                 if($hidden_link in tohide){
    
                                                 }else{
                                                    tohide[$hidden_link] = true;
                                                 }
                                                 if($(this).val() != 0 && ($(this).val() == $idc || $idc == 0 ) ){
                                                    tohide[$hidden_link] = false;
                                                 }";

                                $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').hide();";
                                if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                    && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
                                        || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
                                    $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                } else {
                                    if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                                        if (Session::getLoginUserID() == $idc) {
                                            $script2 .= "$('[id-field =\"field" . $hidden_link . "\"]').show();";
                                        }
                                    }
                                }
                            }
                            $script .= "$.each( tohide, function( key, value ) {
                                            if(value == true){
                                               $('[id-field =\"field'+key+'\"]').hide();
                                               $('div[id-field =\"field'+key+'\"]').find(':input').each(function() {

                                             switch(this.type) {
                                                    case 'password':
                                                    case 'text':
                                                    case 'textarea':
                                                    case 'file':
                                                    case 'date':
                                                    case 'number':
                                                    case 'tel':
                                                    case 'email':
                                                        jQuery(this).val('');
                                                        break;
                                                    case 'select-one':
                                                    case 'select-multiple':
                                                        jQuery(this).val('0').trigger('change');
                                                        jQuery(this).val('0');
                                                        break;
                                                    case 'checkbox':
                                                    case 'radio':
                                                         if(this.checked == true) {
                                                                this.click();
                                                                this.checked = false;
                                                                break;
                                                            }
                                                }
                                                regex = /multiselectfield.*_to/g;
                                                totest = this.id;
                                                found = totest.match(regex);
                                                if(found !== null) {
                                                  regex = /multiselectfield[0-9]*/;
                                                   found = totest.match(regex);
                                                   $('#'+found[0]+'_leftAll').click();
                                                }
                                            });
                                               $('[name =\"field['+key+']\"]').removeAttr('required');
                                            }else{
                                               $('[id-field =\"field'+key+'\"]').show();
                                            }
                                         });";
//                                    }
//                                }
                            $script .= "});";
                            //Initialize id default value

                            foreach ($check_values as $idc => $check_value) {
                                $hidden_link = $check_value['hidden_link'];
                                if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                    $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                    foreach ($default_values as $k => $v) {
                                        if ($v == 1) {
                                            if ($idc == $k) {
//                                                $idc = $idc[$k];
//                                                $idv = $hidden_block[$idc];
                                                $script .= " $('[id-field =\"field" . $hidden_link . "\"]').show();";
                                            }
                                        }
                                    }
                                }
                            }
                            echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                            break;
                    }
                }
            }
        }
    }

    public static function blocksHiddenScript($data)
    {

        if (isset($data['options'])) {
            $check_values = $data['options'];
//            if (is_array($check_values)) {
//                if (count($check_values) > 0) {
//                    foreach ($check_values as $idc => $check_value) {
//
//                        if (!empty($data['options'][$idc]['hidden_block'])) {
                            switch ($data['type']) {
                                case 'yesno':
                                    $script2 = "";
                                    $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $data['options'][$idc]['hidden_block'];

                                        $script .= "
                                            if($(this).val() == $idc){
                                              $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                              
                                            }else{
                                             $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                        $script .= PluginMetademandsField::getJStorersetFields($hidden_block);
                                        $script .= "}
                                             ";
                                        if ($idc == $data["custom_values"]) {
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                                && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != $idc) {
                                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            }
                                        } else {
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                                && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                            }
                                        }
                                    }
                                    $script .= "});";
                                    $script .= "fixButtonIndicator();console.log('hidden-yesno-1');";
                                    //Initialize id default value
                                    foreach ($check_values as $idc => $check_value) {

                                        $hidden_block = $check_value['hidden_block'];
                                        //include child blocks
                                        if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                                            $childs_blocks = json_decode($check_value['childs_blocks'], true);
                                            if (is_array($childs_blocks)) {
                                                foreach ($childs_blocks as $childs_block) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();";
                                                    $hiddenblocks[] = $childs_block;
                                                    $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                                }
                                            }
                                        }
                                        if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                            $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                            foreach ($default_values as $k => $v) {
                                                if ($v == 1) {
                                                    if ($idc == $k) {
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');


                                    //                  case 'PluginResourcesResource':
                                    //                  case 'PluginMetademandsITILApplication':
                                    //                  case 'PluginMetademandsITILEnvironment':

                                    break;
                                case 'dropdown_multiple':
                                    $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                                    $script2 = "";

                                    $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
                                    $script .= "var tohide = {};";

                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];
                                        $script .= "
                                                   if($hidden_block in tohide){
                        
                                                   }else{
                                                      tohide[$hidden_block] = true;
                                                   }
                                                   ";
    //                                    }
                                        $script .= "
                                                  $.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {
                                                  ";
    //                                    foreach ($idc as $key => $fields) {
    //                                        if ($idc != 0) {
                                        $val =  0;
                                        if (isset($custom_value[$idc])) {
                                            $val =  Toolbox::addslashes_deep($custom_value[$idc]);
                                        }

                                        $script .= "
                                        if($(value).attr('title') == '$val'){
                                           tohide[" . $hidden_block . "] = false;
                                        }
                                     ";
                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                        if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                            && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc) {
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                        }
                                        if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                            foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                                if ($fieldSession == $idc) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                }
                                            }
                                        }
                                        $script .= "});";
                                    }


                                    $script .= "$.each( tohide, function( key, value ) {
                                    if(value == true){
                                     $('[bloc-id =\"bloc'+key+'\"]').hide();
                                     $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                        switch(this.type) {
                                               case 'password':
                                               case 'text':
                                               case 'textarea':
                                               case 'file':
                                               case 'date':
                                               case 'number':
                                               case 'tel':
                                               case 'email':
                                                   jQuery(this).val('');
                                                   break;
                                               case 'select-one':
                                               case 'select-multiple':
                                                   jQuery(this).val('0').trigger('change');
                                                   jQuery(this).val('0');
                                                   break;
                                               case 'checkbox':
                                               case 'radio':
                                                   this.checked = false;
                                                   break;
                                           }
                                       });
                                    } else {
                                    $('[bloc-id =\"bloc'+key+'\"]').show();

                                    }

                                 });";
                                    $script .= "fixButtonIndicator();console.log('hidden-dropdown_multiple-1')});";
//                                }

                                    //Initialize id default value
                                    foreach ($check_values as $idc => $check_value) {
                                        //include child blocks
                                        if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                                            $childs_blocks = json_decode($check_value['childs_blocks'], true);
                                            if (is_array($childs_blocks)) {
                                                foreach ($childs_blocks as $childs_block) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();";
                                                    $hiddenblocks[] = $childs_block;
                                                    $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                                }
                                            }
                                        }
                                        if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                            $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                            $hidden_block = $data['options'][$idc]['hidden_block'];
                                            foreach ($default_values as $k => $v) {
                                                if ($v == 1) {
                                                    if ($idc == $k) {
//                                                $idc = $idc[$k];
//                                                $idv = $hidden_block[$idc];
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                                    break;
                                case 'checkbox':
                                    $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                                    $script2 = "";
                                    $script .= "var tohide = {};";
                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];
                                        $script .= " if (this.checked){ ";

                                            $script .= "
                                                  if($(this).val() == $idc || $idc == -1 ){
                                                     if($hidden_block in tohide){
    
                                                     }else{
                                                        tohide[$hidden_block] = true;
                                                     }
                                                     tohide[$hidden_block] = false;
                                                  }";
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                                && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                                foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                                    if ($fieldSession == $idc || $idc == -1) {
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
        //                                        }

                                            $script .= "$.each( tohide, function( key, value ) {
                                                    if(value == true){
                                                     $('[bloc-id =\"bloc'+key+'\"]').hide();
                                                     $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                                        switch(this.type) {
                                                               case 'password':
                                                               case 'text':
                                                               case 'textarea':
                                                               case 'file':
                                                               case 'date':
                                                               case 'number':
                                                               case 'tel':
                                                               case 'email':
                                                                   jQuery(this).val('');
                                                                   break;
                                                               case 'select-one':
                                                               case 'select-multiple':
                                                                   jQuery(this).val('0').trigger('change');
                                                                   jQuery(this).val('0');
                                                                   break;
                                                               case 'checkbox':
                                                               case 'radio':
                                                                   this.checked = false;
                                                                   break;
                                                           }
                                                       });
                                                    } else {
                                                    $('[bloc-id =\"bloc'+key+'\"]').show();
    
                                                    }
    
                                                 });";
                                            $script .= "fixButtonIndicator();console.log('hidden-checkbox1');";
                                        $script .= " } else { ";
    //                                        foreach ($hidden_block as $key => $fields) {
                                            $script .= "
                                                  if($(this).val() == $idc){
                                                     if($hidden_block in tohide){
    
                                                     }else{
                                                        tohide[$hidden_block] = true;
                                                     }
                                                     $.each( $('[name^=\"field[" . $data["id"] . "]\"]:checked'),function( index, value ){";
                                                        $script .= "if($(value).val() == $idc
                                                                          || $idc == -1 ){
                                                                       tohide[$hidden_block] = false;
                                                                    }";
                                            $script .= " });
                                                        }
                                                        fixButtonIndicator();console.log('hidden-checkbox2');";
                                        $script .= " }";

                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                        if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                            && is_array($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])) {
                                            foreach ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] as $fieldSession) {
                                                if ($fieldSession == $idc || $idc == -1) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                }
                                            }
                                        }

                                        $script .= "$.each( tohide, function( key, value ) {
                                                        if(value == true){
                                                             $('[bloc-id =\"bloc'+key+'\"]').hide();
                                                             $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                                                switch(this.type) {
                                                                       case 'password':
                                                                       case 'text':
                                                                       case 'textarea':
                                                                       case 'file':
                                                                       case 'date':
                                                                       case 'number':
                                                                       case 'tel':
                                                                       case 'email':
                                                                           jQuery(this).val('');
                                                                           break;
                                                                       case 'select-one':
                                                                       case 'select-multiple':
                                                                           jQuery(this).val('0').trigger('change');
                                                                           jQuery(this).val('0');
                                                                           break;
                                                                       case 'checkbox':
                                                                       case 'radio':
                                                                            if(this.checked == true) {
                                                                                this.click();
                                                                                this.checked = false;
                                                                                break;
                                                                            }
                                                                   }
                                                               });
                                                        } else {
                                                            $('[bloc-id =\"bloc'+key+'\"]').show();
                                                        }
                                                    });
                                                    fixButtonIndicator();console.log('hidden-checkbox3');
                                                    ";
                                    }
                                    $script .= "});";

                                    //Initialize id default value
                                    foreach ($check_values as $idc => $check_value) {

                                        //include child blocks
                                        if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                                            $childs_blocks = json_decode($check_value['childs_blocks'], true);
                                            if (is_array($childs_blocks)) {
                                                foreach ($childs_blocks as $childs_block) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();";
                                                    $hiddenblocks[] = $childs_block;
                                                    $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                                }
                                            }
                                        }

                                        $hidden_block = $check_value['hidden_block'];
                                        if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                            $default_values = PluginMetademandsField::_unserialize($data['default_values']);
                                            foreach ($default_values as $k => $v) {
                                                if ($v == 1) {
                                                    if ($idc == $k) {
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
                                        }
                                    }


                                    echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                                    break;

                                case 'text':
                                case 'textarea':
                                    $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                                    $script2 = "";
//
                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];
                                        if (isset($idc) && $idc == 1) {
                                            $script .= "
                                              if($(this).val().trim().length < 1){
                                                 $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            $script .= PluginMetademandsField::getJStorersetFields($hidden_block);
                                            $script .= "
                                              } else {
                                                 $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                              }
                                            ";
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                                && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != "") {
                                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                            }
                                        } else {
                                            $script .= "
                                                 if($(this).val().trim().length < 1){
                                                       $('[bloc-id =\"bloc" . $hidden_block . "\"]').show();
                                                    } else {
                                                       $('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            $script .= PluginMetademandsField::getJStorersetFields($hidden_block);
                                            $script .= " }";
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                            if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                                && $_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == "") {
                                                $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                            }
                                        }
    //                                    }
                                        $childs_blocks = [];
                                        if (isset($data['options'])) {
                                            $opts = $data['options'];
                                            foreach ($opts as $optid => $opt) {
                                                if ($optid == $idc) {
                                                    if (!empty($opt['childs_blocks'])) {
                                                        $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                                                    }
                                                }
                                            }
                                        }

                                        if (is_array($childs_blocks[0]) && count($childs_blocks[0]) > 0) {
    //                                        foreach ($childs_blocks[0] as $customvalue => $childs) {
                                            if (isset($idc) && $idc == 1) {
                                                $script .= "
                                                  if($(this).val().trim().length < 1){";
                                                foreach ($childs_blocks[0] as $k => $v) {
                                                    $script .= PluginMetademandsField::getJStorersetFields($v);
                                                }

                                                $script .= "}
                                              ";
                                            } else {
                                                $script .= "
                                                  if($(this).val().trim().length >= 1){";
                                                foreach ($childs_blocks[0] as $k => $v) {
                                                    $script .= PluginMetademandsField::getJStorersetFields($v);
                                                }

                                                $script .= "}";
                                            }

                                            foreach ($childs_blocks[0] as $k => $v) {
                                                if ($v > 0) {
                                                    $hiddenblocks[] = $v;
                                                    $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                                }
                                            }
                                        }
                                    }
                                    $script .= "fixButtonIndicator();console.log('hidden-text1');";
                                    $script .= "});";
                                    //Initialize id default value
                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];
                                        if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                            $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                            foreach ($default_values as $k => $v) {
                                                if ($v == 1) {
                                                    if ($idc == $k) {
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                                    break;


                                case 'radio':
                                    $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";
                                    $script2 = "";
                                    $script .= "var tohide = {};";

                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];

                                        $script .= "
                                          if($hidden_block in tohide){

                                          }else{
                                             tohide[$hidden_block] = true;
                                          }
                                          if($(this).val() == $idc || $idc == -1){
                                             tohide[$hidden_block] = false;
                                          }";
                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                        if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                            && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc || $idc == -1)) {
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                        }
                                    }
                                    $script .= "$.each( tohide, function( key, value ) {
                                                        if(value == true){
                                                         $('[bloc-id =\"bloc'+key+'\"]').hide();
                                                         $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                                            switch(this.type) {
                                                                   case 'password':
                                                                   case 'text':
                                                                   case 'textarea':
                                                                   case 'file':
                                                                   case 'date':
                                                                   case 'number':
                                                                   case 'tel':
                                                                   case 'email':
                                                                       jQuery(this).val('');
                                                                       break;
                                                                   case 'select-one':
                                                                   case 'select-multiple':
                                                                       jQuery(this).val('0').trigger('change');
                                                                       jQuery(this).val('0');
                                                                       break;
                                                                   case 'checkbox':
                                                                   case 'radio':
                                                                       this.checked = false;
                                                                       break;
                                                               }
                                                           });
                                                        } else {
                                                        $('[bloc-id =\"bloc'+key+'\"]').show();
                                                        }
                                                    ";
                                    $script .= "});";
                                    $script .= "fixButtonIndicator();console.log('hidden-radio1');});";
                                    //Initialize id default value
                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];

                                        //include child blocks
                                        if (isset($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                                            $childs_blocks = json_decode($check_value['childs_blocks'], true);
                                            if (is_array($childs_blocks)) {
                                                foreach ($childs_blocks as $childs_block) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $childs_block . "\"]').hide();";
                                                    $hiddenblocks[] = $childs_block;
                                                    $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                                }
                                            }
                                        }


                                        if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                            $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                            foreach ($default_values as $k => $v) {
                                                if ($v == 1) {
                                                    if ($idc == $k) {
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                                    break;

                                case 'group':
                                case 'dropdown':
                                case 'dropdown_object':
                                case 'dropdown_meta':
                                    if ($data['item'] == "ITILCategory_Metademands") {
                                        $name = "field_plugin_servicecatalog_itilcategories_id";
                                    } else {
                                        $name = "field[" . $data["id"] . "]";
                                    }

                                    $script = "$('[name=\"$name\"]').change(function() { ";
                                    $script2 = "";
                                    $script .= "var tohide = {};";
                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];

                                        $script .= "
                                          if($hidden_block in tohide){

                                          } else {
                                             tohide[$hidden_block] = true;
                                          }
                                          if($(this).val() == $idc || ($(this).val() != 0 &&  $idc == 0 ) ){

                                             tohide[$hidden_block] = false;
                                          }";

                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').hide();";
                                        if (isset($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]])
                                            && ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] == $idc
                                                || ($_SESSION['plugin_metademands'][$data["plugin_metademands_metademands_id"]]['fields'][$data["id"]] != 0 && $idc == 0))) {
                                            $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                        } else {
                                            if ($data['type'] == "dropdown_object" && $data['item'] == 'User') {
                                                if (Session::getLoginUserID() == $idc) {
                                                    $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                }
                                            }
                                        }
                                    }
                                    $script .= "$.each( tohide, function( key, value ) {
                                                    if(value == true){
                                                     $('[bloc-id =\"bloc'+key+'\"]').hide();
                                                     $('div[bloc-id=\"bloc'+key+'\"]').find(':input').each(function() {
                                                              switch(this.type) {
                                                                     case 'password':
                                                                     case 'text':
                                                                     case 'textarea':
                                                                     case 'file':
                                                                     case 'date':
                                                                     case 'number':
                                                                     case 'tel':
                                                                     case 'email':
                                                                         jQuery(this).val('');
                                                                         break;
                                                                     case 'select-one':
                                                                     case 'select-multiple':
                                                                         jQuery(this).val('0').trigger('change');
                                                                         jQuery(this).val('0');
                                                                         break;
                                                                     case 'checkbox':
                                                                     case 'radio':
                                                                         this.checked = false;
                                                                         break;
                                                                 }
                                                             });
                                                    } else {
                                                    $('[bloc-id =\"bloc'+key+'\"]').show();
                
                                                    }
                
                                                 });";

                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];
                                        $childs_blocks = [];
                                        if (isset($data['options'])) {
                                            $opts = $data['options'];
                                            foreach ($opts as $optid => $opt) {
                                                if ($optid == $idc) {
                                                    if (!empty($opt['childs_blocks'])) {
                                                        $childs_blocks[] = json_decode($opt['childs_blocks'], true);
                                                    }
                                                }
                                            }
                                        }

                                        if (is_array($childs_blocks[0]) && count($childs_blocks[0]) > 0) {
                                            if (isset($idc)) {
                                                $script .= "
                                                     if((($(this).val() != $idc && $idc != 0 )
                                                     ||  ($(this).val() == 0 &&  $idc == 0 ) )){";

                                                foreach ($childs_blocks[0] as $k => $v) {
                                                    $script .= PluginMetademandsField::getJStorersetFields($v);
                                                }

                                                $script .= "}";

                                                foreach ($childs_blocks[0] as $k => $v) {
                                                    if ($v > 0) {
                                                        $hiddenblocks[] = $v;
                                                        $_SESSION['plugin_metademands']['hidden_blocks'] = $hiddenblocks;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $script .= "fixButtonIndicator();console.log('hidden-dropdown1')});";
                                    //Initialize id default value
                                    foreach ($check_values as $idc => $check_value) {
                                        $hidden_block = $check_value['hidden_block'];
                                        if (is_array(PluginMetademandsField::_unserialize($data['default_values']))) {
                                            $default_values = PluginMetademandsField::_unserialize($data['default_values']);

                                            foreach ($default_values as $k => $v) {
                                                if ($v == 1) {
                                                    if ($idc == $k) {
                                                        $script2 .= "$('[bloc-id =\"bloc" . $hidden_block . "\"]').show();";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    echo Html::scriptBlock('$(document).ready(function() {' . $script2 . " " . $script . '});');
                                    break;
                                default:
                                    break;
                            }
                        }
//                    }
//                }
//            }
//        }
    }

    public static function checkboxScript($data)
    {
        if (isset($data['options'])) {
            $check_values = $data['options'];

            if (is_array($check_values)) {
                if (count($check_values) > 0) {
                    foreach ($check_values as $idc => $check_value) {

                        if (!empty($data['options'][$idc]['checkbox_id'])
                            && !empty($data['options'][$idc]['checkbox_value'])) {
                            switch ($data['type']) {
                                case 'dropdown_multiple':
                                    if ($data["display_type"] == PluginMetademandsField::CLASSIC_DISPLAY) {
                                        $script = "$('[name^=\"field[" . $data["id"] . "]\"]').change(function() {";

                                        $checkbox_id = $data['options'][$idc]['checkbox_id'];
                                        $checkbox_value = $data['options'][$idc]['checkbox_value'];
//                                        
                                        $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);
                                        $script .= "
                          $.each($(this).siblings('span.select2').children().find('li.select2-selection__choice'), function( key, value ) {
                          ";
//                                        foreach ($idc as $key => $fields) {
                                        if (isset($checkbox_id) && $checkbox_id > 0) {
                                            if ($data["item"] == "other") {
                                                $title = Toolbox::addslashes_deep($custom_value[$idc]);
                                                $script .= "
                                                           if($(value).attr('title') == '$title'){
                                                              document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                                                           }
                                                        ";
                                            } else {
                                                $script .= "
                                                           if($(value).attr('title') == '" . $data["item"]::getFriendlyNameById($idc) . "'){
                                                              document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                                                           }
                                                        ";
                                            }
                                        }
//                                        }

                                        $script .= "});
                                        fixButtonIndicator();});";

                                        echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
                                    } else {
                                        $script = "$('[name^=\"field[" . $data["id"] . "]\"]').on('DOMSubtreeModified',function() {";

                                        if (isset($data['options'][$idc]['hidden_link'])
                                            && !empty($data['options'][$idc]['hidden_link'])) {
                                            $checkbox_id = $data['options'][$idc]['checkbox_id'];
                                            $checkbox_value = $data['options'][$idc]['checkbox_value'];
//                                        
//                                            $custom_value = PluginMetademandsField::_unserialize($data['custom_values']);

                                            $script .= "
                          $.each($('#multiselectfield" . $data["id"] . "_to').children(), function( key, value ) {
                          ";
//                                        foreach ($idc as $key => $fields) {
                                            if (isset($checkbox_id) && $checkbox_id > 0) {
                                                $fields = Toolbox::addslashes_deep($idc);
                                                $script .= " 
                           if($(value).attr('value') == '$idc'){
                              document.getElementById('field[$checkbox_id][$checkbox_value]').checked=true;
                           }
                        ";
                                            }
//                                        }

                                            $script .= "});
                           fixButtonIndicator();});";
                                        }

                                        echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
                                    }

                                    break;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * check fields_link to be mandatory
     * @param $id
     * @param $value
     * @param $fields
     * @return array
     */
    public static function getMandatoryFields($id, $values, $fields_links, $fields)
    {

        $toBeMandatory = [];

        $ids = [];
        if (isset($values['id'])) {
            $ids[] = $values['id'];
        }


        if (in_array($id, $ids) && !array_key_exists($id, $fields)) {
            $toBeMandatory[] = $id;
        }

        if (array_key_exists($id, $fields)
            && in_array($id, $fields_links)
            && $fields[$id] == null
        ) {
            $toBeMandatory[] = $id;
        }

        return $toBeMandatory;
    }


    /**
     * Unset values in data & post for hiddens fields
     * Add metademands_hide in Session for hidden fields
     *
     * @param $data
     * @param $post
     */
    public static function unsetHidden(&$data, &$post)
    {

        foreach ($data as $id => $value) {
            //if field is hidden remove it from Data & Post
            if (isset($value['options'])) {
                $check_values = $value['options'];

                if (is_array($check_values)) {
                    foreach ($check_values as $idc => $check_value) {

                        $hidden_link = $check_value['hidden_link'];
                        $hidden_block = $check_value['hidden_block'];
                        $taskChild = $check_value['plugin_metademands_tasks_id'];
                        $toKeep = [];

                        //for hidden fields
                        if (!isset($toKeep[$hidden_link])) {
                            $toKeep[$hidden_link] = false;
                        }
                        if (isset($post[$id]) && isset($hidden_link)) {
                            $test = PluginMetademandsTicket_Field::isCheckValueOKFieldsLinks($post[$id], $idc, $value['type']);
                        } else {
                            $test = false;
                        }

                        if ($test == true) {
                            $toKeep[$hidden_link] = true;
                            if ($taskChild != 0) {
                                $metaTask = new PluginMetademandsMetademandTask();
                                $metaTask->getFromDB($taskChild);
                                $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                                unset($_SESSION['metademands_hide'][$idChild]);
                            }
                        } else {
                            if ($taskChild != 0) {
                                $metaTask = new PluginMetademandsMetademandTask();
                                $metaTask->getFromDB($taskChild);
                                $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                                $_SESSION['metademands_hide'][$idChild] = $idChild;
                            }
                        }
                        $hidden_blocks = [$hidden_block];
                        //include child blocks
                        if (isset ($check_value['childs_blocks']) && $check_value['childs_blocks'] != null) {
                            $childs_blocks = json_decode($check_value['childs_blocks'], true);
                            if (is_array($childs_blocks)) {
                                foreach ($childs_blocks as $childs_block) {
                                    $hidden_blocks[] =$childs_block;
                                }
                            }
                        }


                        //for hidden blocks
                        $metademandsFields = new PluginMetademandsField();
                        $metademandsFields = $metademandsFields->find(["rank" => $hidden_blocks,
                            'plugin_metademands_metademands_id' => $value['plugin_metademands_metademands_id']], 'order');

                        foreach ($metademandsFields as $metademandField) {
                            if (!isset($toKeep[$metademandField['id']])) {
                                $toKeep[$metademandField['id']] = false;
                            }
                            if (isset($post[$id]) && isset($metademandField['id'])) {
                                $test = PluginMetademandsTicket_Field::isCheckValueOKFieldsLinks($post[$id], $idc, $value['type']);
                            } else {
                                $test = false;
                            }

                            if ($test == true) {
                                $toKeep[$metademandField['id']] = true;
                                if ($taskChild != 0) {
                                    $metaTask = new PluginMetademandsMetademandTask();
                                    $metaTask->getFromDB($taskChild);
                                    $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                                    unset($_SESSION['metademands_hide'][$idChild]);
                                }
                            } else {
                                if ($taskChild != 0) {
                                    $metaTask = new PluginMetademandsMetademandTask();
                                    $metaTask->getFromDB($taskChild);
                                    $idChild = $metaTask->getField('plugin_metademands_metademands_id');
                                    $_SESSION['metademands_hide'][$idChild] = $idChild;
                                }
                            }
                        }

                        foreach ($toKeep as $k => $v) {
                            if ($v == false) {
                                if (isset($post[$k])) {
                                    unset($post[$k]);
                                }
                                if (isset($data[$k])) {
                                    $data[$k]['is_mandatory'] = false;
                                }
                            }
                        }
                    }
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
}
