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
 * Class PluginMetademandsFieldCustomvalue
 */
class PluginMetademandsFieldCustomvalue extends CommonDBChild
{
    public static $itemtype = 'PluginMetademandsField';
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


    public static function getTypeName($nb = 0)
    {
        return _n('Custom value', 'Custom values', $nb, 'metademands');
    }


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }
    //
    //
    //    static function canView()
    //    {
    //        return Session::haveRight(self::$rightname, READ);
    //    }
    //
    //    /**
    //     * @return bool
    //     */
    //    static function canCreate()
    //    {
    //        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    //    }



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
        Html::requireJs('tinymce');

        $metademand = new PluginMetademandsMetademand();
        $metademand_fields = new PluginMetademandsField();
        $metademand_params = new PluginMetademandsFieldParameter();

        $item = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, UPDATE);
            $metademand_fields->getFromDB($item->getID());
            $metademand_params->getFromDBByCrit(
                ["plugin_metademands_fields_id" => $item->getID()]
            );
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
        } else {
            $metademand_fields->getFromDB($item->getID());
            $metademand_params->getFromDBByCrit(
                ["plugin_metademands_fields_id" => $item->getID()]
            );
            $metademand->getFromDB($metademand_fields->fields['plugin_metademands_metademands_id']);
            // Create item
            $options['plugin_metademands_fields_id'] = $options['parent']->getField('id');
            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);
        $target = self::getFormURL();
        echo "<form method='post' action=\"$target\">";
        echo Html::hidden('plugin_metademands_fields_id', ['value' => $item->getID()]);
        echo Html::hidden('type', ['value' => $metademand_fields->fields['type']]);
        echo Html::hidden('item', ['value' => $metademand_fields->fields['item']]);

        $params = PluginMetademandsField::getAllParamsFromField($metademand_fields);

        self::showFieldCustomValues($params);
        Html::closeForm();
        if ($ID > 0) {
            echo "<table class='tab_cadre' width='100%'>";
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='2'>" . __('Field informations', 'metademands') . "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Type') . "</td>";
            echo "<td>";
            echo PluginMetademandsField::getFieldTypesName($params["type"]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Example', 'metademands') . "</td>";
            echo "<td>";
            echo PluginMetademandsField::getFieldInput([], $params, false, 0, 0, false, "");
            echo "</td>";
            echo "</tr>";

            echo "</table>";
        }

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
    public static function showFieldCustomValues($params = [])
    {
        //        $params['value'] = 0;
        //        $params['item'] = '';
        //        $params['type'] = '';
        //
        //        $values = $options['custom_values'];
        //        $comment = $options['comment_values'];
        //        $default = $options['default_values'];
        //
        //        foreach ($options as $key => $value) {
        //            $params[$key] = $value;
        //        }

        $allowed_customvalues_types = self::$allowed_customvalues_types;
        $allowed_custom_types = self::$allowed_custom_types;
        $allowed_customvalues_items = self::$allowed_customvalues_items;

        if (in_array($params['type'], $allowed_customvalues_types)
            || in_array($params['type'], $allowed_custom_types)
            || in_array($params['item'], $allowed_customvalues_items)) {
            echo "<table class='tab_cadre_fixe'>";
            if ($params['type'] != "dropdown_multiple"
                && $params['item'] != 'User') {
                echo "<tr class='tab_bg_1'>";
                echo "<th colspan='5'>";
                echo self::getTypeName(2);
                echo "</th>";
                echo "</tr>";
            }

            if ($params["type"] == "dropdown_multiple" && empty($params["item"])) {
                $params["item"] = "other";
            }

            if ($params["type"] == "radio") {
                $params["item"] = "radio";
            }
            if ($params["type"] == "checkbox") {
                $params["item"] = "checkbox";
            }

            if (in_array($params['type'], $allowed_customvalues_types)
                || in_array($params['item'], $allowed_customvalues_items)) {
                $ranks = [];
                foreach ($params['custom_values'] as $key => $value) {
                    $ranks[] = $value['rank'];
                }
                if (count($ranks) > 0) {
                    $hasDuplicates = count($ranks) > count(array_unique($ranks));
                    if ($hasDuplicates == true) {
                        echo "<div class='alert alert-warning d-flex'>";
                        echo "<i class='fas fa-exclamation-triangle fa-2x' style='color: orange;'></i>&nbsp;" . __(
                            'You have duplicates rank!',
                            'metademands'
                        );
                        echo "</div>";
                    }

                    if (self::isSequentialFromZero($ranks) == false) {
                        echo "<div class='alert alert-warning flex'>";
                        echo "<div class='left'>";
                        echo "<i class='fas fa-exclamation-triangle fa-2x' style='color: orange;'></i>&nbsp;" . __(
                            'The ranks are not ordered correctly, you will not be able to order them!',
                            'metademands'
                        );
                        echo "<br><br>";
                        echo _x('button', 'Do you want to fix them ? Warning you must check your options after!', 'metademands');
                        echo "</div>";
                        echo "<div class='right'>";
                        $target = self::getFormURL();
                        Html::showSimpleForm(
                            $target,
                            'fixranks',
                            _x('button', 'Do you want to fix them ? Warning you must check your options after!', 'metademands'),
                            [
                                'plugin_metademands_fields_id' => $params["plugin_metademands_fields_id"],
                            ],
                            'fa-wrench',
                            "class='btn btn-warning'"
                        );
                        echo "</div>";
                        echo "</div>";
                    }
                }
            }


            if ($params["type"] != "dropdown_multiple") {
                switch ($params['item']) {
                    case 'impact':
                    case 'urgency':
                    case 'priority':
                    case 'mydevices':
                    case 'other':
                        PluginMetademandsDropdownmeta::showFieldCustomValues($params);
                        break;
                    default:
                        break;
                }
            }

            $class = PluginMetademandsField::getClassFromType($params['type']);

            switch ($params['type']) {
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
                case 'date':
                case 'time':
                case 'date_interval':
                case 'datetime':
                case 'upload':
                case 'datetime_interval':
                case 'dropdown':
                case 'parent_field':
                    break;
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

            echo "</table>";
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
    public static function initCustomValue($count, $display_comment = false, $display_default = false, $plugin_metademands_fields_id = 0)
    {
        Html::requireJs("metademands");
        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(
            ['root_doc' => PLUGIN_METADEMANDS_WEBDIR]
        ) . ");";

        echo Html::hidden('display_comment', ['id' => 'display_comment', 'value' => $display_comment]);
        echo Html::hidden('count_custom_values', ['id' => 'count_custom_values', 'value' => $count]);
        echo Html::hidden('display_default', ['id' => 'display_default', 'value' => $display_default]);

        echo "&nbsp;<i class='fa-2x fas fa-plus-square' style='cursor:pointer;'
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
            'icon'  => 'fas fa-upload',
            'confirm' => __('Are you sure ? Custom values will be deleted !', 'metademands')]);
        $warning = __('Please respect this format : name; display by default(0|1); comment; - sorted by display order', 'metademands');
        Html::showToolTip($warning);
        Html::closeForm();
        echo "</div>";
        echo Html::scriptBlock(
            <<<JAVASCRIPT
         function formToggle(ID) {
                var element = document.getElementById(ID);
                if (element.style.display === "none") {
                    element.style.display = "block";
                } else {
                    element.style.display = "none";
                }
            };
JAVASCRIPT
        );
        echo "</td>";
        echo "</tr>";
    }

    /**
     * @param $valueId
     * @param $display_comment
     * @param $display_default
     */
    public static function addNewValue($rank, $display_comment, $display_default, $fields_id)
    {

        $target = self::getFormURL();
        echo "<form method='post' action=\"$target\">";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";

        echo "<td id='show_custom_fields'>";
        echo '<span id=\'custom_values' . $rank . '\'>';
        echo __('Rank', 'metademands') . ' ' . $rank . ' ';
        $name = "custom_values[$rank]";
        echo Html::input($name, ['size' => 50]);
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo '<span id=\'comment_values' . $rank . '\'>';
        if ($display_comment) {
            echo " " . __('Comment') . " ";
            $name = "comment_values[$rank]";
            echo Html::input($name, ['size' => 30]);
        }
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo '<span id=\'default_values' . $rank . '\'>';
        if ($display_default) {
            echo " " . _n('Default value', 'Default values', 1, 'metademands') . " ";
            $name = "default_values[$rank]";
            $value = 0;
            Dropdown::showYesNo($name, $value);
        }

        echo "</span>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo Html::submit("", ['name'  => 'add',
            'class' => 'btn btn-primary',
            'icon'  => 'fas fa-save']);

        echo Html::hidden('rank', ['value' => $rank]);
        echo Html::hidden('fields_id', ['value' => $fields_id]);
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        Html::closeForm();
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
            return false; // Vérifie que le tableau n'est pas vide et commence bien par 0
        }

        for ($i = 1; $i < count($arr); $i++) {
            if ($arr[$i] - $arr[$i - 1] !== 1) {
                return false; // Vérifie que la progression est bien de +1
            }
        }
        return true;
    }

    public static function fixRanks(array $data)
    {
        // Extraire les clés du tableau
        $keys = array_keys($data);

        // Réinitialiser le rank à partir de 0
        foreach ($keys as $index => $key) {
            $data[$key]['rank'] = $index;
        }

        return $data;
    }
}
