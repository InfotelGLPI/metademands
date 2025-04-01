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
 * Class PluginMetademandsField
 */
class PluginMetademandsField extends CommonDBChild
{
    public static $rightname = 'plugin_metademands';

    public static $itemtype = 'PluginMetademandsMetademand';
    public static $items_id = 'plugin_metademands_metademands_id';

    public $dohistory = true;
    // Request type
    const MAX_FIELDS = 40;

    use Glpi\Features\Clonable;

    public static $field_types = [
        '',
        'dropdown',
        'dropdown_object',
        'dropdown_meta',
        'dropdown_multiple',
        'title',
        'title-block',
        'informations',
        'text',
        'tel',
        'email',
        'url',
        'textarea',
        'yesno',
        'checkbox',
        'radio',
        'number',
        'range',
        'freetable',
        'basket',
        'date',
        'time',
        'datetime',
        'date_interval',
        'datetime_interval',
        'upload',
        'link',
        'signature',
        'parent_field'
    ];

    public static $field_title_types = [
        'title',
        'title-block',
        'informations',
    ];

    public static $field_customvalues_types = [
        'dropdown_meta',
        'dropdown_multiple',
        'checkbox',
        'radio',
    ];

    public static $field_dropdown_types = [
        'dropdown',
        'dropdown_object',
    ];

    public static $field_text_types = [
        'text',
        'tel',
        'email',
        'url',
        'textarea',
        'signature',
    ];

    public static $field_date_types = [
        'date',
        'time',
        'datetime',
        'date_interval',
        'datetime_interval',
    ];

    public static $field_specificobjects = [
        'urgency',
        'impact',
        'priority',
        'mydevices',
    ];

    public static $field_withobjects = [
        'dropdown',
        'dropdown_object',
        'dropdown_meta',
        'dropdown_multiple',
        'basket',
    ];

    public static $not_null = 'NOT_NULL';


    public static function getIcon()
    {
        return PluginMetademandsMetademand::getIcon();
    }


    public function canCreateItem()
    {
        return true;
    }

    /**
     * functions mandatory
     * getTypeName(), canCreate(), canView()
     *
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Wizard creation', 'metademands');
    }

    /**
     * @return bool|int
     */
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



    public function getCloneRelations(): array
    {
        return [
            PluginMetademandsFieldParameter::class,
            PluginMetademandsFieldOption::class,
            PluginMetademandsFieldCustomvalue::class,
            PluginMetademandsFieldTranslation::class,
            PluginMetademandsFreetablefield::class,
        ];
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
     *
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'PluginMetademandsMetademand') {
            if ($_SESSION['glpishow_count_on_tabs']) {
                $dbu = new DbUtils();
                return self::createTabEntry(
                    self::getTypeName(),
                    $dbu->countElementsInTable(
                        $this->getTable(),
                        ["plugin_metademands_metademands_id" => $item->getID()]
                    )
                );
            }
            return self::getTypeName();
        }
        return '';
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
        self::listFields($item);

        return true;
    }

    /**
     * @param array $options
     *
     * @return array
     * @see CommonGLPI::defineTabs()
     */
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginMetademandsFieldParameter', $ong, $options);
        if ($this->fields['type'] == 'freetable') {
            $this->addStandardTab('PluginMetademandsFreetablefield', $ong, $options);
        } else {
            $this->addStandardTab('PluginMetademandsFieldCustomvalue', $ong, $options);
        }
        $this->addStandardTab('PluginMetademandsFieldOption', $ong, $options);
        $this->addStandardTab('PluginMetademandsFieldTranslation', $ong, $options);
        if (Session::getCurrentInterface() == 'central') {
            $this->addStandardTab('Log', $ong, $options);
        }
        return $ong;
    }


    public function showExistingForm($ID, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!$this->canview()) {
            return false;
        }

        if (!$this->cancreate()) {
            return false;
        }

        $metademand = new PluginMetademandsMetademand();

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            if (!isset($item)) {
                return false;
            }
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
            $metademand->getFromDB($item->getID());
            // Create item
            $this->check(-1, CREATE, $options);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
            $this->fields['color'] = '#000';
        }

        // Data saved in session
        $sessionId = $ID > 0 ? $ID : 0;
        if (isset($_SESSION['glpi_plugin_metademands_fields'][$sessionId])) {
            foreach ($_SESSION['glpi_plugin_metademands_fields'][$sessionId] as $key => $value) {
                $this->fields[$key] = $value;
            }
            unset($_SESSION['glpi_plugin_metademands_fields']);
        }

        $this->showFormHeader($options);

        echo Html::hidden(
            'plugin_metademands_metademands_id',
            ['value' => $this->fields["plugin_metademands_metademands_id"]]
        );

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Field', 'metademands') . "</td>";
        echo "<td>";

        $randType = self::dropdownFieldTypes(
            self::$field_types,
            ['metademands_id' => $this->fields["plugin_metademands_metademands_id"]]
        );
        $paramsType = [
            'value' => '__VALUE__',
            'step' => 'listfieldbytype'
        ];
        Ajax::updateItemOnSelectEvent(
            'dropdown_type' . $randType,
            "show_listfields_by_type",
            PLUGIN_METADEMANDS_WEBDIR .
            "/ajax/viewtypefields.php?id=" . $this->fields['id'],
            $paramsType
        );

        echo "<div id='show_listfields_by_type'>";
        echo "</div>";

        echo "<div id='show_fields_infos'>";
        echo "</div>";

        echo "</td>";
        echo "</tr>";


        // BLOCK
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Block', 'metademands') . "</td>";
        echo "<td>";

        $values = [];

        $randRank = Dropdown::showNumber('rank', [
            'value' => $this->fields["rank"],
            'min' => 1,
            'max' => self::MAX_FIELDS
        ]);
        $paramsRank = [
            'rank' => '__VALUE__',
            'step' => 'order',
            'fields_id' => $this->fields['id'],
            'metademands_id' => $this->fields['plugin_metademands_metademands_id'],
            'previous_fields_id' => $this->fields['plugin_metademands_fields_id']
        ];
        Ajax::updateItemOnSelectEvent(
            'dropdown_rank' . $randRank,
            "show_order",
            PLUGIN_METADEMANDS_WEBDIR .
            "/ajax/viewtypefields.php?id=" . $this->fields['id'],
            $paramsRank
        );
        echo "</td>";
        echo "</tr>";

        // ORDER
        echo "<tr class='tab_bg_1'>";
        if ($this->fields['type'] != "title-block") {
            echo "<td>" . __('Display field after', 'metademands') . "</td>";
            echo "<td>";
            echo "<span id='show_order'>";
            $this->showOrderDropdown(
                $this->fields['rank'],
                $this->fields['id'],
                $this->fields['plugin_metademands_fields_id'],
                $this->fields["plugin_metademands_metademands_id"]
            );
            echo "</span>";
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }
        echo "</tr>";

        $this->showFormButtons(['colspan' => 2]);
        return true;
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     * @throws \GlpitestSQLError
     */
    public function showForm($ID, $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!$this->canview()) {
            return false;
        }
        if (!$this->cancreate()) {
            return false;
        }
        Html::requireJs('tinymce');

        $metademand = new PluginMetademandsMetademand();

        if (isset($options['parent']) && !empty($options['parent'])) {
            $item = $options['parent'];
        }

        if ($ID > 0) {
            $this->check($ID, READ);
            $metademand->getFromDB($this->fields['plugin_metademands_metademands_id']);
        } else {
            // Create item
            if (!isset($item)) {
                return false;
            }
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();
            $metademand->getFromDB($item->getID());
            // Create item
            $this->check(-1, CREATE, $options);
            $this->getEmpty();
            $this->fields["plugin_metademands_metademands_id"] = $item->fields['id'];
        }

        // Data saved in session
        $sessionId = $ID > 0 ? $ID : 0;
        if (isset($_SESSION['glpi_plugin_metademands_fields'][$sessionId])) {
            foreach ($_SESSION['glpi_plugin_metademands_fields'][$sessionId] as $key => $value) {
                $this->fields[$key] = $value;
            }
            unset($_SESSION['glpi_plugin_metademands_fields']);
        }

        $this->showFormHeader($options);

        $metademand_fields = new self();
        $metademand_fields->getFromDBByCrit([
            'plugin_metademands_metademands_id' => $this->fields['plugin_metademands_metademands_id'],
            'item' => 'ITILCategory_Metademands'
        ]);
        $categories = [];
        if (isset($metademand->fields['itilcategories_id'])) {
            if (is_array(json_decode($metademand->fields['itilcategories_id'], true))) {
                $categories = json_decode($metademand->fields['itilcategories_id'], true);
            }
        }

        echo Html::hidden(
            'plugin_metademands_metademands_id',
            ['value' => $this->fields["plugin_metademands_metademands_id"]]
        );

        if (count($metademand_fields->fields) < 1 && count($categories) > 1) {
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo "<i class='fas fa-exclamation-triangle fa-3x'></i>&nbsp;" . __(
                    'Please add a type category field',
                    'metademands'
                );
            echo "</div>";
        }

        echo "<tr class='tab_bg_1'>";

        // LABEL
        echo "<td>" . __('Label') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
        echo "<td>";
        if (isset($this->fields["name"])) {
            $name = stripslashes($this->fields["name"]);
        } else {
            $name = "";
        }
        echo Html::input('name', ['value' => $name, 'size' => 40]);
        if ($ID > 0) {
            echo Html::hidden('entities_id', ['value' => $this->fields["entities_id"]]);
            echo Html::hidden('is_recursive', ['value' => $this->fields["is_recursive"]]);
        } else {
            echo Html::hidden('entities_id', ['value' => $item->fields["entities_id"]]);
            echo Html::hidden('is_recursive', ['value' => $item->fields["is_recursive"]]);
        }
        echo "</td>";

        echo "<td colspan='2'>";
        echo "</td>";
        echo "</tr>";

        // LABEL 2
        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Additional label', 'metademands');
        echo "&nbsp;<span id='show_label2' style='color:red;display:none;'>&nbsp;*&nbsp;</span>";
        echo "</td>";
        echo "<td>";
        $label2 = Html::cleanPostForTextArea($this->fields['label2']);
        Html::textarea([
            'name' => 'label2',
            'value' => $label2,
            'enable_richtext' => true,
            'enable_fileupload' => false,
            'enable_images' => true,
            'cols' => 50,
            'rows' => 3
        ]);
        echo "</td>";

        // COMMENT
        echo "<td>" . __('Comments') . "</td>";
        echo "<td>";
        $comment = Html::cleanPostForTextArea($this->fields['comment']);
        Html::textarea([
            'name' => 'comment',
            'value' => $comment,
            'enable_richtext' => true,
            'enable_fileupload' => false,
            'enable_images' => false,
            'cols' => 50,
            'rows' => 3
        ]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        // TYPE
        echo "<td>" . __('Type') . "<span style='color:red'>&nbsp;*&nbsp;</span></td>";
        echo "<td>";

        if ($ID < 1) {
            $randType = self::dropdownFieldTypes(self::$field_types, [
                'value' => $this->fields["type"],
                'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
            ]);

            $paramsType = [
                'value' => '__VALUE__',
                'type' => '__VALUE__',
                'item' => $this->fields['item'],
                'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                'change_type' => 1
            ];
            Ajax::updateItemOnSelectEvent(
                'dropdown_type' . $randType,
                "show_values",
                PLUGIN_METADEMANDS_WEBDIR .
                "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                $paramsType
            );
        } else {
            if (in_array($this->fields["type"], self::$field_title_types)) {
                $randType = self::dropdownFieldTypes(self::$field_title_types, [
                    'value' => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                ]);

                $paramsType = [
                    'value' => '__VALUE__',
                    'type' => '__VALUE__',
                    'item' => $this->fields['item'],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    'change_type' => 1
                ];
                Ajax::updateItemOnSelectEvent(
                    'dropdown_type' . $randType,
                    "show_values",
                    PLUGIN_METADEMANDS_WEBDIR .
                    "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                    $paramsType
                );
            } elseif (in_array($this->fields["type"], self::$field_customvalues_types)) {
                if (in_array($this->fields["item"], PluginMetademandsDropdownmultiple::$dropdown_multiple_objects)) {
                    $randType = self::dropdownFieldTypes(["dropdown_multiple"], [
                        'value' => $this->fields["type"],
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                    ]);

                    $paramsType = [
                        'value' => '__VALUE__',
                        'type' => '__VALUE__',
                        'item' => $this->fields['item'],
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                        'change_type' => 1
                    ];
                    Ajax::updateItemOnSelectEvent(
                        'dropdown_type' . $randType,
                        "show_values",
                        PLUGIN_METADEMANDS_WEBDIR .
                        "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                        $paramsType
                    );
                } else {
                    if ($this->fields["item"] == "other" || $this->fields["type"] == "radio" || $this->fields["type"] == "checkbox") {
                        $randType = self::dropdownFieldTypes(self::$field_customvalues_types, [
                            'value' => $this->fields["type"],
                            'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                        ]);

                        $paramsType = [
                            'value' => '__VALUE__',
                            'type' => '__VALUE__',
                            'item' => $this->fields['item'],
                            'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                            'change_type' => 1
                        ];
                        Ajax::updateItemOnSelectEvent(
                            'dropdown_type' . $randType,
                            "show_values",
                            PLUGIN_METADEMANDS_WEBDIR .
                            "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                            $paramsType
                        );
                    } else {
                        echo self::getFieldTypesName($this->fields['type']);
                        echo Html::hidden('type', ['value' => $this->fields['type']]);
                    }
                }
            } elseif (in_array($this->fields["type"], self::$field_text_types)) {
                $randType = self::dropdownFieldTypes(self::$field_text_types, [
                    'value' => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                ]);

                $paramsType = [
                    'value' => '__VALUE__',
                    'type' => '__VALUE__',
                    'item' => $this->fields['item'],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    'change_type' => 1
                ];
                Ajax::updateItemOnSelectEvent(
                    'dropdown_type' . $randType,
                    "show_values",
                    PLUGIN_METADEMANDS_WEBDIR .
                    "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                    $paramsType
                );
            } elseif (in_array($this->fields["type"], self::$field_date_types)) {
                $randType = self::dropdownFieldTypes(self::$field_date_types, [
                    'value' => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                ]);

                $paramsType = [
                    'value' => '__VALUE__',
                    'type' => '__VALUE__',
                    'item' => $this->fields['item'],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    'change_type' => 1
                ];
                Ajax::updateItemOnSelectEvent(
                    'dropdown_type' . $randType,
                    "show_values",
                    PLUGIN_METADEMANDS_WEBDIR .
                    "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                    $paramsType
                );
            } elseif (in_array($this->fields["type"], self::$field_dropdown_types)) {
                $randType = self::dropdownFieldTypes(self::$field_dropdown_types, [
                    'value' => $this->fields["type"],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                ]);

                $paramsType = [
                    'value' => '__VALUE__',
                    'type' => '__VALUE__',
                    'item' => $this->fields['item'],
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    'change_type' => 1
                ];
                Ajax::updateItemOnSelectEvent(
                    'dropdown_type' . $randType,
                    "show_values",
                    PLUGIN_METADEMANDS_WEBDIR .
                    "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                    $paramsType
                );
            } elseif ($this->fields["type"] == "dropdown_multiple") {
                if (in_array($this->fields["item"], PluginMetademandsDropdownmultiple::$dropdown_multiple_objects)) {
                    echo self::getFieldTypesName($this->fields['type']);
                    echo Html::hidden('type', ['value' => $this->fields['type']]);
                } else {
                    $randType = self::dropdownFieldTypes(["dropdown_multiple"], [
                        'value' => $this->fields["type"],
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"]
                    ]);

                    $paramsType = [
                        'value' => '__VALUE__',
                        'type' => '__VALUE__',
                        'item' => $this->fields['item'],
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                        'change_type' => 1
                    ];
                    Ajax::updateItemOnSelectEvent(
                        'dropdown_type' . $randType,
                        "show_values",
                        PLUGIN_METADEMANDS_WEBDIR .
                        "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                        $paramsType
                    );
                }
            } else {
                echo self::getFieldTypesName($this->fields['type']);
                echo Html::hidden('type', ['value' => $this->fields['type']]);
            }
        }
        if ($metademand->fields['is_basket'] == 0
            && ($this->fields['type'] == 'basket' || $this->fields['type'] == 'free_input')) {
            echo "<span class='alert alert-warning d-flex'>";
            echo __('Remember to activate basket mode on your metademand !', 'metademands');
            echo "</span>";
        }
        echo "</td>";

        echo "<td>" . __('Block', 'metademands') . "</td>";
        echo "<td>";
        $randRank = Dropdown::showNumber('rank', [
            'value' => $this->fields["rank"],
            'min' => 1,
            'max' => self::MAX_FIELDS
        ]);
        $paramsRank = [
            'rank' => '__VALUE__',
            'step' => 'order',
            'fields_id' => $this->fields['id'],
            'metademands_id' => $this->fields['plugin_metademands_metademands_id'],
            'previous_fields_id' => $this->fields['plugin_metademands_fields_id']
        ];
        Ajax::updateItemOnSelectEvent(
            'dropdown_rank' . $randRank,
            "show_order",
            PLUGIN_METADEMANDS_WEBDIR .
            "/ajax/viewtypefields.php?id=" . $this->fields['id'],
            $paramsRank
        );
        echo "</td>";

        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        // ITEM
        //Display for dropdown list
        echo "<td style='vertical-align: top;'>";
        if ($ID < 1) {
            echo "<span id='show_item_object' style='display:none'>";
            echo __('Object', 'metademands') . "<span style='color:red'>&nbsp;*&nbsp;</span>";
            echo "</span>";

            //Display to add a title
            echo "<span id='show_item_label_title' style='display:none'>";

            echo "</span>";
        } else {
            echo __('Object', 'metademands');
        }
        echo "</td>";
        echo "<td>";
        if ($ID < 1) {
            echo "<span id='show_item' >";
            $randItem = self::dropdownFieldItems($this->fields["type"], ['value' => $this->fields["item"]]);
            echo "</span>";
            $paramsType = [
                'value' => '__VALUE__',
                'type' => '__VALUE__',
                'item' => $this->fields['item'],
                'step' => 'object',
                'rand' => $randItem,
                'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                'change_type' => 1
            ];
            Ajax::updateItemOnSelectEvent(
                'dropdown_type' . $randType,
                "show_item",
                PLUGIN_METADEMANDS_WEBDIR .
                "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                $paramsType
            );

            echo "<span id='show_item_title' style='display:none'>";
        } else {
            if ($this->fields["type"] == "dropdown_meta") {
                $metademand_custom = new PluginMetademandsFieldCustomvalue();
                if ($customs = $metademand_custom->find(["plugin_metademands_fields_id" => $this->fields['id']])) {
                    if (count($customs) > 0) {
                        echo self::getFieldItemsName($this->fields['type'], 'other');
                        echo Html::hidden('item', ['value' => 'other']);
                    }
                } else {
                    echo self::getFieldItemsName($this->fields['type'], $this->fields['item']);
                    echo Html::hidden('item', ['value' => $this->fields['item']]);
                }
            } elseif (in_array($this->fields["type"], self::$field_dropdown_types)) {
                echo "<span id='show_item' >";
                $randItem = self::dropdownFieldItems($this->fields["type"], ['value' => $this->fields["item"]]);
                echo "</span>";
                $paramsType = [
                    'value' => '__VALUE__',
                    'type' => '__VALUE__',
                    'item' => $this->fields['item'],
                    'step' => 'object',
                    'rand' => $randItem,
                    'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                    'change_type' => 1
                ];
                Ajax::updateItemOnSelectEvent(
                    'dropdown_type' . $randType,
                    "show_item",
                    PLUGIN_METADEMANDS_WEBDIR .
                    "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                    $paramsType
                );

                echo "<span id='show_item_title' style='display:none'>";
            } elseif ($this->fields["type"] == "dropdown_multiple") {
                if ($this->fields["type"] == "dropdown_multiple" && $this->fields["item"] == "other") {
                    echo self::getFieldItemsName($this->fields['type'], $this->fields['item']);
                    echo Html::hidden('item', ['value' => isset($this->fields['item']) ? $this->fields['item'] : null]);
                } else {
                    echo "<span id='show_item' >";
                    $randItem = self::dropdownFieldItems($this->fields["type"], [
                        'value' => $this->fields["item"],
                        'criteria' => PluginMetademandsDropdownmultiple::$dropdown_multiple_items
                    ]);
                    echo "</span>";
                    $paramsType = [
                        'value' => '__VALUE__',
                        'type' => '__VALUE__',
                        'item' => $this->fields['item'],
                        'step' => 'object',
                        'rand' => $randItem,
                        'metademands_id' => $this->fields["plugin_metademands_metademands_id"],
                        'change_type' => 1
                    ];
                    Ajax::updateItemOnSelectEvent(
                        'dropdown_type' . $randType,
                        "show_item",
                        PLUGIN_METADEMANDS_WEBDIR .
                        "/ajax/viewtypefields.php?id=" . $this->fields['id'],
                        $paramsType
                    );

                    echo "<span id='show_item_title' style='display:none'>";
                }
            } else {
                echo self::getFieldItemsName($this->fields['type'], $this->fields['item']);
                echo Html::hidden('item', ['value' => isset($this->fields['item']) ? $this->fields['item'] : null]);
            }
        }

        // ORDER
        if ($this->fields['type'] != "title-block") {
            echo "<td>" . __('Display field after', 'metademands') . "</td>";
            echo "<td>";
            echo "<span id='show_order'>";
            $this->showOrderDropdown(
                $this->fields['rank'],
                $this->fields['id'],
                $this->fields['plugin_metademands_fields_id'],
                $this->fields["plugin_metademands_metademands_id"]
            );
            echo "</span>";
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }


        echo "</tr>";

        if ($ID > 0) {
            $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
            $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;


            if (isset($field->fields['type'])
                && (in_array($field->fields['type'], $allowed_customvalues_types)
                    || in_array($field->fields['item'], $allowed_customvalues_items))
                && !in_array($this->fields["item"], self::$field_specificobjects)
                && $field->fields['item'] != "Appliance"
                && $field->fields['item'] != "Group") {
                $field_custom = new PluginMetademandsFieldCustomvalue();
                if (!$field_custom->find(["plugin_metademands_fields_id" => $this->getID()])) {
                    echo "<div class='alert alert-important alert-warning d-flex'>";
                    echo "<b>" . __(
                            'Warning : there is no custom values for this object',
                            'metademands'
                        ) . "</b></div>";
                }
            }
        }

        $this->showFormButtons(['colspan' => 2]);
        return true;
    }

    /**
     * @param $plugin_metademands_metademands_id
     * @param $canedit
     *
     * @throws \GlpitestSQLError
     */
    private static function listFields($item)
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        $rand = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        if ($canedit) {
            echo "<div id='viewfield" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addField" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = [
                'type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id' => -1
            ];
            Ajax::updateItemJsCode(
                "viewfield" . $item->getType() . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
                "<a class='submit btn btn-primary' href='javascript:addField" .
                $item->getType() . $item->getID() . "$rand();'>" . __('Add a new field', 'metademands') .
                "</a>&nbsp;";

            echo "<a class='submit btn btn-primary' href='javascript:addExistingField" .
                $item->getType() . $item->getID() . "$rand();'>" . __('Add a existing field', 'metademands') .
                "</a></div><br>";

            echo "<div id='viewexistingfield" . $item->getType() . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addExistingField" . $item->getType() . $item->getID() . "$rand() {\n";
            $params = [
                'type' => __CLASS__,
                'parenttype' => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id' => -1
            ];
            Ajax::updateItemJsCode(
                "viewexistingfield" . $item->getType() . $item->getID() . "$rand",
                PLUGIN_METADEMANDS_WEBDIR . "/ajax/viewexistingsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
        }

        $cond['plugin_metademands_metademands_id'] = $item->getID();

        if (isset($_SESSION['plugin_metademands_searchresults'][$item->getID()]['block'])
            && $_SESSION['plugin_metademands_searchresults'][$item->getID()]['block'] != 0) {
            $cond['rank'] = $_SESSION['plugin_metademands_searchresults'][$item->getID()]['block'];
        }
        if (isset($_SESSION['plugin_metademands_searchresults'][$item->getID()]['type'])
            && $_SESSION['plugin_metademands_searchresults'][$item->getID()]['type'] != 0) {
            $cond['type'] = $_SESSION['plugin_metademands_searchresults'][$item->getID()]['type'];
        }
        if (isset($_SESSION['plugin_metademands_searchresults'][$item->getID()]['item'])
            && $_SESSION['plugin_metademands_searchresults'][$item->getID()]['item'] != 0) {
            $cond['item'] = $_SESSION['plugin_metademands_searchresults'][$item->getID()]['item'];
        }

        self::searchForm($item, $cond);

        echo Html::scriptBlock(
            '
           
           $(document).ready(function () {
                var hash = window.location.hash;
            
                function updateActiveTab(rank) {
                    document.querySelectorAll("a[id^=\"ablock\"]").forEach(a => a.classList.remove("active"));
                    document.querySelectorAll("div[id^=\"block\"]").forEach(div => div.classList.remove("active"));
            
                    document.getElementById("ablock" + rank)?.classList.add("active");
                    $("div[id^=\"block\"]").hide();
                    $("#block" + rank).show();
                    
                    window.location.hash = "#block" + rank; // Mise à jour manuelle
                }
                
                var fieldid = sessionStorage.getItem("loadedblock");
                if (typeof fieldid !== "undefined" && fieldid != null && fieldid.length > 0) {
                     updateActiveTab(fieldid.substr(5));
                }
                if (hash.startsWith("#block")) {
                    updateActiveTab(hash.replace("#block", ""));
                } else {
                    updateActiveTab(1); // Sélectionne par défaut block1
                }
            
                $("#fieldslist a").click(function (e) {
                    e.preventDefault();
                    var tabId = $(this).attr("href").replace("#", "");
                    sessionStorage.setItem("loadedblock", tabId);
                    updateActiveTab(tabId.replace("block", ""));
                });
            
                $("ul.nav-tabs > li > a").on("shown.bs.tab", function (e) {
                    var id = $(e.target).attr("href").substr(1);
                    window.location.hash = id;
                });
            });
            

            '
        );

        $self = new self();

        $data = $self->find(
            $cond,
            ['rank', 'order']
        );

        $fieldparameter = new PluginMetademandsFieldParameter();

        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        $new_types = [];
        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginDropdownFieldItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    foreach ($new_fields as $plugin) {
                        foreach ($plugin as $k => $field) {
                            $new_types[] = $k;
                        }
                    }
                }
            }
        }

        $koparams = 0;
        $kocustom = 0;

        $blocks = [];
        $block_fields = [];


        foreach ($data as $value) {
            $blocks[$value['rank']] = __('Block', 'metademands') . " " . $value['rank'];
        }
        if (count($blocks) > 0) {
            echo "<div class='tabs-container'>";
            echo "<button class='scroll-btn scroll-left'><i class='fas fa-chevron-left'></i></button>";
            echo "<div class='d-flex flex-nowrap border-bottom scrollable-tabs'>";
            echo "<ul class='nav nav-tabs flex-nowrap' role='tablist' id='fieldslist'>";
            foreach ($blocks as $idblock => $block) {
                $nameblock = $block;
                echo "<li class='nav-item'>";
                echo "<a class='nav-link tablinks' id='ablock$idblock' href='#block".$idblock."' data-toggle='tab'>".$nameblock."</a>";
                echo "</li>";
            }
            echo "</ul>";
            echo "</div>";
            echo "<button class='scroll-btn scroll-right'><i class='fas fa-chevron-right'></i></button>";
            echo "</div>";

            echo Html::scriptBlock('
                setTimeout(() => {
                    const scrollContainer = document.querySelector(".scrollable-tabs");
                    const scrollLeftBtn = document.querySelector(".scroll-left");
                    const scrollRightBtn = document.querySelector(".scroll-right");
                
                    if (scrollLeftBtn && scrollRightBtn && scrollContainer) {
                        scrollLeftBtn.addEventListener("click", function () {
                            scrollContainer.scrollBy({ left: -150, behavior: "smooth" });
                        });
                
                        scrollRightBtn.addEventListener("click", function () {
                            scrollContainer.scrollBy({ left: 150, behavior: "smooth" });
                        });
                    }
                }, 500);
            ');

            foreach ($blocks as $idblock => $block) {
                foreach ($data as $value) {
                    if ($idblock == $value['rank']) {
                        $block_fields[$idblock][] = $value;
                    }
                }
            }
        }

        foreach ($block_fields as $idblock => $data) {
            $rand = mt_rand();
            $defaultblock = "";
            if ($idblock == 1) {
                $defaultblock = "active";
            }
            echo "<div id='block$idblock' class='tabfieldcontent $defaultblock'>";

            $orders = [];
            foreach ($data as $value) {
                $orders[] = $value['order'];
            }

            if (!isset($_SESSION['plugin_metademands_searchresults'][$value['plugin_metademands_metademands_id']]['block'])
                && self::isSequentialFromOne($orders) == false) {
                echo "<div class='alert alert-warning flex'>";
                echo "<div class='left'>";
                echo "<i class='fas fa-exclamation-triangle fa-2x' style='color: orange;'></i>&nbsp;" . __(
                        'The fields are not ordered correctly, you will not be able to order them!',
                        'metademands'
                    );
                echo "<br><br>";
                echo _x('button', 'Do you want to fix them ?', 'metademands');
                echo "</div>";
                echo "<div class='right'>";
                $target = self::getFormURL();
                Html::showSimpleForm(
                    $target,
                    'fixorders',
                    _x('button', 'Do you want to fix them ?', 'metademands'),
                    [
                        'plugin_metademands_metademands_id' => $value['plugin_metademands_metademands_id'],
                        'rank' => $value['rank'],
                    ],
                    'fa-wrench',
                    "class='btn btn-warning'"
                );
                echo "</div>";
                echo "</div>";
            }

            foreach ($data as $value) {
                if (!$fieldparameter->find(["plugin_metademands_fields_id" => $value['id']])) {
                    $koparams++;
                }

                if (isset($value['type'])
                    && (in_array(
                            $value['type'],
                            $allowed_customvalues_types
                        ) && ($value['item'] != "ITILCategory_Metademands"
                            && !in_array($value["item"], self::$field_specificobjects))
                        && !in_array($value['item'], $new_types))
                    || (in_array(
                            $value['item'],
                            $allowed_customvalues_items
                        ) && $value['item'] != 'Appliance' && $value['item'] != 'Group')) {
                    $field_custom = new PluginMetademandsFieldCustomvalue();
                    if (!$field_custom->find(["plugin_metademands_fields_id" => $value['id']])) {
                        $kocustom++;
                    }
                }
            }
            if ($koparams > 0) {
                echo "<div class='alert alert-important alert-warning d-flex'>";
                echo "<b>" . __(
                        'Warning : there are fields without parameters, please check',
                        'metademands'
                    ) . "</b></div>";
            }
            if ($kocustom > 0) {
                echo "<div class='alert alert-important alert-warning d-flex'>";
                echo "<b>" . __(
                        'Warning : there are fields without custom values, please check',
                        'metademands'
                    ) . "</b></div>";
            }

            $fieldopt = new PluginMetademandsFieldOption();

            if (is_array($data) && count($data) > 0) {
                if ($canedit) {
                    Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                    $massiveactionparams = [
                        'item' => __CLASS__,
                        'container' => 'mass' . __CLASS__ . $rand
                    ];
                    Html::showMassiveActions($massiveactionparams);
                }
                echo "<div id='drag$rand'>";
                echo "<table class='tab_cadre_fixehov'>";
//                echo "<tr class='tab_bg_2'>";
//                echo "<th class='center b' colspan='12'>" . __('Form fields', 'metademands') . " ".$blocks[$idblock]."</th>";
//                echo "</tr>";
                echo "<tr>";
                if ($canedit) {
                    echo "<th width='10'>";
                    echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                    echo "</th>";
                }
                echo "<th class='center b'></th>";
                echo "<th class='center b'>" . __('ID') . "</th>";
                echo "<th class='center b'>" . __('Label') . "</th>";
                echo "<th class='center b'>" . __('Type') . " / ".__('Object', 'metademands')."</th>";
                echo "<th class='center b'>" . __('Mandatory', 'metademands') . "</th>";
                echo "<th class='center b'>" . __('Value to check', 'metademands') . "</th>";
                if ($item->fields['is_order'] == 1) {
                    echo "<th class='center b'>" . __('Display into the basket', 'metademands') . "</th>";
                }
                echo "<th class='center b'>" . __('Use this field as object field', 'metademands') . "</th>";
                echo "<th class='center b'>" . __('Launch a task with the field', 'metademands') . "</th>";
                echo "<th class='center b' style='width: 70px;'>" . __('Actions', 'metademands') . "</th>";
                echo "</tr>";

                // Init navigation list for field items
                Session::initNavigateListItems($self->getType(), self::getTypeName(1));

                $new_types = [];
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            foreach ($new_fields as $plugin) {
                                foreach ($plugin as $k => $field) {
                                    $new_types[] = $k;
                                }
                            }
                        }
                    }
                }

                foreach ($data as $value) {
                    Session::addToNavigateListItems($self->getType(), $value['id']);

                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td class='rowhandler control center'>";
//                        echo "<div class=\"drag\">";
                        Html::showMassiveActionCheckBox(__CLASS__, $value["id"]);
//                        echo "</div>";
                        echo "</td>";
                    }

                    echo "<td>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    if (!$fieldparameter->find(["plugin_metademands_fields_id" => $value['id']]
                        ) || ((isset($value['type'])
                                && (in_array(
                                        $value['type'],
                                        $allowed_customvalues_types
                                    ) && ($value['item'] != "ITILCategory_Metademands"
                                        && !in_array($value["item"], self::$field_specificobjects))
                                    && !in_array($value['item'], $new_types))
                                || (in_array(
                                        $value['item'],
                                        $allowed_customvalues_items
                                    ) && $value['item'] != 'Appliance' && $value['item'] != 'Group')
                            )
                            && !$field_custom->find(["plugin_metademands_fields_id" => $value['id']]))) {
                        echo "<i class='fa fa-warning fa-1x' style='color: orange;'></i>";
                    }
                    echo "</div>";
                    echo "</td>";

                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    echo $value['id'];
                    echo "</div>";
                    echo "</td>";

                    $name = "";
                    if (isset($value['name'])) {
                        $name = $value['name'];
                    }

                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    echo " <a href='" . Toolbox::getItemTypeFormURL(__CLASS__) . "?id=" . $value['id'] . "'>";
                    if (empty(trim($name))) {
                        echo __('ID') . " - " . $value['id'];
                    } else {
                        echo Toolbox::stripslashes_deep($name);
                    }
                    echo "</a>";
                    echo "</div>";
                    echo "</td>";

                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    echo self::getFieldTypesName($value['type']);
                    //name of parent field
                    if ($value['type'] == 'parent_field') {
                        if ($fieldopt->getFromDBByCrit(["plugin_metademands_fields_id" => $value['id']])) {
                            $field = new self();
                            if ($field->getFromDB($fieldopt->fields['parent_field_id'])) {
                                if (empty(trim($field->fields['name']))) {
                                    echo " ( ID - " . $value['parent_field_id'] . ")";
                                } else {
                                    echo " (" . $field->fields['name'] . ")";
                                }
                            }
                        }
                    }

                    $itemtypename = self::getFieldItemsName($value['type'], $value['item']);
                    if ($itemtypename != Dropdown::EMPTY_VALUE) {
                        echo " (";
                        echo $itemtypename;
                        echo ")";
                    }
                    echo "</td>";


                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $value['id']])) {
                        if ($fieldparameter->fields['is_mandatory'] == 1) {
                            echo "<span class='red'>";
                        }
                        echo Dropdown::getYesNo($fieldparameter->fields['is_mandatory']);
                        if ($fieldparameter->fields['is_mandatory'] == 1) {
                            echo "</span>";
                        }
                    }
                    echo "</div>";
                    echo "</td>";


                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    $fieldopt = new PluginMetademandsFieldOption();
                    if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $value['id']])) {
                        $nbopts = count($opts);
                        if ($nbopts > 1) {
                            echo __('Multiples', 'metademands');
                        } else {
                            foreach ($opts as $opt) {
                                $datao['item'] = $value['item'];
                                $datao['type'] = $value['type'];
                                $datao['id'] = $value['id'];
                                $datao['check_value'] = $opt['check_value'];
                                $datao['parent_field_id'] = $opt['parent_field_id'];

                                $metademand_custom = new PluginMetademandsFieldCustomvalue();
                                $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
                                $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

                                if (isset($value['type'])
                                    && in_array($value['type'], $allowed_customvalues_types)
                                    || in_array($value['item'], $allowed_customvalues_items)) {
                                    $datao['custom_values'] = [];
                                    if ($customs = $metademand_custom->find(
                                        ["plugin_metademands_fields_id" => $value['id']],
                                        "rank"
                                    )) {
                                        if (count($customs) > 0) {
                                            $datao['custom_values'] = $customs;
                                        }
                                    }
                                } else {
                                    $datao['custom_values'] = $value['custom_values'] ?? [];
                                }

                                echo PluginMetademandsFieldOption::getValueToCheck($datao);
                            }
                        }
                    } else {
                        echo Dropdown::EMPTY_VALUE;
                    }
                    echo "</div>";
                    echo "</td>";
                    if ($item->fields['is_order'] == 1) {
                        echo "<td class='rowhandler control center'>";
                        echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                        echo Dropdown::getYesNo($fieldparameter->fields['is_basket']) . "</td>";
                        echo "</div>";
                    }
                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    $searchOption = Search::getOptions('Ticket');
                    if ($fieldparameter->getFromDBByCrit(['plugin_metademands_fields_id' => $value['id']])) {
                        if ($fieldparameter->fields['used_by_ticket']
                            && $value['type'] !== 'text'
                            && $value['type'] !== 'email'
                            && $value['type'] !== 'tel'
                            && $value['type'] !== 'url') {
                            echo $searchOption[$fieldparameter->fields['used_by_ticket']]['name'];
                        } else {
                            echo Dropdown::EMPTY_VALUE;
                        }
                    } else {
                        echo Dropdown::EMPTY_VALUE;
                    }
                    echo "</div>";
                    echo "</td>";

                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;
border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    $fieldopt = new PluginMetademandsFieldOption();
                    if ($opts = $fieldopt->find(["plugin_metademands_fields_id" => $value['id']])) {
                        foreach ($opts as $opt) {
                            $tasks = [];
                            if (!empty($opt['plugin_metademands_tasks_id'])) {
                                $tasks[] = $opt['plugin_metademands_tasks_id'];
                            }
                            if (is_array($tasks)) {
                                if (count($tasks) > 0) {
                                    foreach ($tasks as $k => $task) {
                                        $metatask = new PluginMetademandsTask();
                                        if ($metatask->getFromDB($task)) {
                                            if ($metatask->fields['type'] == PluginMetademandsTask::METADEMAND_TYPE) {
                                                $metachildtask = new PluginMetademandsMetademandTask();
                                                if ($metachildtask->getFromDBByCrit(["plugin_metademands_tasks_id" => $task]
                                                )) {
                                                    echo Dropdown::getDropdownName(
                                                        'glpi_plugin_metademands_metademands',
                                                        $metachildtask->fields['plugin_metademands_metademands_id']
                                                    );
                                                }
                                            } else {
                                                echo $metatask->getName();
                                            }
                                            echo "<br>";
                                        }
                                    }
                                } else {
                                    echo Dropdown::EMPTY_VALUE;
                                }
                            }
                        }
                    } else {
                        echo Dropdown::EMPTY_VALUE;
                    }
                    echo "</div>";
                    echo "</td>";

                    $form = self::getFormURL();
                    echo "<td class='rowhandler control center'>";
                    echo "<div class=\"drag row\" style=\"cursor: move;border-width: 0 !important;border-style: none !important; border-color: initial !important;border-image: initial !important;\">";
                    $debug = (isset($_SESSION['glpi_use_mode'])
                    && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
                    if ($debug) {
                        echo $value['order'];
                    }
                    echo "<i class=\"fas fa-grip-horizontal grip-rule\"></i>";
                    echo "</div>";
                    echo "&nbsp;";
                    echo Html::getSimpleForm(
                        $form,
                        'purge',
                        "",
                        ["id" => $value['id'],
                            "plugin_metademands_metademands_id" => $value['plugin_metademands_metademands_id']],
                        "fa-times-circle fa-1x",
                        "",
                        __('Are you sure you want to delete this field ?', 'metademands')
                    );
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "</div>";

                if ($canedit && count($data)) {
                    $massiveactionparams['ontop'] = false;
                    Html::showMassiveActions($massiveactionparams);
                    Html::closeForm();
                }

                $plugin_metademands_metademands_id = $value['plugin_metademands_metademands_id'];
                echo "<script type='text/javascript' >\n";
                echo "$(document).ready(function() {
                plugin_metademands_orderredipsInit($rand, $plugin_metademands_metademands_id )});";
                echo "\n</script>";

            } else {
                echo "<div class='center first-bloc'>";
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr  class='tab_bg_1'><td class='center'>" . __('No item to display') . "</td></tr>";
                echo "</table>";
                echo "</div>";
            }

            echo "</div>";
        }
    }

    /**
     * Show field types dropdown
     *
     * @param type $name
     * @param array $param
     *
     * @return dropdown of types
     * @throws \GlpitestSQLError
     */
    public static function dropdownFieldTypes($type_fields, $param = [])
    {
        global $PLUGIN_HOOKS;

        $name = "type";
        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        if (isset($PLUGIN_HOOKS['metademands'])) {
            foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                $new_fields = self::addPluginTextFieldItems($plug);
                if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                    $type_fields = array_merge($type_fields, $new_fields);
                }
            }
        }

        foreach ($type_fields as $key => $types) {
            //delete type parent_field if no parent metademand & not field
            if ($types == 'parent_field') {
                $metademands_parent = PluginMetademandsMetademandTask::getAncestorOfMetademandTask(
                    $p['metademands_id']
                );
                $list_fields = [];
                $field = new self();
                foreach ($metademands_parent as $parent_id) {
                    $condition = [
                        'plugin_metademands_metademands_id' => $parent_id,
                        ['NOT' => ['type' => ['parent_field', 'upload']]]
                    ];
                    $datas_fields = $field->find($condition, ['rank', 'order']);
                    foreach ($datas_fields as $data_field) {
                        $list_fields[$data_field['id']] = $data_field['name'];
                    }
                }

                if (count($metademands_parent) == 0) {
                    continue;
                } elseif (count($list_fields) == 0) {
                    continue;
                }
            }
            if (empty($types)) {
                $options[$key] = self::getFieldTypesName($types);
            } else {
                $options[$types] = self::getFieldTypesName($types);
            }
        }

        return Dropdown::showFromArray($name, $options, $p);
    }

    /**
     * get field types name
     *
     * @param string $value
     *
     * @return string types
     */
    public static function getFieldTypesName($value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($value) {
            case 'title':
                return PluginMetademandsTitle::getTypeName();
            case 'title-block':
                return PluginMetademandsTitleblock::getTypeName();
            case 'informations':
                return PluginMetademandsInformation::getTypeName();
            case 'text':
                return PluginMetademandsText::getTypeName();
            case 'tel':
                return PluginMetademandsTel::getTypeName();
            case 'email':
                return PluginMetademandsEmail::getTypeName();
            case 'url':
                return PluginMetademandsUrl::getTypeName();
            case 'textarea':
                return PluginMetademandsTextarea::getTypeName();
            case 'dropdown_meta':
                return PluginMetademandsDropdownmeta::getTypeName();
            case 'dropdown_object':
                return PluginMetademandsDropdownobject::getTypeName();
            case 'dropdown':
                return PluginMetademandsDropdown::getTypeName();
            case 'dropdown_multiple':
                return PluginMetademandsDropdownmultiple::getTypeName();
            case 'checkbox':
                return PluginMetademandsCheckbox::getTypeName();
            case 'radio':
                return PluginMetademandsRadio::getTypeName();
            case 'yesno':
                return PluginMetademandsYesno::getTypeName();
            case 'number':
                return PluginMetademandsNumber::getTypeName();
            case 'range':
                return PluginMetademandsRange::getTypeName();
            case 'freetable':
                return PluginMetademandsFreetable::getTypeName();
            case 'date':
                return PluginMetademandsDate::getTypeName();
            case 'time':
                return PluginMetademandsTime::getTypeName();
            case 'datetime':
                return PluginMetademandsDatetime::getTypeName();
            case 'date_interval':
                return PluginMetademandsDateinterval::getTypeName();
            case 'datetime_interval':
                return PluginMetademandsDatetimeInterval::getTypeName();
            case 'upload':
                return PluginMetademandsUpload::getTypeName();
            case 'link':
                return PluginMetademandsLink::getTypeName();
            case 'basket':
                return PluginMetademandsBasket::getTypeName();
            case 'signature':
                return PluginMetademandsSignature::getTypeName();
            case 'parent_field':
                return __('Father\'s field', 'metademands');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldTypesName($plug);

                        if (Plugin::isPluginActive($plug)
                            && is_array($new_fields)) {
                            if (isset($new_fields[$value])) {
                                return $new_fields[$value];
                            } else {
                                continue;
                            }
                            return Dropdown::EMPTY_VALUE;
                        }
                    }
                }
                return Dropdown::EMPTY_VALUE;
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginCaseCustomFields($plug, $name, $p)
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
                if ($item && is_callable([$item, 'addCaseCustomFields'])) {
                    return $item->addCaseCustomFields($name, $p);
                }
            }
        }
    }

    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function showPluginFieldCase(
        $plug,
        $metademands_data,
        $data,
        $on_order = false,
        $itilcategories_id = 0,
        $idline = 0
    ) {
        global $PLUGIN_HOOKS;

        $dbu = new DbUtils();

        if (isset($PLUGIN_HOOKS['metademands'][$plug])) {
            $pluginclasses = $PLUGIN_HOOKS['metademands'][$plug];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    continue;
                }

                $item = $dbu->getItemForItemtype($pluginclass);
                if ($item && is_callable([$item, 'showFieldCase'])) {
                    echo $item->showFieldCase(
                        $metademands_data,
                        $data,
                        $on_order = false,
                        $itilcategories_id = 0,
                        $idline = 0
                    );
                }
            }
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function addPluginFieldItems($plug)
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
                if ($item && is_callable([$item, 'addFieldItems'])) {
                    return $item->addFieldItems();
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
    public static function getPluginFieldTypesName($plug)
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
                if ($item && is_callable([$item, 'getFieldTypesName'])) {
                    return $item->getFieldTypesName();
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

    /**
     * Show field item dropdown
     *
     * @param type $name
     * @param array $param
     *
     * @return dropdown of items
     */
    public static function dropdownFieldItems($typefield, $param = [])
    {
        global $PLUGIN_HOOKS;

        $p = [];
        foreach ($param as $key => $val) {
            $p[$key] = $val;
        }

        $name = "item";

        $type_fields = PluginMetademandsDropdownmeta::$dropdown_meta_items;
        $type_fields_multiple = PluginMetademandsDropdownmultiple::$dropdown_multiple_items;
        if (isset($p["criteria"])) {
            $type_fields_multiple = $p["criteria"];
        }

        switch ($typefield) {
            case "dropdown_multiple":
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $options[0] = Dropdown::EMPTY_VALUE;
                }

                foreach ($type_fields_multiple as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName("dropdown_multiple", $items);
                    } else {
                        $options[$items] = self::getFieldItemsName("dropdown_multiple", $items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownMultipleFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown":
                $options = Dropdown::getStandardDropdownItemTypes();
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $allowedDropdownValues[0] = Dropdown::EMPTY_VALUE;
                    $options = array_merge($allowedDropdownValues, $options);
                }

                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown_meta":
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $options[0] = Dropdown::EMPTY_VALUE;
                }
                foreach ($type_fields as $key => $items) {
                    if (empty($items)) {
                        $options[$key] = self::getFieldItemsName("dropdown_meta", $items);
                    } else {
                        $options[$items] = self::getFieldItemsName("dropdown_meta", $items);
                    }
                }
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::addPluginDropdownFieldItems($plug);
                        if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                            $options = array_merge_recursive($options, $new_fields);
                        }
                    }
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "dropdown_object":
                $options = self::getGlpiObject();
                if (isset($p["with_empty_value"])
                    && $p["with_empty_value"] == true) {
                    $allowedDropdownValues[0] = Dropdown::EMPTY_VALUE;
                    $options = array_merge($allowedDropdownValues, $options);
                }
                return Dropdown::showFromArray($name, $options, $p);
                break;
            case "basket":
                $options = new PluginMetademandsBasketobjecttype();
                return $options->Dropdown(["name" => $name, 'value' => $p['value']]);
                break;
            default :

                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $newcase = self::addPluginCaseCustomFields($plug, $name, $p);
                    }
                    return $newcase;
                }
                break;
        }
    }

    /**
     * get field items name
     *
     * @param string $value
     *
     * @return string item
     */
    public static function getFieldItemsName($type = '', $value = '')
    {
        global $PLUGIN_HOOKS;

        switch ($type) {
            case 'basket':
                $basketobject = new PluginMetademandsBasketobjecttype();
                $name = Dropdown::EMPTY_VALUE;
                if ($basketobject->getFromDB($value)) {
                    $name = $basketobject->getName();
                }
                return $name;
        }

        switch ($value) {
            case 'other':
                return __('My values', 'metademands');
            case 'ITILCategory_Metademands':
                return __('Category of the metademand', 'metademands');
            case 'mydevices':
                return __('My devices');
            case 'urgency':
                return __('Urgency');
            case 'impact':
                return __('Impact');
            case 'priority':
                return __('Priority');
            case 'user':
                return __('User');
            case 'appliance':
                return __('Appliance');
            default:
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                        $new_fields = self::getPluginFieldItemsName($plug);
                        if (Plugin::isPluginActive($plug)
                            && is_array($new_fields)) {
                            if (isset($new_fields[$value])) {
                                return $new_fields[$value];
                            } else {
                                continue;
                            }
                            return Dropdown::EMPTY_VALUE;
                        }
                    }
                }
                $dbu = new DbUtils();
                if (!is_numeric($value)) {
                    if ($value != null && $item = $dbu->getItemForItemtype($value)) {
                        if (is_callable([$item, 'getTypeName'])) {
                            return $item::getTypeName();
                        }
                    }
                }
                return Dropdown::EMPTY_VALUE;
        }
    }


    /**
     * Load fields from plugins
     *
     * @param $plug
     */
    public static function getPluginFieldItemsType($plug)
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
                if ($item && is_callable([$item, 'getFieldItemsType'])) {
                    return $item->getFieldItemsType();
                }
            }
        }
    }


    public static function getAllParamsFromField($field)
    {
        $metademand = new PluginMetademandsMetademand();
        $metademand_params = new PluginMetademandsFieldParameter();
        $field_custom = new PluginMetademandsFieldCustomvalue();
        $freetablefield = new PluginMetademandsFreetablefield();

        $metademand_params->getFromDBByCrit(
            ["plugin_metademands_fields_id" => $field->getID()]
        );
        $metademand->getFromDB($field->fields['plugin_metademands_metademands_id']);

        $default_values = [];
        if (isset($metademand_params->fields['default'])) {
            $default_values = PluginMetademandsFieldParameter::_unserialize($metademand_params->fields['default']);
        }

        $custom_values = [];
        if (isset($metademand_params->fields['custom'])) {
            $custom_values = PluginMetademandsFieldParameter::_unserialize($metademand_params->fields['custom']);
        }

        $allowed_customvalues_types = PluginMetademandsFieldCustomvalue::$allowed_customvalues_types;
        $allowed_customvalues_items = PluginMetademandsFieldCustomvalue::$allowed_customvalues_items;

        if (isset($field->fields['type'])
            && (in_array($field->fields['type'], $allowed_customvalues_types)
                || in_array($field->fields['item'], $allowed_customvalues_items))
            && !in_array($field->fields["item"], self::$field_specificobjects)
            && $field->fields['item'] != "Appliance"
            && $field->fields['item'] != "Group") {
            $custom_values = [];
            if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $field->getID()], "rank")) {
                if (count($customs) > 0) {
                    $custom_values = $customs;
                }
                $default_values = [];
            }
        }

        if (isset($field->fields['type'])
            && $field->fields['type'] == "freetable") {
            $custom_values = [];
            if ($customs = $freetablefield->find(["plugin_metademands_fields_id" => $field->getID()], "rank")) {
                if (count($customs) > 0) {
                    $custom_values = $customs;
                }
                $default_values = [];
            }
        }

        $params = [
            'id' => $field->fields['id'],
            'object_to_create' => $metademand->fields['object_to_create'],
            'is_order' => $metademand->fields['is_order'],
            'name' => $field->fields['name'],
            'comment' => $field->fields['comment'],
            'label2' => $field->fields['label2'],
            'rank' => $field->fields['rank'],
            'plugin_metademands_metademands_id' => $field->fields["plugin_metademands_metademands_id"],
            'plugin_metademands_fields_id' => $field->getID(),
            'item' => $field->fields['item'],
            'type' => $field->fields['type'],
            'row_display' => $metademand_params->fields['row_display'] ?? 0,
            'hide_title' => $metademand_params->fields['hide_title'] ?? 0,
            'is_basket' => $metademand_params->fields['is_basket'] ?? 0,
            'color' => $metademand_params->fields['color'] ?? "",
            'icon' => $metademand_params->fields['icon'] ?? "",
            'is_mandatory' => $metademand_params->fields['is_mandatory'] ?? 0,
            'used_by_ticket' => $metademand_params->fields['used_by_ticket'] ?? 0,
            'used_by_child' => $metademand_params->fields['used_by_child'] ?? 0,
            'use_richtext' => $metademand_params->fields['use_richtext'] ?? 0,
            'default_use_id_requester' => $metademand_params->fields['default_use_id_requester'] ?? 0,
            'default_use_id_requester_supervisor' => $metademand_params->fields['default_use_id_requester_supervisor'] ?? 0,
            'readonly' => $metademand_params->fields['readonly'] ?? 0,
            'max_upload' => $metademand_params->fields['max_upload'] ?? 0,
            'regex' => $metademand_params->fields['regex'] ?? 0,
            'use_future_date' => $metademand_params->fields['use_future_date'] ?? 0,
            'use_date_now' => $metademand_params->fields['use_date_now'] ?? 0,
            'additional_number_day' => $metademand_params->fields['additional_number_day'] ?? 0,
            'display_type' => $metademand_params->fields['display_type'] ?? 0,
            'informations_to_display' => $metademand_params->fields['informations_to_display'] ?? ['fullname'],
            'link_to_user' => $metademand_params->fields["link_to_user"] ?? 0,
            'hidden' => $metademand_params->fields["hidden"] ?? 0,
            'custom_values' => $custom_values,
            'default_values' => $default_values,
        ];

        return $params;
    }

    /**
     * @param        $data
     * @param        $metademands_data
     * @param bool $preview
     * @param string $config_link
     * @param int $itilcategories_id
     */
    public static function displayFieldByType($metademands_data, $data, $preview = false, $itilcategories_id = 0)
    {
        global $PLUGIN_HOOKS;

        $config_link = "";
        if (Session::getCurrentInterface() == 'central' && $preview) {
            $config_link = "&nbsp;<a href='" . Toolbox::getItemTypeFormURL(
                    'PluginMetademandsField'
                ) . "?id=" . $data['id'] . "'>";
            $config_link .= "<i class='fas fa-wrench'></i></a>";
        }
        $debug = (isset($_SESSION['glpi_use_mode'])
        && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

        $required = "";
//        if ($data['is_mandatory'] == 1 && $data['type'] != 'parent_field') {
//            $required = "required=required style='color:red'";
//        }

        $upload = "";
        if ($data['type'] == "upload") {
            $max = "";
            if ($data["max_upload"] > 0) {
                $max = "( " . sprintf(
                        __("Maximum number of documents : %s ", "metademands"),
                        $data["max_upload"]
                    ) . ")";
            }

            $upload = "$max (" . Document::getMaxUploadSize() . ")";
        }
//        if ($data['is_mandatory'] == 1) {
//            $required = "style='color:red'";
//        }

        if (empty($label = self::displayField($data['id'], 'name'))) {
            $label = "";
            if (isset($data['name'])) {
                $label = $data['name'];
            }
        }

        $hidden = $data['hidden'] ?? 0;
        if ($hidden == 1 && isset($_SESSION['glpiactiveprofile']['interface'])
            && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $hidden = 0;
        }
        if ($data['type'] != "title"
            && $data['type'] != "title-block"
            && $data['type'] != "informations") {
            if (isset($data['hide_title']) && $data['hide_title'] == 0) {
                if ($hidden == 0) {
                    echo "<div $required class='col-form-label metademand-label'>";
                    echo Toolbox::stripslashes_deep($label) . " $upload";

                    if ($debug) {
                        echo " (ID:". $data['id'].")";
                    }
                    if ($preview) {
                        echo $config_link;
                    }

                    if (empty($comment = self::displayField($data['id'], 'comment'))) {
                        $comment = $data['comment'];
                    }
                    if ($data['type'] != "text"
                        && $data['type'] != "tel"
                        && $data['type'] != "email"
                        && $data['type'] != "url"
                        && !empty($comment)) {
                        $display = true;
                        if ($data['use_richtext'] == 0) {
                            $display = false;
                        }
                        if ($display) {
                            echo "&nbsp;";
                            echo Html::showToolTip(Glpi\RichText\RichText::getSafeHtml($comment), [
                                'awesome-class' => 'fa-info-circle',
                                'display' => false
                            ]);
                        }
                    }
                    echo "<span class='metademands_wizard_red' id='metademands_wizard_red" . $data['id'] . "'>";
                    if ($data['is_mandatory'] == 1
                        && $data['type'] != 'parent_field') {
                        echo "*";
                    }


                    //use plugin fields types
                    if (isset($PLUGIN_HOOKS['metademands'])) {
                        foreach ($PLUGIN_HOOKS['metademands'] as $plug => $method) {
                            $new_fields = self::getPluginFieldItemsType($plug);
                            if (Plugin::isPluginActive($plug) && is_array($new_fields)) {
                                if (in_array($data['type'], array_keys($new_fields))) {
                                    $data['type'] = $new_fields[$data['type']];
                                }
                            }
                        }
                    }

                    echo "</div>";
                }
            } else {
                echo "<div style='margin-top: 10px;'>";
                if ($preview) {
                    echo $config_link;
                }
            }
        }
        echo self::getFieldInput($metademands_data, $data, false, $itilcategories_id, 0, $preview, $config_link);

        if ($data['type'] != "title"
            && $data['type'] != "title-block"
            && $data['type'] != "informations") {
            if (isset($data['hide_title']) && $data['hide_title'] == 1) {
                echo "</div>";
            }
        }
    }


    /**
     * Generate the HTML to display a field
     * @param      $metademands_data
     * @param array $data row from DB with associated options, see PluginMetademandsMetademand->constructForm() for details
     * @param bool $on_order
     * @param int $itilcategories_id
     *
     * @param int $idline
     *
     * @return int|mixed|String
     */
    public static function getFieldInput(
        $metademands_data,
        $data,
        $on_order = false,
        $itilcategories_id = 0,
        $idline = 0,
        $preview = false,
        $config_link = ''
    ) {
        global $PLUGIN_HOOKS;

        $metademand = new PluginMetademandsMetademand();
        $metademand->getFromDB($data['plugin_metademands_metademands_id']);

        $field = '';
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }

        if ($on_order == false) {
            $namefield = 'field';
        } else {
            $namefield = 'field_basket_' . $idline;
        }

        switch ($data['type']) {
            case 'title':
                PluginMetademandsTitle::showWizardField($data, $namefield, $value, $on_order, $preview, $config_link);
                break;
            case 'title-block':
                PluginMetademandsTitleblock::showWizardField(
                    $data,
                    $namefield,
                    $value,
                    $on_order,
                    $preview,
                    $config_link
                );
                break;
            case 'informations':
                PluginMetademandsInformation::showWizardField(
                    $data,
                    $namefield,
                    $value,
                    $on_order,
                    $preview,
                    $config_link
                );
                break;
            case 'text':
                PluginMetademandsText::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'tel':
                PluginMetademandsTel::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'email':
                PluginMetademandsEmail::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'url':
                PluginMetademandsUrl::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'textarea':
                PluginMetademandsTextarea::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'dropdown_meta':
                PluginMetademandsDropdownmeta::showWizardField(
                    $data,
                    $namefield,
                    $value,
                    $on_order,
                    $itilcategories_id
                );
                break;
            case 'dropdown_object':
                PluginMetademandsDropdownobject::showWizardField(
                    $data,
                    $namefield,
                    $value,
                    $on_order,
                    $itilcategories_id
                );
                break;
            case 'dropdown':
                PluginMetademandsDropdown::showWizardField($data, $namefield, $value, $on_order, $itilcategories_id);
                break;
            case 'dropdown_multiple':
                PluginMetademandsDropdownmultiple::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'checkbox':
                PluginMetademandsCheckbox::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'radio':
                PluginMetademandsRadio::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'yesno':
                PluginMetademandsYesno::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'number':
                PluginMetademandsNumber::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'range':
                PluginMetademandsRange::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'freetable':
                PluginMetademandsFreetable::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'date':
                PluginMetademandsDate::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'time':
                PluginMetademandsTime::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'datetime':
                PluginMetademandsDatetime::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'date_interval':
                PluginMetademandsDateinterval::showWizardField($data, $namefield, $value, false);
                break;
            case 'datetime_interval':
                PluginMetademandsDatetimeinterval::showWizardField($data, $namefield, $value, false);
                break;
            case 'upload':
                PluginMetademandsUpload::showWizardField($data, $namefield, $value, $on_order, $idline);
                break;
            case 'link':
                PluginMetademandsLink::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'basket':
                PluginMetademandsBasket::showWizardField($data, $on_order, $itilcategories_id, $idline);
                break;
            case 'signature':
                PluginMetademandsSignature::showWizardField($data, $namefield, $value, $on_order);
                break;
            case 'parent_field':
                foreach ($metademands_data as $metademands_data_steps) {
                    foreach ($metademands_data_steps as $line_data) {
                        foreach ($line_data['form'] as $field_id => $field_value) {
                            if (isset($data['options'])) {
                                $opts = $data['options'];

                                if (isset($opts[0]['parent_field_id'])) {
                                    $value_parent_field = '';
                                    $parent_field_id = 0;
                                    if (isset($opts[0]['parent_field_id'])) {
                                        $parent_field_id = $opts[0]['parent_field_id'];
                                    }

                                    if (isset($line_data['form'][$parent_field_id]['type'])
                                        && isset(
                                            $_SESSION['plugin_metademands'][$metademand->getID(
                                            )]['fields'][$parent_field_id]
                                        )) {
                                        if (isset(
                                            $_SESSION['plugin_metademands'][$metademand->getID(
                                            )]['fields'][$parent_field_id]
                                        )) {
                                            $value = $_SESSION['plugin_metademands'][$metademand->getID(
                                            )]['fields'][$parent_field_id];
                                        } else {
                                            $value = 0;
                                        }

                                        switch ($line_data['form'][$parent_field_id]['type']) {
                                            case 'dropdown_multiple':
                                                if (!empty($line_data['form'][$parent_field_id]['custom_values'])) {
                                                    $value_parent_field = $line_data['form'][$parent_field_id]['custom_values'][$parent_field_id];
                                                }
                                                break;
                                            case 'dropdown':
                                            case 'dropdown_object':
                                            case 'dropdown_meta':
                                                if (!empty($line_data['form'][$value_parent_field]['custom_values'])
                                                    && $line_data['form'][$value_parent_field]['item'] == 'other') {
                                                    $value_parent_field = $line_data['form'][$parent_field_id]['custom_values'][$parent_field_id];
                                                } else {
                                                    switch ($line_data['form'][$parent_field_id]['item']) {
                                                        case 'User':
                                                            $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                            $user = new User();
                                                            $user->getFromDB($value);
                                                            $value_parent_field .= $user->getName();
                                                            break;
                                                        default:
                                                            $dbu = new DbUtils();
                                                            $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                            $value_parent_field .= Dropdown::getDropdownName(
                                                                $dbu->getTableForItemType(
                                                                    $line_data['form'][$parent_field_id]['item']
                                                                ),
                                                                $value
                                                            );
                                                            break;
                                                    }
                                                }
                                                break;
                                            case 'checkbox':
                                                if (!empty($line_data['form'][$parent_field_id]['custom_values'])) {
                                                    $line_data['form'][$parent_field_id]['custom_values'] = PluginMetademandsFieldParameter::_unserialize(
                                                        $line_data['form'][$parent_field_id]['custom_values']
                                                    );
                                                    foreach ($line_data['form'][$parent_field_id]['custom_values'] as $k => $val) {
                                                        if (!empty(
                                                        $ret = self::displayField(
                                                            $line_data['form'][$parent_field_id]["id"],
                                                            "custom" . $k
                                                        )
                                                        )) {
                                                            $line_data['form'][$parent_field_id]['custom_values'][$k] = $ret;
                                                        }
                                                    }
                                                    $checkboxes = PluginMetademandsFieldParameter::_unserialize($value);

                                                    $custom_checkbox = [];
                                                    $value_parent_field = "";
                                                    foreach ($line_data['form'][$parent_field_id]['custom_values'] as $key => $label) {
                                                        $checked = isset($checkboxes[$key]) ? 1 : 0;
                                                        if ($checked) {
                                                            $custom_checkbox[] .= $label;
                                                            $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "][" . $key . "]' value='checkbox'>";
                                                        }
                                                    }
                                                    $value_parent_field .= implode('<br>', $custom_checkbox);
                                                }
                                                break;

                                            case 'radio':
                                                if (!empty($line_data['form'][$parent_field_id]['custom_values'])) {
                                                    $line_data['form'][$parent_field_id]['custom_values'] = PluginMetademandsFieldParameter::_unserialize(
                                                        $line_data['form'][$parent_field_id]['custom_values']
                                                    );
                                                    foreach ($line_data['form'][$parent_field_id]['custom_values'] as $k => $val) {
                                                        if (!empty(
                                                        $ret = self::displayField(
                                                            $line_data['form'][$parent_field_id]["id"],
                                                            "custom" . $k
                                                        )
                                                        )) {
                                                            $line_data['form'][$parent_field_id]['custom_values'][$k] = $ret;
                                                        }
                                                    }
                                                    foreach ($line_data['form'][$parent_field_id]['custom_values'] as $key => $label) {
                                                        if ($value == $key) {
                                                            $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='$key' >";
                                                            $value_parent_field .= $label;
                                                            break;
                                                        }
                                                    }
                                                }
                                                break;

                                            case 'date':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                $value_parent_field .= Html::convDate($value);
                                                break;

                                            case 'time':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                $value_parent_field .= $value;
                                                break;

                                            case 'datetime':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                $value_parent_field .= Html::convDateTime($value);
                                                break;

                                            case 'date_interval':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                if (isset(
                                                    $_SESSION['plugin_metademands'][$metademand->getID(
                                                    )]['fields'][$data['parent_field_id'] . "-2"]
                                                )) {
                                                    $value2 = $_SESSION['plugin_metademands'][$metademand->getID(
                                                    )]['fields'][$parent_field_id . "-2"];
                                                    $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value2 . "'>";
                                                } else {
                                                    $value2 = 0;
                                                }
                                                $value_parent_field .= Html::convDate($value) . " - " . Html::convDate(
                                                        $value2
                                                    );
                                                break;

                                            case 'datetime_interval':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                if (isset(
                                                    $_SESSION['plugin_metademands'][$metademand->getID(
                                                    )]['fields'][$data['parent_field_id'] . "-2"]
                                                )) {
                                                    $value2 = $_SESSION['plugin_metademands'][$metademand->getID(
                                                    )]['fields'][$parent_field_id . "-2"];
                                                    $value_parent_field .= "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "-2]' value='" . $value2 . "'>";
                                                } else {
                                                    $value2 = 0;
                                                }
                                                $value_parent_field .= Html::convDateTime(
                                                        $value
                                                    ) . " - " . Html::convDateTime($value2);
                                                break;
                                            case 'yesno':
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                                $value_parent_field .= Dropdown::getYesNo($value);
                                                break;
                                            case 'basket':

                                                break;
                                            default:
                                                $value_parent_field = "<input type='hidden' name='" . $namefield . "[" . $data['id'] . "]' value='" . $value . "'>";
                                        }
                                    }
                                    $field .= $value_parent_field;
                                    break;
                                }
//                                }
                            }
                        }
                    }
                }
                break;
            default:
                //plugin case
                if (isset($PLUGIN_HOOKS['metademands'])) {
                    $hooks_plugins = $PLUGIN_HOOKS['metademands'];
                    foreach ($hooks_plugins as $plug => $pluginclass) {
                        if (Plugin::isPluginActive($plug)) {
                            echo self::showPluginFieldCase(
                                $plug,
                                $metademands_data,
                                $data,
                                $on_order = false,
                                $itilcategories_id = 0,
                                $idline = 0
                            );
                        }
                    }
                }
                break;
        }
//        if ($on_order == false) {
        echo $field;
//        } else {
//            return $field;
//        }
    }


    /**
     * @param        $entity
     * @param        $userid
     * @param string $filter
     * @param bool $first
     *
     * @return array|int|mixed
     */
    public static function getUserGroup($entity, $userid, $cond = '', $first = true)
    {
        global $DB;

        $dbu = new DbUtils();

        $where = [];
        if ($cond) {
            $where = $cond;
        }

        $query = [
            'FIELDS' => ['glpi_groups' => ['id']],
            'FROM' => 'glpi_groups_users',
            'INNER JOIN' => [
                'glpi_groups' => [
                    'FKEY' => [
                        'glpi_groups' => 'id',
                        'glpi_groups_users' => 'groups_id'
                    ]
                ]
            ],
            'WHERE' => [
                    'users_id' => $userid,
                    $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
                ] + $where
        ];

        $rep = [];
        foreach ($DB->request($query) as $data) {
            if ($first) {
                return $data['id'];
            }
            $rep[] = $data['id'];
        }
        return ($first ? 0 : $rep);
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
     * @param $metademands_id
     *
     * @return array
     */
    public function listMetademandsfields($metademands_id)
    {
        $field = new self();
        $listMetademandsFields = $field->find(['plugin_metademands_metademands_id' => $metademands_id]);

        return $listMetademandsFields;
    }


    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForAdd($input)
    {
        // legacy support
        if (isset($input['existing_field_id']) && isset($input['item']) && $input['item'] == 'User') {
            if (isset($input['informations_to_display']) && $input['informations_to_display'] == '[]') {
                $input['informations_to_display'] = '["full_name"]';
            }
        }

        if (!$this->checkMandatoryFields($input)) {
            return false;
        }

        //      $meta = new PluginMetademandsMetademand();

        //      if ($meta->getFromDB($input['plugin_metademands_metademands_id'])
        //          && $meta->fields['is_order'] == 1) {
        //         $input['is_basket'] = 1;
        //      }

        if (isset($input["type"]) && $input["type"] == "checkbox") {
            $input["item"] = "checkbox";
        }
        if (isset($input["type"]) && $input["type"] == "radio") {
            $input["item"] = "radio";
        }

        return $input;
    }


    /**
     * @param array $input
     *
     * @return array|bool
     */
    public function prepareInputForUpdate($input)
    {
        if (!$this->checkMandatoryFields($input)) {
            return false;
        }
        if (isset($input["type"]) && $input["type"] == "checkbox") {
            $input["item"] = "checkbox";
        }
        if (isset($input["type"]) && $input["type"] == "radio") {
            $input["item"] = "radio";
        }

        return $input;
    }

    public function cleanDBonPurge()
    {
        $temp = new PluginMetademandsTicket_Field();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new PluginMetademandsBasketline();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new PluginMetademandsFieldOption();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new PluginMetademandsFieldParameter();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new PluginMetademandsFieldCustomvalue();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new PluginMetademandsFreetablefield();
        $temp->deleteByCriteria(['plugin_metademands_fields_id' => $this->fields['id']], false, false);

        $temp = new PluginMetademandsFieldOption();
        $temp->deleteByCriteria(['parent_field_id' => $this->fields['id']], false, false);
    }

    /**
     * @param $value
     *
     * @return bool|string
     */
    public static function setColor($value)
    {
        return substr(
            substr(dechex(($value * 298)), 0, 2) .
            substr(dechex(($value * 7777)), 0, 3) .
            substr(dechex(($value * 1)), 0, 1) .
            substr(dechex(($value * 64)), 0, 1) .
            substr(dechex(($value * 13)), 0, 1) .
            substr(dechex(($value * 1)), 0, 1),
            0,
            6
        );
    }

    /**
     * @param $input
     *
     * @return bool
     */
    public function checkMandatoryFields($input)
    {
        $msg = [];
        $checkKo = false;

        $mandatory_fields = [
            'name' => __('Label'),
            'label2' => __('Additional label', 'metademands'),
            'type' => __('Type'),
            'item' => __('Object', 'metademands')
        ];
        $id = isset($input['id']) ? $input['id'] : 0;
        foreach ($input as $key => $value) {
            if (array_key_exists($key, $mandatory_fields)) {
                if (empty($value)) {
                    if (($key == 'item' && ($input['type'] == 'dropdown'
                                || $input['type'] == 'dropdown_object'
                                || $input['type'] == 'dropdown_meta'))
                        || ($key == 'label2' && ($input['type'] == 'date_interval' || $input['type'] == 'datetime_interval'))) {
                        $msg[] = $mandatory_fields[$key];
                        $checkKo = true;
                    } elseif ($key != 'item' && $key != 'label2') {
                        $msg[] = $mandatory_fields[$key];
                        $checkKo = true;
                    }
                }
            }
            $_SESSION['glpi_plugin_metademands_fields'][$id][$key] = $value;
        }

        if ($checkKo) {
            Session::addMessageAfterRedirect(
                sprintf(__("Mandatory fields are not filled. Please correct: %s"), implode(', ', $msg)),
                false,
                ERROR
            );
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function rawSearchOptions()
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
            'id' => '814',
            'table' => $this->getTable(),
            'field' => 'rank',
            'name' => __('Block', 'metademands'),
            'datatype' => 'specific',
            'massiveaction' => true
        ];

        $tab[] = [
            'id' => '815',
            'table' => $this->getTable(),
            'field' => 'order',
            'name' => __('Order', 'metademands'),
            'datatype' => 'specific',
            'massiveaction' => false
        ];

        $tab[] = [
            'id' => '817',
            'table' => $this->getTable(),
            'field' => 'label2',
            'name' => __('Additional label', 'metademands'),
            'datatype' => 'text'
        ];

        $tab[] = [
            'id' => '818',
            'table' => $this->getTable(),
            'field' => 'comment',
            'name' => __('Comments'),
            'datatype' => 'text'
        ];

//        $tab[] = [
//            'id' => '819',
//            'table' => $this->getTable(),
//            'field' => 'is_mandatory',
//            'name' => __('Mandatory field'),
//            'datatype' => 'bool'
//        ];
//
//        $tab[] = [
//            'id' => '820',
//            'table' => $this->getTable(),
//            'field' => 'is_basket',
//            'name' => __('Display into the basket', 'metademands'),
//            'datatype' => 'bool'
//        ];

        $tab[] = [
            'id' => '880',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown'
        ];

        $tab[] = [
            'id' => '886',
            'table' => $this->getTable(),
            'field' => 'is_recursive',
            'name' => __('Child entities'),
            'datatype' => 'bool'
        ];

        return $tab;
    }


    /**
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'change_color':
                echo Html::showColorField('color', ['display' => false]);
                echo "<br>" .
                    Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            case 'change_icon':
                $icon_selector_id = 'icon_' . mt_rand();
                $return = Html::select(
                    'icon',
                    [],
                    [
                        'id' => $icon_selector_id,
                        'display' => false,
                        'style' => 'width:175px;'
                    ]
                );

                $return .= Html::script('js/Forms/FaIconSelector.js');
                $return .= Html::scriptBlock(
                    <<<JAVASCRIPT
         $(
            function() {
               var icon_selector = new GLPI.Forms.FaIconSelector(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
            }
         );
JAVASCRIPT
                );
                echo $return;
                echo "&nbsp;" .
                    Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return false;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions['PluginMetademandsField:change_icon'] = __("Modify icon", "metademands");
            $actions['PluginMetademandsField:change_color'] = __("Modify color", "metademands");
        }

        return $actions;
    }

    /**
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case "change_icon":
                $input = $ma->getInput();

                foreach ($ids as $id) {
                    $field = new PluginMetademandsField();
                    $param = new PluginMetademandsFieldParameter();
                    $msg = MassiveAction::ACTION_OK;
                    if ($param->getFromDBByCrit(["plugin_metademands_fields_id" => $id])) {
                        $field->getFromDB($id);
                        if ($field->fields['type'] == 'title-block'
                            || $field->fields['type'] == 'title') {
                            $param->update(['id' => $param->fields['id'], 'icon' => $input['icon']]);
                        } else {
                            $ma->addMessage(__('You cannot do this for this field', 'metademands'));
                            $msg = MassiveAction::ACTION_KO;
                        }

                    }
                    $item->getFromDB($id);
                    $ma->itemDone($item->getType(), $id, $msg);
                }
                return;
            case "change_color":
                $input = $ma->getInput();
                foreach ($ids as $id) {
                    $field = new PluginMetademandsField();
                    $param = new PluginMetademandsFieldParameter();
                    $msg = MassiveAction::ACTION_OK;
                    if ($param->getFromDBByCrit(["plugin_metademands_fields_id" => $id])) {
                        $field->getFromDB($id);
                        if ($field->fields['type'] == 'title-block'
                            || $field->fields['type'] == 'title') {
                            $param->update(['id' => $param->fields['id'], 'color' => $input['color']]);
                        } else {
                            $ma->addMessage(__('You cannot do this for this field', 'metademands'));
                            $msg = MassiveAction::ACTION_KO;
                        }

                    }
                    $item->getFromDB($id);
                    $ma->itemDone($item->getType(), $id, $msg);
                }
                return;
        }
        return;
    }

    /**
     * @param $field
     * @param $name (default '')
     * @param $values (default '')
     * @param $options   array
     *
     * @return string
     **@since version 0.84
     *
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'rank':
                $options['min'] = 1;
                $options['max'] = self::MAX_FIELDS;

                return Dropdown::showNumber($name, $options);
                break;
            case 'order':
                return Dropdown::showNumber($name, $options);
                break;
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * @param $rank
     * @param $fields_id
     * @param $previous_fields_id
     * @param $metademands_id
     */
    public function showOrderDropdown($rank, $fields_id, $previous_fields_id, $metademands_id)
    {
        if (empty($rank)) {
            $rank = 1;
        }
        $restrict = ['rank' => $rank, 'plugin_metademands_metademands_id' => $metademands_id];
        //      $restrict += ['NOT' => ['type' => 'title-block']];
        if (!empty($fields_id)) {
            $restrict += ['NOT' => ['id' => $fields_id]];
        }

        $order = [Dropdown::EMPTY_VALUE];

        foreach ($this->find($restrict, ['order']) as $id => $values) {
            $order[$id] = $values['name'] ? Toolbox::stripslashes_deep($values['name']) : $id;
            //if (!empty($values['label2'])) {
            //   $order[$id] .= ' - ' . $values['label2'];
            //}
            if (empty(trim($order[$id]))) {
                $order[$id] = __('ID') . " - " . $id;
            }
        }
        Dropdown::showFromArray('plugin_metademands_fields_id', $order, ['value' => $previous_fields_id]);
    }

    /**
     * @param $input
     */
    public function recalculateOrder($input)
    {
        $previousfield = new self();
        $new_order = [];

        // Set current field after selected field
        if (!empty($input['plugin_metademands_fields_id'])) {
            $previousfield->getFromDB($input['plugin_metademands_fields_id']);
            $input['order'] = $previousfield->fields['order'] + 1;
        } else {
            $input['order'] = 1;
        }

        // Calculate order
        foreach (
            $this->find(
                [
                    'rank' => $input['rank'],
                    'plugin_metademands_metademands_id' => $input["plugin_metademands_metademands_id"]
                ],
                ['order']
            ) as $fields_id => $values
        ) {
            if ($fields_id == $input['id']) {
                $values['order'] = $input['order'];
            }
            if ($values['order'] >= $input['order'] && $values['id'] != $input['id']) {
                $new_order[$fields_id] = $values['order'] + 1;
            } else {
                $new_order[$fields_id] = $values['order'];
            }
        }
        asort($new_order);// sort by value

        // Update the new order on each fields of the rank
        $count = 1;// reinit orders with a counter
        $previous = [];
        foreach ($new_order as $fields_id => $order) {
            $previous[$count] = $fields_id;
            $myfield = new self();
            $myfield->getFromDB($fields_id);
            // Update order
            $myfield->fields['order'] = $count;
            // Update previous fields_id
            if (isset($previous[$count - 1])) {
                $myfield->fields['plugin_metademands_fields_id'] = $previous[$count - 1];
            } else {
                $myfield->fields['plugin_metademands_fields_id'] = 0;
            }
            $myfield->updateInDB(['order', 'plugin_metademands_fields_id']);
            $count++;
        }
    }


    /**
     * Returns the translation of the field
     *
     * @param type $item
     * @param type $field
     *
     * @return type
     * @global type $DB
     *
     */
    public static function displayField($id, $field, $lang = '')
    {
        global $DB;

        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_metademands_fieldtranslations',
            'WHERE' => [
                'itemtype' => self::getType(),
                'items_id' => $id,
                'field' => $field,
                'language' => $_SESSION['glpilanguage']
            ]
        ]);
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                'FROM' => 'glpi_plugin_metademands_fieldtranslations',
                'WHERE' => [
                    'itemtype' => self::getType(),
                    'items_id' => $id,
                    'field' => $field,
                    'language' => $lang
                ]
            ]);
        }


        if (count($iterator)) {
            foreach ($iterator as $data) {
                $res = $data['value'];
            }
        }
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            if (count($iterator2)) {
                foreach ($iterator2 as $data2) {
                    $res .= ' / ' . $data2['value'];
                    $iterator2->next();
                }
            }
        }
        return $res;
    }


    /**
     * Returns the translation of the field
     *
     * @param type $item
     * @param type $field
     *
     * @return type
     * @global type $DB
     *
     */
    public static function displayCustomvaluesField($id, $field, $type = "name", $lang = '')
    {
        global $DB;

        $field_custom = new PluginMetademandsFieldCustomvalue();
        $field_custom->getFromDB($field);
        if ($type == "name") {
            $field = "custom" . $field_custom->fields['rank'];
        } elseif ($type == "comment") {
            $field = "commentcustom" . $field_custom->fields['rank'];
        }


        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_metademands_fieldtranslations',
            'WHERE' => [
                'itemtype' => self::getType(),
                'items_id' => $id,
                'field' => $field,
                'language' => $_SESSION['glpilanguage']
            ]
        ]);
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                'FROM' => 'glpi_plugin_metademands_fieldtranslations',
                'WHERE' => [
                    'itemtype' => self::getType(),
                    'items_id' => $id,
                    'field' => $field,
                    'language' => $lang
                ]
            ]);
        }

        if (count($iterator)) {
            foreach ($iterator as $data) {
                $res = $data['value'];
            }
        }
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            if (count($iterator2)) {
                foreach ($iterator2 as $data2) {
                    $res .= ' / ' . $data2['value'];
                    $iterator2->next();
                }
            }
        }

        return $res;
    }

    /**
     * @return array
     */
    /**
     * @return array
     */
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden = parent::getForbiddenStandardMassiveAction();

        $forbidden[] = 'merge';
        $forbidden[] = 'add_transfer_list';
        $forbidden[] = 'amend_comment';

        return $forbidden;
    }

    /**
     * @return array[]
     */
    public static function getGlpiObject()
    {
        $optgroup = [
            __("Assets") => [
                Computer::class => Computer::getTypeName(2),
                Monitor::class => Monitor::getTypeName(2),
                Software::class => Software::getTypeName(2),
                Networkequipment::class => Networkequipment::getTypeName(2),
                Peripheral::class => Peripheral::getTypeName(2),
                Printer::class => Printer::getTypeName(2),
                CartridgeItem::class => CartridgeItem::getTypeName(2),
                ConsumableItem::class => ConsumableItem::getTypeName(2),
                Phone::class => Phone::getTypeName(2),
                Line::class => Line::getTypeName(2)
            ],
            __("Assistance") => [
                Ticket::class => Ticket::getTypeName(2),
                Problem::class => Problem::getTypeName(2),
                TicketRecurrent::class => TicketRecurrent::getTypeName(2)
            ],
            __("Management") => [
                Budget::class => Budget::getTypeName(2),
                Supplier::class => Supplier::getTypeName(2),
                Contact::class => Contact::getTypeName(2),
                Contract::class => Contract::getTypeName(2),
                Document::class => Document::getTypeName(2),
                Project::class => Project::getTypeName(2),
                Appliance::class => Appliance::getTypeName(2)
            ],
            __("Tools") => [
                Reminder::class => __("Notes"),
                RSSFeed::class => __("RSS feed")
            ],
            __("Administration") => [
                User::class => User::getTypeName(2),
                Group::class => Group::getTypeName(2),
                Entity::class => Entity::getTypeName(2),
                Profile::class => Profile::getTypeName(2)
            ],
        ];
        if (class_exists(PassiveDCEquipment::class)) {
            // Does not exists in GLPI 9.4
            $optgroup[__("Assets")][PassiveDCEquipment::class] = PassiveDCEquipment::getTypeName(2);
        }

        $plugin = new Plugin();
        if ($plugin->isActivated("genericobject")) {
            foreach (PluginGenericobjectType::getTypes() as $id => $objecttype) {
                $itemtype = $objecttype['itemtype'];
                if (class_exists($itemtype)) {
                    $item = new $itemtype();
                    $optgroup[__("Assets")][$item::class] = $item::getTypeName(2);
                }
            }
        }

        return $optgroup;
    }

    public static function getDeviceName($value)
    {
        global $DB, $CFG_GLPI;
        $userID = Session::getLoginUserID();
        $entity_restrict = $_SESSION['glpiactiveentities'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => Dropdown::EMPTY_VALUE];
            $devices = [];

            // My items
            foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                    && Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                        'FROM' => $itemtable,
                        'WHERE' => [
                                'users_id' => $userID
                            ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                        'ORDER' => $item->getNameField()
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'glpi_groups_users.groups_id',
                        'glpi_groups.name'
                    ],
                    'FROM' => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_groups' => [
                            'ON' => [
                                'glpi_groups_users' => 'groups_id',
                                'glpi_groups' => 'id'
                            ]
                        ]
                    ],
                    'WHERE' => [
                            'glpi_groups_users.users_id' => $userID
                        ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
                ]);

                $devices = [];
                $groups = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups = array_merge($groups, $a_groups);
                    }

                    foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                            && Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria = [
                                'FROM' => $itemtable,
                                'WHERE' => [
                                        'groups_id' => $groups
                                    ] + getEntitiesRestrictCriteria(
                                        $itemtable,
                                        '',
                                        $entity_restrict,
                                        $item->maybeRecursive()
                                    ),
                                'ORDER' => 'name'
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect(
                    $CFG_GLPI['software_types'],
                    $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]
                );
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                            'SELECT' => [
                                'glpi_softwareversions.name AS version',
                                'glpi_softwares.name AS name',
                                'glpi_softwares.id'
                            ],
                            'DISTINCT' => true,
                            'FROM' => 'glpi_items_softwareversions',
                            'LEFT JOIN' => [
                                'glpi_softwareversions' => [
                                    'ON' => [
                                        'glpi_items_softwareversions' => 'softwareversions_id',
                                        'glpi_softwareversions' => 'id'
                                    ]
                                ],
                                'glpi_softwares' => [
                                    'ON' => [
                                        'glpi_softwareversions' => 'softwares_id',
                                        'glpi_softwares' => 'id'
                                    ]
                                ]
                            ],
                            'WHERE' => [
                                    'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                    'glpi_items_softwareversions.itemtype' => $itemtype,
                                    'glpi_softwares.is_helpdesk_visible' => 1
                                ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                            'ORDERBY' => 'glpi_softwares.name'
                        ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"]
                                        )
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer']) && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                        && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                            'SELECT' => "$itemtable.*",
                            'DISTINCT' => true,
                            'FROM' => 'glpi_computers_items',
                            'LEFT JOIN' => [
                                $itemtable => [
                                    'ON' => [
                                        'glpi_computers_items' => 'items_id',
                                        $itemtable => 'id'
                                    ]
                                ]
                            ],
                            'WHERE' => [
                                    'glpi_computers_items.itemtype' => $itemtype,
                                    'glpi_computers_items.computers_id' => $already_add['Computer']
                                ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                            'ORDERBY' => "$itemtable.name"
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }
        }
        $array = explode('_', $value);
        $itemType = $array[0];
        $item_id = $array[1];

        $item = new $itemType();
        $item->getFromDB($item_id);
        $return = $itemType . " - " . $item->fields['name'] . " (" . $item_id . ")";
        return $return;
    }

    /**
     * Make a select box for Ticket my devices
     *
     * @param integer $userID User ID for my device section (default 0)
     * @param integer $entity_restrict restrict to a specific entity (default -1)
     * @param int $itemtype of selected item (default 0)
     * @param integer $items_id of selected item (default 0)
     * @param array $options array of possible options:
     *    - used     : ID of the requester user
     *    - multiple : allow multiple choice
     *
     * @return void
     */
    public static function dropdownMyDevices(
        $userID = 0,
        $entity_restrict = -1,
        $itemtype = 0,
        $items_id = 0,
        $options = [],
        $limit = [],
        $display = true
    ) {
        global $DB, $CFG_GLPI;

        $params = [
            'tickets_id' => 0,
            'used' => [],
            'multiple' => false,
            'name' => 'my_items',
            'value' => 0,
            'rand' => mt_rand()
        ];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        if ($userID == 0) {
            $userID = Session::getLoginUserID();
        }

        $rand = $params['rand'];
        $already_add = $params['used'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
            $my_devices = ['' => Dropdown::EMPTY_VALUE];
            $devices = [];

            $itemtypes = $CFG_GLPI["linkuser_types"];
            if (count($limit) > 0) {
                $itemtypes = $limit;
            }

            // My items
            foreach ($itemtypes as $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                    && Ticket::isPossibleToAssignType($itemtype)) {
                    $itemtable = getTableForItemType($itemtype);

                    $criteria = [
                        'FROM' => $itemtable,
                        'WHERE' => [
                                'users_id' => $userID
                            ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                        'ORDER' => $item->getNameField()
                    ];

                    if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                    }
                    if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                        $criteria['WHERE']['is_helpdesk_visible'] = 1;
                    }

                    $iterator = $DB->request($criteria);
                    $nb = count($iterator);
                    if ($nb > 0) {
                        $type_name = $item->getTypeName($nb);

                        foreach ($iterator as $data) {
                            if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                                $output = $data[$item->getNameField()];
                                if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                    $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                }
                                $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                if ($itemtype != 'Software') {
                                    if (!empty($data['serial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                    }
                                    if (!empty($data['otherserial'])) {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                }
                                $devices[$itemtype . "_" . $data["id"]] = $output;

                                $already_add[$itemtype][] = $data["id"];
                            }
                        }
                    }
                }
            }

            if (count($devices)) {
                $my_devices[__('My devices')] = $devices;
            }
            // My group items
            if (Session::haveRight("show_group_hardware", "1")) {
                $iterator = $DB->request([
                    'SELECT' => [
                        'glpi_groups_users.groups_id',
                        'glpi_groups.name'
                    ],
                    'FROM' => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_groups' => [
                            'ON' => [
                                'glpi_groups_users' => 'groups_id',
                                'glpi_groups' => 'id'
                            ]
                        ]
                    ],
                    'WHERE' => [
                            'glpi_groups_users.users_id' => $userID
                        ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
                ]);

                $devices = [];
                $groups = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $a_groups = getAncestorsOf("glpi_groups", $data["groups_id"]);
                        $a_groups[$data["groups_id"]] = $data["groups_id"];
                        $groups = array_merge($groups, $a_groups);
                    }

                    foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                        if (($item = getItemForItemtype($itemtype))
                            && Ticket::isPossibleToAssignType($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $criteria = [
                                'FROM' => $itemtable,
                                'WHERE' => [
                                        'groups_id' => $groups
                                    ] + getEntitiesRestrictCriteria(
                                        $itemtable,
                                        '',
                                        $entity_restrict,
                                        $item->maybeRecursive()
                                    ),
                                'ORDER' => 'name'
                            ];

                            if ($item->maybeDeleted()) {
                                $criteria['WHERE']['is_deleted'] = 0;
                            }
                            if ($item->maybeTemplate()) {
                                $criteria['WHERE']['is_template'] = 0;
                            }

                            $iterator = $DB->request($criteria);
                            if (count($iterator)) {
                                $type_name = $item->getTypeName();
                                if (!isset($already_add[$itemtype])) {
                                    $already_add[$itemtype] = [];
                                }
                                foreach ($iterator as $data) {
                                    if (!in_array($data["id"], $already_add[$itemtype])) {
                                        $output = '';
                                        if (isset($data["name"])) {
                                            $output = $data["name"];
                                        }
                                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                            $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                        }
                                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                        if (isset($data['serial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                                        }
                                        if (isset($data['otherserial'])) {
                                            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                        }
                                        $devices[$itemtype . "_" . $data["id"]] = $output;

                                        $already_add[$itemtype][] = $data["id"];
                                    }
                                }
                            }
                        }
                    }
                    if (count($devices)) {
                        $my_devices[__('Devices own by my groups')] = $devices;
                    }
                }
            }
            // Get software linked to all owned items
            if (in_array('Software', $itemtypes)
                && in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
                $software_helpdesk_types = array_intersect(
                    $CFG_GLPI['software_types'],
                    $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]
                );
                foreach ($software_helpdesk_types as $itemtype) {
                    if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                        $iterator = $DB->request([
                            'SELECT' => [
                                'glpi_softwareversions.name AS version',
                                'glpi_softwares.name AS name',
                                'glpi_softwares.id'
                            ],
                            'DISTINCT' => true,
                            'FROM' => 'glpi_items_softwareversions',
                            'LEFT JOIN' => [
                                'glpi_softwareversions' => [
                                    'ON' => [
                                        'glpi_items_softwareversions' => 'softwareversions_id',
                                        'glpi_softwareversions' => 'id'
                                    ]
                                ],
                                'glpi_softwares' => [
                                    'ON' => [
                                        'glpi_softwareversions' => 'softwares_id',
                                        'glpi_softwares' => 'id'
                                    ]
                                ]
                            ],
                            'WHERE' => [
                                    'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                                    'glpi_items_softwareversions.itemtype' => $itemtype,
                                    'glpi_softwares.is_helpdesk_visible' => 1
                                ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                            'ORDERBY' => 'glpi_softwares.name'
                        ]);

                        $devices = [];
                        if (count($iterator)) {
                            $item = new Software();
                            $type_name = $item->getTypeName();
                            if (!isset($already_add['Software'])) {
                                $already_add['Software'] = [];
                            }
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add['Software'])) {
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                                    $output = sprintf(
                                        __('%1$s (%2$s)'),
                                        $output,
                                        sprintf(
                                            __('%1$s: %2$s'),
                                            __('version'),
                                            $data["version"]
                                        )
                                    );
                                    if ($_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                                    }
                                    $devices["Software_" . $data["id"]] = $output;

                                    $already_add['Software'][] = $data["id"];
                                }
                            }
                            if (count($devices)) {
                                $my_devices[__('Installed software')] = $devices;
                            }
                        }
                    }
                }
            }
            // Get linked items to computers
            if (isset($already_add['Computer']) && count($already_add['Computer'])) {
                $devices = [];

                // Direct Connection
                $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
                foreach ($types as $itemtype) {
                    if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                        && ($item = getItemForItemtype($itemtype))) {
                        $itemtable = getTableForItemType($itemtype);
                        if (!isset($already_add[$itemtype])) {
                            $already_add[$itemtype] = [];
                        }
                        $criteria = [
                            'SELECT' => "$itemtable.*",
                            'DISTINCT' => true,
                            'FROM' => 'glpi_computers_items',
                            'LEFT JOIN' => [
                                $itemtable => [
                                    'ON' => [
                                        'glpi_computers_items' => 'items_id',
                                        $itemtable => 'id'
                                    ]
                                ]
                            ],
                            'WHERE' => [
                                    'glpi_computers_items.itemtype' => $itemtype,
                                    'glpi_computers_items.computers_id' => $already_add['Computer']
                                ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                            'ORDERBY' => "$itemtable.name"
                        ];

                        if ($item->maybeDeleted()) {
                            $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                        }
                        if ($item->maybeTemplate()) {
                            $criteria['WHERE']["$itemtable.is_template"] = 0;
                        }

                        $iterator = $DB->request($criteria);
                        if (count($iterator)) {
                            $type_name = $item->getTypeName();
                            foreach ($iterator as $data) {
                                if (!in_array($data["id"], $already_add[$itemtype])) {
                                    $output = $data["name"];
                                    if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                        $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                                    }
                                    $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                                    if ($itemtype != 'Software') {
                                        $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                                    }
                                    $devices[$itemtype . "_" . $data["id"]] = $output;

                                    $already_add[$itemtype][] = $data["id"];
                                }
                            }
                        }
                    }
                }
                if (count($devices)) {
                    $my_devices[__('Connected devices')] = $devices;
                }
            }

            $return = "<span id='show_items_id_requester'>";
            $return .= Dropdown::showFromArray(
                $params['name'],
                $my_devices,
                ['rand' => $rand, 'display' => false, 'value' => $params['value']]
            );
            $return .= "</span>";

            if ($display) {
                echo $return;
            } else {
                return $return;
            }
            // Auto update summary of active or just solved tickets
            //         $params = ['my_items' => '__VALUE__'];
            //
            //         Ajax::updateItemOnSelectEvent("dropdown_my_items$rand", "item_ticket_selection_information$rand",
            //                                       $CFG_GLPI["root_doc"] . "/ajax/ticketiteminformation.php",
            //                                       $params);
        }
    }

    public function getProfileJoinCriteria()
    {
        return [
            'INNER JOIN' => [
                Profile_User::getTable() => [
                    'ON' => [
                        Profile_User::getTable() => 'users_id',
                        User::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => getEntitiesRestrictCriteria(
                Profile_User::getTable(),
                'entities_id',
                $_SESSION['glpiactiveentities'],
                true
            )
        ];
    }

    /**
     * Get request criteria to select uniques users
     *
     * @return array
     * @since 9.4
     *
     */
    final public function getDistinctUserCriteria()
    {
        return [
            'FIELDS' => [
                User::getTable() . '.id AS users_id',
                User::getTable() . '.language AS language'
            ],
            'DISTINCT' => true,
        ];
    }


    public function post_addItem()
    {
        $pluginField = new PluginMetademandsPluginfields();
        $input = [];
        if (isset($this->input['plugin_fields_fields_id'])) {
            $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
            $input['plugin_metademands_fields_id'] = $this->fields['id'];
            $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
            $pluginField->add($input);
        }
    }

    public function post_updateItem($history = 1)
    {
        $pluginField = new PluginMetademandsPluginfields();
        if (isset($this->input['plugin_fields_fields_id'])) {
            if ($pluginField->getFromDBByCrit(['plugin_metademands_fields_id' => $this->fields['id']])) {
                $input = [];
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['id'];
                $input['id'] = $pluginField->fields['id'];
                $pluginField->update($input);
            } else {
                $input = [];
                $input['plugin_fields_fields_id'] = $this->input['plugin_fields_fields_id'];
                $input['plugin_metademands_fields_id'] = $this->fields['id'];
                $input['plugin_metademands_metademands_id'] = $this->fields['plugin_metademands_metademands_id'];
                $pluginField->add($input);
            }
        }
    }

    public static function searchForm($item, $cond)
    {
        global $DB;

        $params = $cond ?? [];

        $p['type'] = '';
        $p['item'] = '';
        $p['rank'] = 0;
        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        echo "<form name='form' method='post' action='" . PLUGIN_METADEMANDS_WEBDIR . "/front/field.php'>";
        echo "<div class='center'><table class='tab_cadrehov'>";
        echo "<tr class='tab_bg_2'>";

        echo "<td class='center'>";
        $iterator = $DB->request([
            'SELECT'    => ['MAX' => 'rank AS maxrank'],
            'FROM'      => 'glpi_plugin_metademands_fields',
            'WHERE'     => [
                'plugin_metademands_metademands_id'  => $item->getID()
            ],
        ]);

        $max = PluginMetademandsField::MAX_FIELDS;
        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $max = $data['maxrank'];
            }
        }


        echo __('Block', 'metademands')."&nbsp;";
        Dropdown::showNumber('block', [
            'value' => $p['rank'],
            'min' => 1,
            'max' => $max,
            'toadd' => [0 => Dropdown::EMPTY_VALUE]
        ]);
        echo "</td>";

        echo "<td class='center'>";
        echo __('Field type', 'metademands')."&nbsp;";
        $mrand = self::dropdownFieldTypes(
            self::$field_types, ['value' => $p['type'], 'metademands_id' => $item->getID(), 'on_change' => 'plugin_metademands_reloaditem();']
        );
        echo "</td>";

        echo "<td class='center'>";
        echo "<span id='plugin_metademands_item'>";
        if (in_array($p['type'], self::$field_withobjects)) {
            echo __('Object', 'metademands')."&nbsp;";
            self::dropdownFieldItems($p['type'], ['value' => $p["item"], 'with_empty_value' => true]);
        }
        echo "</span>";
        echo "</td>";

        echo "<script type='text/javascript'>";
        echo "function plugin_metademands_reloaditem() {";
        $params = ['action'            => 'reloaditem',
            'type'              => '__VALUE__',
        ];
        Ajax::updateItemJsCode(
            'plugin_metademands_item',
            PLUGIN_METADEMANDS_WEBDIR . '/ajax/reloaditem.php',
            $params,
            'dropdown_type' . $mrand
        );
        echo "};";
        echo "</script>";

        echo "<td>";
        echo Html::hidden('plugin_metademands_metademands_id', ['value' => $item->getID()]);
        echo Html::submit(_sx('button', 'Post'), ['name' => 'search', 'class' => 'btn btn-primary']);
        echo "</td>";
        echo "</tr>";

        echo "</table></div>";
        Html::closeForm();
    }


    /**
     * @param array $params
     */
    public function reorder(array $params)
    {
        if (isset($params['old_order'])
            && isset($params['new_order'])) {
            $crit = [
                'order' => $params['old_order'],
                'rank' => $params['rank'],
                'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id']
            ];

            $itemMove = new self();
            $itemMove->getFromDBByCrit($crit);

            if (isset($itemMove->fields["id"])) {
                // Reorganization of all fields
                if ($params['old_order'] < $params['new_order']) {
                    $toUpdateList = $this->find([
                        '`order`' => ['>', $params['old_order']],
                        'order' => ['<=', $params['new_order']],
                        'rank' => $params['rank'],
                        'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id']
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id' => $toUpdate['id'],
                            'order' => $toUpdate['order'] - 1
                        ]);
                    }
                } else {
                    $toUpdateList = $this->find([
                        '`order`' => ['<', $params['old_order']],
                        'order' => ['>=', $params['new_order']],
                        'rank' => $params['rank'],
                        'plugin_metademands_metademands_id' => $params['plugin_metademands_metademands_id']
                    ]);

                    foreach ($toUpdateList as $toUpdate) {
                        $this->update([
                            'id' => $toUpdate['id'],
                            'order' => $toUpdate['order'] + 1
                        ]);
                    }
                }

                if (isset($itemMove->fields["id"])
                    && $itemMove->fields['id'] > 0) {
                    $this->update([
                        'id' => $itemMove->fields['id'],
                        'order' => $params['new_order']
                    ]);
                }
            }
        }
    }

    static function isSequentialFromOne(array $arr) {
        if (empty($arr) || $arr[0] !== 1) {
            return false; // Vérifie que le tableau n'est pas vide et commence bien par 0
        }

        for ($i = 1; $i < count($arr); $i++) {
            if ($arr[$i] - $arr[$i - 1] !== 1) {
                return false; // Vérifie que la progression est bien de +1
            }
        }
        return true;
    }

    static function fixOrders(array $data) {
        // Extraire les clés du tableau
        $keys = array_keys($data);

        // Réinitialiser le rank à partir de 0
        foreach ($keys as $index => $key) {
            $data[$key]['order'] = $index + 1;
        }

        return $data;
    }
}
