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
/**
 *
 */
class PluginMetademandsTools extends CommonDBTM
{

    static $rightname = 'plugin_metademands';
    private $table = "";

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    static function getTypeName($nb = 0)
    {
        return __('Tools', 'metademands');
    }

    public static function getTable($classname = null)
    {
        return "glpi_plugin_metademands_configs";
    }

    /**
     * @param \CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     * @see CommonGLPI::getTabNameForItem()
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == 'PluginMetademandsConfig') {
            return self::getTypeName();
        }
        return '';
    }

    /**
     * @param \CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     * @see CommonGLPI::displayTabContentForItem()
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'PluginMetademandsConfig') {
            $self = new self();
            $self->showTools();
        }
        return true;
    }


    public static function showTools()
    {
        global $DB;

        echo "<br><div class='center'>";
        echo "<table class='tab_cadre_fixe'>";

        $query = "SELECT plugin_metademands_fields_id, COUNT(plugin_metademands_fields_id),
                        check_value, COUNT(check_value) as nbr_doublon
                    FROM
                        glpi_plugin_metademands_fieldoptions
                    GROUP BY 
                        plugin_metademands_fields_id, 
                        check_value
                    HAVING 
                           (COUNT(plugin_metademands_fields_id) > 1) AND 
                           (COUNT(check_value) > 1)";

        $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='center'>";
            echo __('Duplicates fields options', 'metademands');
            echo "</th>";
            echo "</tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>";

            while ($array = $DB->fetchAssoc($result)) {

                $field = new PluginMetademandsField();
                $field->getfromDB($array['plugin_metademands_fields_id']);

                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_2'>";
                echo "<th class='center'>";
                echo __('Field');
                echo "</th>";
                echo "<th class='center'>";
                echo _n('Meta-Demand', 'Meta-Demands', 1, 'metademands');
                echo "</th>";
                echo "<th class='center'>";
                echo __('Number of duplicates', 'metademands');
                echo "</th>";
                echo "<th class='center'>";
                echo "</th>";
                echo "</tr>";
                echo "<tr class='tab_bg_2'>";
                echo "<td class='center'>";
                echo $field->getLink();
                echo "</td>";
                echo "<td class='center'>";
                echo Dropdown::getDropdownName("glpi_plugin_metademands_metademands", $field->fields['plugin_metademands_metademands_id']);
                echo "</td>";
                echo "<td class='center'>";
                echo $array['nbr_doublon'];
                echo "</td>";
                echo "</tr>";
                echo "</table>";
            }
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<th class='center'>";
            echo __('No duplicates founded', 'metademands');
            echo "</th>";
            echo "</tr>";
        }

        echo "</table></div>";
    }
}