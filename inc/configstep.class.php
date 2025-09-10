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
 * PluginMetademandsConfigStep Class
 *
 **/
class PluginMetademandsConfigstep extends CommonDBTM
{
    static $rightname = 'plugin_metademands';
    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

    const BOTH_INTERFACE = 0;
    const ONLY_HELPDESK_INTERFACE = 1;
    const ONLY_CENTRAL_INTERFACE = 2;



    public static $disableAutoEntityForwarding   = true;
    static function canView(): bool
    {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    public static function getIcon()
    {
        return "ti ti-adjustments-pause";
    }
    /**
     * @return bool
     */
    static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Step by step settings', 'metademands');
    }

    public static function getEnumInterface()
    {
        return [
            self::ONLY_CENTRAL_INTERFACE => __('Standard interface'),
            self::ONLY_HELPDESK_INTERFACE => __('Simplified interface'),
            self::BOTH_INTERFACE => __('Both', 'metademands'),
        ];
    }


    /**
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case PluginMetademandsMetademand::getType():
                if ($item->fields['step_by_step_mode'] == 1) {
                    return self::createTabEntry(self::getTypeName());
                } else {
                    return false;
                }
                break;
        }
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $field = new self();

        if ($item->getType() == 'PluginMetademandsMetademand') {
            $field->showForMetademand($item);
        }
        return true;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    function showForMetademand($item) {

        if (!$this->canView()) {
            return false;
        }
        if (!$this->canCreate()) {
            return false;
        }
        $userLink = 0;
        $supervisor_validation = 0;
        $multipleGroup = 0;
        $addasrequester = 0;
        $confStep = new self();
        if($confStep->getFromDBByCrit(['plugin_metademands_metademands_id' => $item->fields['id']])) {
            $userLink = $confStep->fields['link_user_block'];
            $supervisor_validation = $confStep->fields['supervisor_validation'];
            $multipleGroup = $confStep->fields['multiple_link_groups_blocks'];
            $addasrequester = $confStep->fields['add_user_as_requester'];
            $blocksastab = $confStep->fields['see_blocks_as_tab'];
        }

        echo "<form name = 'form' method='post' action='".Toolbox::getItemTypeFormURL('PluginMetademandsConfigstep')."'>";
        echo "<div align='center'><table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='6'>".self::getTypeName()."</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('See blocks as tab', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('see_blocks_as_tab', $blocksastab);
        echo "</td>";
        echo "<td>";
        echo __('Activate supervisor validation', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('supervisor_validation', $supervisor_validation);
        echo "</td>";
        echo "</tr>";

        if ($supervisor_validation == 0) {
            echo "<tr class='tab_bg_1'>";

            echo "<td>";
            echo __('The user must select a defined user of the selected groups', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('link_user_block', $userLink);
            echo "</td>";

            echo "<td>";
            echo __('Link multiple groups to a block', 'metademands');
            echo "</td>";
            echo "<td>";
            Dropdown::showYesNo('multiple_link_groups_blocks', $multipleGroup);
            echo "</td>";

            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Add all form actors as ticket requester', 'metademands');
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('add_user_as_requester', $addasrequester);
        echo "</td>";
        echo "<td>";
        echo __('Interface', 'metademands');
        echo "</td>";
        echo "<td>";
        $step_by_step = $confStep->fields['step_by_step_interface'] ?? self::BOTH_INTERFACE;
        Dropdown::showFromArray('step_by_step_interface', self::getEnumInterface(),['value' => $step_by_step]);
        echo "</td>";
        echo "</tr>";

        echo "<tr><td class='tab_bg_2 center' colspan='6'>";
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
        echo Html::submit(_sx('button', 'Update'), ['name' => 'update_configstep', 'class' => 'btn btn-primary']);
        echo "</td></tr>";
        echo "</table></div>";
        Html::closeForm();
    }

    public function prepareInputForUpdate($input)
    {
//        $array_unique = [];
//        $blocks = [];
//        $step = new PluginMetademandsStep();
//        $condition = [
//            'plugin_metademands_metademands_id' => $input['plugin_metademands_metademands_id']
//        ];
//        $configStep = new PluginMetademandsConfigstep();
//        $res = $configStep->getFromDBByCrit(['id' => $input['id']]);
//        if ($res) {
//            if ($configStep->fields['link_user_block'] != $input['link_user_block']
//                || $configStep->fields['multiple_link_groups_blocks'] != $input['multiple_link_groups_blocks']) {
//                $steps = $step->find($condition);
//
//                if ($steps) {
//                    foreach ($steps as $block) {
//                        $blocks[] = $block['block_id'];
//                    }
//
//                    $array_unique = array_unique($blocks);
//                    if (count($array_unique) != count($blocks)) {
//                        $input = [];
//                        Session::addMessageAfterRedirect(__('Cannot change settings because blocks are linked to multiple groups', 'metademands'), false, ERROR);
//                    }
//                }
//            }
//        }

        return $input;
    }




}

