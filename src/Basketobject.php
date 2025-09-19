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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use GlpiPlugin\Orderfollowup\Material;
use Html;
use Plugin;
use Session;
use PluginOrdermaterialMaterial;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Basketobject
 */
class Basketobject extends CommonDBTM
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
        $material = new Basketobject();
        if ($material->getFromDBByCrit(['reference' => $input['reference']])) {
            Session::addMessageAfterRedirect(__('This reference already exists', 'metademands'), false, ERROR);
            return false;
        }

        if (empty($input['name'])) {
            Session::addMessageAfterRedirect(__("The designation is mandatory", "metademands"), false, ERROR);
            return false;
        }

        if (empty($input['reference'])) {
            Session::addMessageAfterRedirect(__("Reference is mandatory", "metademands"), false, ERROR);
            return false;
        }

        if (!isset($input['plugin_metademands_basketobjecttypes_id'])
            || $input['plugin_metademands_basketobjecttypes_id'] == 0) {
            Session::addMessageAfterRedirect(__("The object type is mandatory", "metademands"), false, ERROR);
            return false;
        }
        return $input;
    }


    /**
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return __('Reference catalog', 'metademands');
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
            'name' => __('Designation', 'metademands'),
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
            'name' => __('Reference', 'metademands'),
            'datatype' => 'text',
        ];


        $tab[] = [
            'id' => 7,
            'table' => Basketobjecttype::getTable(),
            'field' => 'name',
            'name' => Basketobjecttype::getTypeName(),
            'datatype' => 'dropdown'
        ];

        if (Plugin::isPluginActive("ordermaterial")) {
            $tab = array_merge($tab, PluginOrdermaterialMaterial::rawSearchOptionsToAdd());
        }
        if (Plugin::isPluginActive("orderfollowup")) {
            $tab = array_merge($tab, Material::rawSearchOptionsToAdd());
        }
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
        return "ti ti-shopping-bag";
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
        echo "<td colspan='2'>" . __('Designation', 'metademands') . "<span style='color : red'> *</span></td>";
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
        echo "<td colspan='2'>" . __('Reference', 'metademands') . " <span style='color : red'> *</span></td>";
        echo "<td colspan='2'>";
        $options = [
            'value' => $this->fields['reference']
        ];
        echo Html::input('reference', $options);
        echo "</td>";
        echo "</tr>";

        echo "<tr class = 'tab_bg_1'>";
        echo "<td colspan='2'>" . Basketobjecttype::getTypeName() . "<span style='color : red'> *</span></td>";
        echo "<td colspan='2'>";
        $options = [
            'name' => 'plugin_metademands_basketobjecttypes_id',
            'value' => $this->fields['plugin_metademands_basketobjecttypes_id']
        ];
        \Dropdown::show(Basketobjecttype::class, $options);
        echo "</td>";

        echo "</tr>";

        $this->showFormButtons($options);


        return true;
    }
}
