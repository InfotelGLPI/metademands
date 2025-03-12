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
 * Class PluginMetademandsFreetablefield
 */
class PluginMetademandsFreetablefield extends CommonDBTM
{
    public static $itemtype = 'PluginMetademandsField';
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    static $rightname = 'plugin_metademands';

    const TYPE_TEXT                 = 1;
    const TYPE_SELECT               = 2;

    static function getTypeName($nb = 0)
    {
        return _n('Free table field', 'Free table fields', $nb, 'metademands');
    }


    static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }


    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (isset($item->fields['type'])
        && $item->fields['type'] == "freetable") {
            $nb = self::getNumberOfFieldsForItem($item);
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
    static function getNumberOfFieldsForItem($item)
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
            $field_custom->showFieldsForm($field_custom->getID(), ['parent' => $item]);
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
            $types[0] = Dropdown::EMPTY_VALUE;
        }
        $types[self::TYPE_TEXT] = __('Text', 'metademands');
        $types[self::TYPE_SELECT] = __('Dropdown', 'metademands');
        return $types;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
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
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
//            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);

        echo Html::hidden('plugin_metademands_fields_id', ['value' => $item->getID()]);
        echo Html::hidden('type', ['value' => $metademand_fields->fields['type']]);
        echo Html::hidden('item', ['value' => $metademand_fields->fields['item']]);

        $params = PluginMetademandsField::getAllParamsFromField($metademand_fields);

        self::showFreetableFields($params);

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
     * @param array $params
     * @return void
     */
    static public function showFreetableFields($params = [])
    {

        if ($params['type'] == "freetable") {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<th colspan='5'>";
            echo _n('Free table field', 'Free table fields', 2, 'metademands');
            $label =  __('(6 fields maximum)', 'metademands');
            echo "&nbsp;";
            Html::showToolTip(
                Glpi\RichText\RichText::getSafeHtml($label),
                ['awesome-class' => 'fa-info-circle']
            );
            echo "</th>";
            echo "</tr>";

            PluginMetademandsFreetable::showFreetableFields($params);

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
                'rank' => $params['old_order']
            ];

            $itemMove = new self();
            $itemMove->getFromDBByCrit($crit);

            if (isset($itemMove->fields["id"])) {
                // Reorganization of all fields
                if ($params['old_order'] < $params['new_order']) {
                    $toUpdateList = $this->find([
                        '`rank`' => ['>', $params['old_order']],
                        'rank'   => ['<=', $params['new_order']]
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id'      => $toUpdate['id'],
                            'rank' => $toUpdate['rank'] - 1
                        ]);
                    }
                } else {
                    $toUpdateList = $this->find([
                        '`rank`' => ['<', $params['old_order']],
                        'rank'   => ['>=', $params['new_order']]
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id'      => $toUpdate['id'],
                            'rank' => $toUpdate['rank'] + 1
                        ]);
                    }
                }

                if (isset($itemMove->fields["id"]) && $itemMove->fields['id'] > 0) {
                    $this->update([
                        'id'      => $itemMove->fields['id'],
                        'rank' => $params['new_order']
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
    public static function initCustomValue($count, $plugin_metademands_fields_id = 0)
    {
        Html::requireJs("metademands");
        $script = "var metademandWizard = $(document).metademandWizard(" . json_encode(
                ['root_doc' => PLUGIN_METADEMANDS_WEBDIR]
            ) . ");";

        echo Html::hidden('display_comment', ['id' => 'display_comment', 'value' => true]);
        echo Html::hidden('count_custom_values', ['id' => 'count_custom_values', 'value' => $count]);
        echo Html::hidden('display_default', ['id' => 'display_default', 'value' => true]);

        echo "&nbsp;<i class='fa-2x fas fa-plus-square' style='cursor:pointer;'
            onclick='$script metademandWizard.metademands_add_custom_values(\"show_custom_fields\", $plugin_metademands_fields_id);'
            title='" . _sx("button", "Add") . "'/></i>&nbsp;";

        echo "<td align='center' id='show_custom_fields'>";
        echo "<span id = 'add_custom_values' style='display:none'>";
        echo Html::submit("", ['name'  => 'add',
            'class' => 'btn btn-primary',
            'icon'  => 'fas fa-save']);
        echo "</td>";
    }


    /**
     * @param $valueId
     * @param $display_comment
     * @param $display_default
     */
    public static function addNewValue($rank)
    {

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>";

        echo "<td id='show_custom_fields'>";
        echo '<span id=\'internal_name_values' . $rank . '\'>';
        echo __('Rank', 'metademands') . ' ' . $rank . '<br>';
        echo " " . __('Internal name', 'metademands') . " ";
        $label =  __('No spaces, no special characters', 'metademands');
        Html::showToolTip(
            Glpi\RichText\RichText::getSafeHtml($label),
            ['awesome-class' => 'fa-info-circle']
        );
        $name = "internal_name_values[$rank]";
        echo Html::input($name, ['size' => 20]);
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo '<span id=\'type_values' . $rank . '\'>';
        echo "<br>" . __('Type', 'metademands') . "<br>";
        $name = "type_values[$rank]";
        $types = self::getTypeFields(true);
        Dropdown::showFromArray($name, $types, ['on_change' => 'hideandshow(this.value)']);
        echo "<script type='text/javascript'>";
        echo "function hideandshow (type) {

        if (type == 1) {
            var span_dropdowns = document.getElementsByClassName('newdropdownvalue$rank');
            for (var i = 0; i < span_dropdowns.length; i++) {
                span_dropdowns[i].style.display = 'none';
            }
            var span_text = document.getElementsByClassName('newcomment$rank');
            for (var j = 0; j < span_text.length; j++) {
                span_text[j].style.display = 'initial';
            }
        } else {
            var span_dropdowns = document.getElementsByClassName('newdropdownvalue$rank');
            for (var h = 0; h < span_dropdowns.length; h++) {
                span_dropdowns[h].style.display = 'initial';
            }
            var span_text = document.getElementsByClassName('newcomment$rank');
            for (var m = 0; m < span_text.length; m++) {
                span_text[m].style.display = 'none';
            }        
        
        }";
        echo "};";
        echo "</script>";
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo "<span id='custom_values$rank'>";
        echo "<br>" . __('Display name', 'metademands') . " ";
        $name = "custom_values[$rank]";
        echo Html::input($name, ['size' => 20]);
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo "<span class='newdropdownvalue$rank' id='dropdown_values$rank'  style='display:none'>";
        echo "<br>" . __('Dropdown values', 'metademands') . " ";
        $label =  __('One value by line, separated by comma', 'metademands');
        Html::showToolTip(
            Glpi\RichText\RichText::getSafeHtml($label),
            ['awesome-class' => 'fa-info-circle']
        );
        $name = "dropdown_values[$rank]";
        Html::textarea(['name' => $name,
            'rows' => 3,
            'cols' => 5]);
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo "<span class='newcomment$rank' id='comment_values$rank'  style='display:none'>";
        echo "<br>" . __('Comment') . " ";
        $name = "comment_values[$rank]";
        echo Html::input($name, ['size' => 20]);
        echo "</span>";
        echo "</td>";

        echo "<td id='show_custom_fields'>";
        echo "<span id='is_mandatory_values$rank'>";
        echo "<br>" . __('Mandatory', 'metademands') . "<br>";
        $name = "is_mandatory_values[$rank]";
        $value = 0;
        Dropdown::showYesNo($name, $value);
        echo "</span>";
        echo "</td>";

        echo Html::hidden('rank', ['value' => $rank]);

        echo "</tr>";
        echo "</table>";
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
                __("You can't add a field without name", "metademands"),
                false,
                ERROR
            );
            return false;
        }
        if (empty($input['type']) || $input['type'] == 0
        ) {
            Session::addMessageAfterRedirect(
                __("You can't add a field without type", "metademands"),
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
}
