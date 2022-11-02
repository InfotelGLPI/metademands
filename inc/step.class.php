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
 * PluginMetademandsStep Class
 *
 **/
class PluginMetademandsStep extends CommonDBChild
{
    public static $itemtype  = 'PluginMetademandsMetademand';
    public static $items_id  = 'plugin_metademands_metademands_id';
    //   public        $dohistory = true;

    static $rightname = 'plugin_metademands';


    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Step-by-Step block', 'Step-by-Step blocks', $nb, 'metademands');
    }

    public static function canView()
    {
        return Session::haveRight(self::$rightname, READ);
    }

     /**
      * @return bool
      */
    public static function canCreate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }


    public function canCreateItem()
    {
        return true;
    }


    public static function canUpdate()
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
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
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
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
                    return self::createTabEntry(self::getTypeName(2));
                } else {
                    return false;
                }

                break;
        }
    }

    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     **
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case PluginMetademandsMetademand::getType():
                self::showStepByBlock($item);
                break;
        }
        return true;
    }


    /**
     * @param $plugin_metademands_metademands_id
     * @param $block_id
     *
     * @return false|mixed
     */
    public static function getGroupForNextBlock($plugin_metademands_metademands_id, $block_id)
    {
        $self      = new self();
        $condition = ['block_id'                          => $block_id,
                      'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id];

        $steps = $self->find($condition);
        if (count($steps) > 0) {
            foreach ($steps as $step) {
                if (isset($step['groups_id'])) {
                    return $step['groups_id'];
                }
            }
        }
        return false;
    }

    /**
     * @param $plugin_metademands_metademands_id
     * @param $block_id
     *
     * @return false|mixed
     */
    public static function getMsgForNextBlock($plugin_metademands_metademands_id, $block_id)
    {
        $self      = new self();
        $condition = ['block_id'                          => $block_id,
                      'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id];

        $steps = $self->find($condition);
        if (count($steps) > 0) {
            foreach ($steps as $step) {
                if (isset($step['message'])) {
                    return $step['message'];
                }
            }
        }
        return false;
    }

    /**
     * Display all translated field for a dropdown
     *
     * @param $item a Dropdown item
     *
     * @return true;
     **/
    public static function showStepByBlock($item)
    {
        global $DB, $CFG_GLPI;

        $rand    = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        if ($canedit) {
            echo "<div id='viewstepbybloc" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addstepbybloc" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = ['type'                      => __CLASS__,
                       'parenttype'                => get_class($item),
                       $item->getForeignKeyField() => $item->getID(),
                       'id'                        => -1];
            Ajax::updateItemJsCode(
                "viewstepbybloc" . $item->getType() . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
                 "<a class='submit btn btn-primary' href='javascript:addstepbybloc" .
                 $item->getType() . $item->getID() . "$rand();'>" . __('Add a new association', 'metademands') .
                 "</a></div><br>";
        }
        $iterator = $DB->request([
                                    'FROM'  => getTableForItemType(__CLASS__),
                                    'WHERE' => [

                                       'plugin_metademands_metademands_id' => $item->getID(),

                                    ]

                                 ]);
        if (count($iterator)) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='3'>" . __("List of associations", 'metademands') . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("Block", 'metademands') . "</th>";
            echo "<th>" . __("Group") . "</th>";
            echo "<th>" . __("Message", 'metademands') . "</th>";
            foreach ($iterator as $data) {
                $onhover = '';
                if ($canedit) {
                    $onhover = "style='cursor:pointer'
                           onClick=\"viewEditstepbybloc" . $item->getType() . $data['id'] . "$rand();\"";
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
                    echo "function viewEditstepbybloc" . $item->getType() . $data['id'] . "$rand() {\n";
                    $params = ['type'                      => __CLASS__,
                               'parenttype'                => get_class($item),
                               $item->getForeignKeyField() => $item->getID(),
                               'id'                        => $data["id"]];
                    Ajax::updateItemJsCode(
                        "viewstepbybloc" . $item->getType() . $item->getID() . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo($data['block_id']);
                echo "</td><td $onhover>";
                echo Dropdown::getDropdownName(Group::getTable(), $data['groups_id']);
                echo "</td><td $onhover>";
                echo Glpi\RichText\RichText::getTextFromHtml($data['message']);
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
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
            echo "<th class='b'>" . __("No association found", 'metademands') . "</th></tr></table>";
        }
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
    public function showForm($ID = -1, $options = [])
    {

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
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Block', 'metademands') . "</td>";
        echo "<td>";
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->getID()]);
//      echo Html::hidden('itemtype', ['value' => get_class($item)]);
        $field  = new PluginMetademandsField();
        $fields = $field->find(["plugin_metademands_metademands_id" => $item->getID()]);
        $blocks = [];
        $self = new self();
        foreach ($fields as $f) {
            if (!isset($blocks[$f['rank']]) &&
                (!$self->getFromDBByCrit(['plugin_metademands_metademands_id' => $item->getID(),
                                          'block_id' => intval($f['rank'])])
                    || $self->getFromDBByCrit(['plugin_metademands_metademands_id' => $item->getID(),
                                               'block_id' => intval($f['rank']), 'id' => $ID]))) {
                $blocks[intval($f['rank'])] = sprintf(__("Block %s", 'metademands'), $f["rank"]);
            }
        }
        ksort($blocks);

        Dropdown::showFromArray(
            'block_id',
            $blocks,
            ['value'   => $this->fields['block_id'],
                'width'    => '100%',
                'entity'   => $_SESSION['glpiactiveentities']]
        );

        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . Group::getTypeName() . "</td>";
        echo "<td>";
        Group::dropdown(['name' => 'groups_id',
                         'value' => $this->fields['groups_id']]);
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'><td>" . __('Message to next group on form', 'metademands') . "</td>";
        echo "<td>";
        Html::textarea(['name'            => 'message',
                        'value'           => $this->fields['message'],
                        'enable_richtext' => false,
                        'cols'            => 80,
                        'rows'            => 3]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);
        return true;
    }
}
