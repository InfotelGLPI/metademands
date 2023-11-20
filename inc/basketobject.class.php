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
 * Class PluginMetademandsBasketobject
 */
class PluginMetademandsBasketobject extends CommonDBTM
{

    public $dohistory = true;
    static $rightname = "plugin_metademands";

    /**
     * @param array $input
     *
     * @return array|bool
     */
    function prepareInputForAdd($input)
    {
        $material = new PluginMetademandsBasketobject();
        if($material->getFromDBByCrit(['reference' => $input['reference']])){
            Session::addMessageAfterRedirect(__('This chargeback reference already exists', 'metademands'), false, ERROR);
            return false;
        }

        if (empty($input['name'])) {
            Session::addMessageAfterRedirect(__("The object name is mandatory", "metademands"), false, ERROR);
            return false;
        }

        if (empty($input['reference'])) {
            Session::addMessageAfterRedirect(__("Chargeback reference is mandatory", "metademands"), false, ERROR);
            return false;
        }

        if ($input['plugin_metademands_basketobjecttypes_id'] == 0) {
            Session::addMessageAfterRedirect(__("The object type is mandatory", "metademands"), false, ERROR);
            return false;
        }
        return $input;
    }


    /**
     * @param int $nb
     *
     * @return translated
     */
    static function getTypeName($nb = 0)
    {
        return __('Basket objects', 'metademands');
    }


    /**
     * @return array
     */
    function rawSearchOptions()
    {

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2)
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => 3,
            'table' => $this->getTable(),
            'field' => 'description',
            'name' => __('Description'),
            'datatype' => 'text',
        ];

        $tab[] = [
            'id' => 4,
            'table' => $this->getTable(),
            'field' => 'reference',
            'name' => __('Chargeback reference', 'metademands'),
            'datatype' => 'text',
        ];
//
//
//        $tab[] = [
//            'id' => 5,
//            'table' => $this->getTable(),
//            'field' => 'estimated_price',
//            'name' => __('Estimated price', 'metademands'),
//            'datatype' => 'decimal'
//        ];
//
//        $tab[] = [
//            'id' => 6,
//            'table' => $this->getTable(),
//            'field' => 'is_specific',
//            'name' => __('On quotation', 'metademands'),
//            'datatype' => 'bool'
//        ];


        $tab[] = [
            'id' => 7,
            'table' => PluginMetademandsBasketobjecttype::getTable(),
            'field' => 'name',
            'name' => PluginMetademandsBasketobjecttype::getTypeName(),
            'massiveaction' => false,
            'datatype' => 'dropdown'
        ];

//        $tab[] = [
//            'id' => 8,
//            'table' => $this->getTable(),
//            'field' => 'is_accessory',
//            'name' => __('Accessory', 'metademands'),
//            'datatype' => 'bool'
//        ];

        return $tab;
    }


    /**
     * get menu content
     *
     * @return array array for menu
     **@since version 0.85
     *
     */
    static function getMenuContent()
    {
        $menu = [];

        $menu['title'] = self::getMenuName();
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);
        if (self::canCreate()) {
            $menu['links']['add'] = self::getFormURL(false);
        }
        $menu['icon'] = self::getIcon();
        return $menu;
    }

    /**
     * @return string
     */
    static function getIcon()
    {
        return "fas fa-shopping-basket";
    }


    /**
     * @param array $options
     *
     * @return array
     */
    function defineTabs($options = [])
    {

        $ong = parent::defineTabs($options);
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }


    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     */
    function showForm($ID, $options = [])
    {

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'>" . __('Object name', 'metademands') . "<span style='color : red'> *</span></td>";
        echo "<td colspan='2'>";
        $options = [
            'value' => $this->fields['name']
        ];
        echo Html::input('name', $options);
        echo "</td>";
        echo "<td colspan='4'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='2'>" . __('Description') . "</td>";
        echo "<td colspan='6'>";
        $options = [
            'value' => $this->fields['description'],
            'name' => 'description',
            'cols' => 5,
            'rows' => 5
        ];
        Html::textarea($options);
        echo "</td>";
        echo "</tr>";

        echo "<tr class = 'tab_bg_1'>";
        echo "<td colspan='2'>" . __('Chargeback reference', 'metademands') . " <span style='color : red'> *</span></td>";
        echo "<td colspan='2'>";
        $options = [
            'value' => $this->fields['reference']
        ];
        echo Html::input('reference', $options);
        echo "</td>";

//        echo "<td colspan='2'>" . __('Estimated unit price', 'metademands') . "</td>";
//        echo "<td colspan = '2'>";
//
//        echo "<input type='number' class=form-control decimal' name='estimated_price' value='".$this->fields['estimated_price']."' step='0.01'>";
//        echo "</td>";
        echo "</tr>";

        echo "<tr class = 'tab_bg_1'>";
        echo "<td colspan='2'>" . PluginMetademandsBasketobjecttype::getTypeName() . "<span style='color : red'> *</span></td>";
        echo "<td colspan='2'>";
        $options = [
            'name' => 'plugin_metademands_basketobjecttypes_id',
            'value' => $this->fields['plugin_metademands_basketobjecttypes_id']
        ];
        Dropdown::show('PluginMetademandsBasketobjecttype', $options);
        echo "</td>";

//        echo "<td colspan='2'>" . __('Accessory', 'metademands') . "</td>";
//        echo "<td colspan='2'>";
//        Dropdown::showYesNo('is_accessory', $this->fields['is_accessory']);
//        echo "</td>";
        echo "</tr>";

//        echo "<tr class = 'tab_bg_1'>";
//        echo "<td colspan='2'>" . __('On quotation', 'metademands') . "</td>";
//        echo "<td colspan='2'>";
//        Dropdown::showYesNo('is_specific', $this->fields['is_specific']);
//        echo "</td>";
//        echo "</tr>";

//        if (Session::haveRight('plugin_ordermaterial_materials', CREATE)) {
            $this->showFormButtons($options);
//        }


        return true;
    }
}
