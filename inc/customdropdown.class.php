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
class PluginMetademandsCustomDropdown extends CommonDBChild
{

    public static $itemtype = 'PluginMetademandsField';
    public static $items_id = 'plugin_metademands_fields_id';
    public $dohistory = true;

    static $rightname = 'plugin_metademands';



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


    public function canCreateItem()
    {

        return true;

    }

    /**
     * Display field option form
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
            case 'title':
                break;
            case 'title-block':
                break;
            case 'informations':
                break;
            case 'text':
                PluginMetademandsText::getParamsValueToCheck($this, $item, $params);
                break;
            case 'textarea':
                PluginMetademandsTextarea::getParamsValueToCheck($this, $item, $params);
                break;
            case 'dropdown_meta':
                PluginMetademandsDropdownmeta::getParamsValueToCheck($this, $item, $params);
                break;
            case 'dropdown_object':
                PluginMetademandsDropdownobject::getParamsValueToCheck($this, $item, $params);
                break;
            case 'dropdown':
                PluginMetademandsDropdown::getParamsValueToCheck($this, $item, $params);
                break;
            case 'dropdown_multiple':
                PluginMetademandsDropdownmultiple::getParamsValueToCheck($this, $item, $params);
                break;
            case 'checkbox':
                PluginMetademandsCheckbox::getParamsValueToCheck($this, $item, $params);
                break;
            case 'radio':
                PluginMetademandsRadio::getParamsValueToCheck($this, $item, $params);
                break;
            case 'yesno':
                PluginMetademandsYesno::getParamsValueToCheck($this, $item, $params);
                break;
            case 'number':
                break;
            case 'date':
                break;
            case 'date_interval':
                break;
            case 'datetime':
                break;
            case 'datetime_interval':
                break;
            case 'upload':
                break;
            case 'link':
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
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        self::getPluginParamsValueToCheck($plug, $this, $item->getID(), $params);
                    }
                }
                break;
        }

        $this->showFormButtons($options);
        return true;
    }

    static function getCustomValuesByField(int $plugin_metademands_fields_id) {
        $self = new self();
        $data = $self->find(['plugin_metademands_fields_id' => $plugin_metademands_fields_id], ['order']);
        if(empty($data)) {
            return [];
        } else {
            $customvalues = [];
            foreach ($data as $custom) {
                $customvalues[$custom['id']] = $custom['value'];
            }
            return $customvalues;
        }
    }

    static function getCustomDefaultValuesByField(int $plugin_metademands_fields_id) {
        $self = new self();
        $data = $self->find(['plugin_metademands_fields_id' => $plugin_metademands_fields_id], ['order']);
        if(empty($data)) {
            return [];
        } else {
            $defaultvalues = [];
            foreach ($data as $custom) {
                $defaultvalues[$custom['id']] = $custom['default_value'];
            }
            return $defaultvalues;
        }
    }

    static function getCustomCommentValuesByField(int $plugin_metademands_fields_id) {
        $self = new self();
        $data = $self->find(['plugin_metademands_fields_id' => $plugin_metademands_fields_id], ['order']);
        if(empty($data)) {
            return [];
        } else {
            $defaultvalues = [];
            foreach ($data as $custom) {
                $defaultvalues[$custom['id']] = $custom['default_value'];
            }
            return $defaultvalues;
        }
    }

    static function manageCustomValues(int $plugin_metademands_fields_id,array $values, array $default_values, array $comment_values) {
        $self = new self();
        $data = $self->find(['plugin_metademands_fields_id' => $plugin_metademands_fields_id]);
        $old_val = [];
        $new_val = [];
        $order = 1;
        if(!empty($data)) {
            foreach ($data as $custom) {
                $old_val[$custom['id']] = $custom['order'];
            }
            $order = max($old_val) + 1;
        }

        foreach ($values as $id => $val) {
            $input = [
                'plugin_metademands_fields_id' => $plugin_metademands_fields_id,
                'value' => $val,
                'default_value' => $default_values[$id],
                'comment' => $comment_values[$id],
            ];
            if(str_contains($id,'new_')) {
                $input['order'] = $order;
                $self->add($input);
                $order++;
            } else {
                $input['id'] = $id;
                $self->update($input);
            }
        }
    }


}
