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
 * Class PluginMetademandsGroup
 */
class PluginMetademandsGroup extends CommonDBChild
{

    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

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
        return __('Groups rights', 'metademands');
    }

    /**
     * @return bool|int
     */
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
     * Display tab for each users
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     * @return array|string
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $dbu = new DbUtils();
        if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
                return self::createTabEntry(self::getTypeName(),
                    $dbu->countElementsInTable($this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()]));
            }
            return self::getTypeName();
        }
        return '';
    }

    /**
     * Display content for each users
     *
     * @static
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     * @return bool|true
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $field = new self();

        if ($item->getType() == 'PluginMetademandsMetademand') {
            $field->showConfigForMetademand($item);
            $field->showForMetademand($item);
        }
        return true;
    }

    /**
     * Get request criteria to search for an item
     *
     * @param string $itemtype Item type
     * @param integer $items_id Item ID
     *
     * @return array|null
     **@since 9.4
     *
     */
    public static function getSQLCriteriaToSearchForItem($itemtype, $items_id)
    {
        $table = static::getTable();

        $criteria = [
            'SELECT' => [
                static::getIndexName(),
                'plugin_metademands_metademands_id AS items_id'
            ],
            'FROM' => $table,
            'WHERE' => [
                $table . '.' . 'plugin_metademands_metademands_id' => $items_id
            ]
        ];

        // Check item 1 type
        $request = false;
        if (preg_match('/^itemtype/', static::$itemtype)) {
            $criteria['SELECT'][] = static::$itemtype . ' AS itemtype';
            $criteria['WHERE'][$table . '.' . static::$itemtype] = $itemtype;
            $request = true;
        } else {
            $criteria['SELECT'][] = new \QueryExpression("'" . static::$itemtype . "' AS itemtype");
            if (
                ($itemtype == static::$itemtype)
                || is_subclass_of($itemtype, static::$itemtype)
            ) {
                $request = true;
            }
        }
        if ($request === true) {
            return $criteria;
        }
        return null;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    function showConfigForMetademand($item)
    {

        $groupconfig = new PluginMetademandsGroupConfig();

        if (!$groupconfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $item->fields['id']])) {
            $groupconfig->getEmpty();
        }

        echo "<form name='form' method='post' action='" .
            Toolbox::getItemTypeFormURL('PluginMetademandsGroup') . "'>";

        echo "<div align='center'><table class='tab_cadre_fixe'>";
        echo "<tr><th>" . __('Define visibility', 'metademands') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        // Dropdown group
        echo "<td class='center'>";
        echo __('Visibility', 'metademands') . '&nbsp;';
        $visibility = [0 => __('Only these groups', 'metademands'),
            1 => __('All groups and not these groups', 'metademands')];
        Dropdown::showFromArray(
            'visibility',
            $visibility,
            [
                'id' => 'visibility',
                'value' => $groupconfig->fields['visibility']
            ]
        );
        echo "</td>";

        echo "<td class='tab_bg_2 center'>";
        echo Html::submit(_sx('button', 'Save'), ['name' => 'define_visibility', 'class' => 'btn btn-primary']);
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
        echo "</td>";
        echo "</tr>";
        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * @param $item
     *
     * @return bool
     */
    function showForMetademand($item)
    {

        if (!$this->canView()) {
            return false;
        }
        if (!$this->canCreate()) {
            return false;
        }
        $config = PluginMetademandsConfig::getInstance();
        $used_groups = [];

        $dataMetademandGroup = $this->find(['plugin_metademands_metademands_id' => $item->fields['id']]);

        $meta = new PluginMetademandsMetademand();
        $canedit = $meta->can($item->fields['id'], UPDATE);

        if ($dataMetademandGroup) {
            foreach ($dataMetademandGroup as $field) {
                $used_groups[] = $field['groups_id'];
            }
        }

        $groups = [];
        $group = new Group();
        $condition = [];

        $dbu = new DbUtils();
        $condition += $dbu->getEntitiesRestrictCriteria($group->getTable(), '', '', $group->maybeRecursive());
        $dataGroup = $group->find($condition, 'name');
        if ($dataGroup) {
            foreach ($dataGroup as $field) {
                $groups[$field['id']] = $field['completename'];
            }
        }

        if ($canedit) {
            echo "<form name='form' method='post' action='" .
                Toolbox::getItemTypeFormURL('PluginMetademandsGroup') . "'>";

            echo "<div align='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('Add a group', 'metademands') . "</th></tr>";

            echo "<tr class='tab_bg_1'>";
            // Dropdown group
            echo "<td class='center'>";
            echo __('Group') . '&nbsp;';
            Dropdown::showFromArray("groups_id", $groups, ['name' => 'groups_id',
                'width' => '150',
                'multiple' => true,
                'used' => $used_groups
            ]);
            echo "</td>";
            echo "</tr>";
            echo "<tr class='tab_bg_1'>";
            // Add groups with a regex
            echo "<td class='center'>";
            echo __('Regular expression', 'metademands') . '&nbsp;';
            echo Html::input('regex_value', ['type' => 'text', 'width' => '150px', 'class' => '']);
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td class='tab_bg_2 center'>";
            echo Html::submit(_sx('button', 'Add'), ['name' => 'add_groups', 'class' => 'btn btn-primary']);
            echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->fields['id']]);
            echo "</td>";
            echo "</tr>";
            echo "</table></div>";
            Html::closeForm();
        }
        if ($dataMetademandGroup) {
            $this->listItems($dataMetademandGroup, $canedit);
        }

    }

    /**
     * @param $fields
     * @param $canedit
     */
    private function listItems($fields, $canedit)
    {

        $rand = mt_rand();
        echo "<div class='left'>";
        if ($canedit) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['item' => __CLASS__, 'container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr>";
        echo "<th colspan='3'>" . __('Groups allowed to enter a demand', 'metademands') . "</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<th width='10'>";
        if ($canedit) {
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
        }
        echo "</th>";
        echo "<th>" . __('Name') . "</th>";
        echo "</tr>";
        foreach ($fields as $field) {
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
                Html::showMassiveActionCheckBox(__CLASS__, $field['id']);
            }
            echo "</td>";
            //DATA LINE
            echo "<td>" . Dropdown::getDropdownName('glpi_groups', $field['groups_id']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }

        echo "</div>";
    }


    /**
     * @param $metademands_id
     *
     * @return bool
     */
    static function isUserHaveRight($metademands_id)
    {
        $dbu = new DbUtils();

        $groupconfig = new PluginMetademandsGroupConfig();

        $visibility = 0;
        if ($groupconfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id])) {
            $visibility = $groupconfig->fields['visibility'];
        }

        // Get metademand groups
        $metademands_groups_data = $dbu->getAllDataFromTable('glpi_plugin_metademands_groups',
            ['`plugin_metademands_metademands_id`' => $metademands_id]);

        if (!empty($metademands_groups_data)) {
            $metademands_groups_id = [];
            foreach ($metademands_groups_data as $groups) {
                $metademands_groups_id[] = $groups['groups_id'];
            }

            // Is the user allowed with his groups ?
            $group_user_data = Group_User::getUserGroups(Session::getLoginUserID());

            if ($visibility == 0) {
                foreach ($group_user_data as $groups) {
                    if (in_array($groups['id'], $metademands_groups_id)) {
                        return true;
                    }
                }
                return false;
            } else {
                foreach ($group_user_data as $groups) {
                    if (in_array($groups['id'], $metademands_groups_id)) {
                        return false;
                    }
                }
                return true;
            }

            return false;
        }

        // No restrictions if no group was added in metademand
        return true;
    }

    /**
     * @return array
     */
    function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(1)
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType()
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number'
        ];

        $tab[] = [
            'id' => '92',
            'table' => 'glpi_groups',
            'field' => 'name',
            'name' => __('Group'),
            'datatype' => 'dropdown'
        ];

        return $tab;
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    function prepareInputForAdd($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        return $input;
    }

    /**
     * @param array $input
     *
     * @return array|bool
     */
    function prepareInputForUpdate($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        return $input;
    }

    /**
     * @param $input
     *
     * @return bool
     */
    function checkMandatoryFields($input)
    {
        $msg = [];
        $checkKo = false;

        $mandatory_fields = ['groups_id' => __('Group')];

        foreach ($input as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    $msg[] = $mandatory_fields[$key];
                    $checkKo = true;
                }
            }
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)), false, ERROR);
            return false;
        }
        return true;
    }

}
