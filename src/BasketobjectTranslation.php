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

use Ajax;
use DbUtils;
use DropdownTranslation;
use Html;
use Session;
use CommonGLPI;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}


/**
 * BasketobjectTranslation Class
 *
 **/
class BasketobjectTranslation extends DropdownTranslation
{

    public static $itemtype  = 'itemtype';
    public static $items_id  = 'items_id';
    public $dohistory = true;
    const TRANSLATE_FIELD_NAME = 'name';
    const TRANSLATE_FIELD_DESCRIPTION = 'description';
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
        return _n('Translation', 'Translations', $nb);
    }


    static function getIcon()
    {
        return Metademand::getIcon();
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
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $nb = self::getNumberOfTranslationsForItem($item);
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
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
        if (self::canBeTranslated($item)) {
            self::showTranslations($item);
        }
        return true;
    }


    /**
     * Display all translated field for a dropdown
     *
     * @param $item a Dropdown item
     *
     * @return true;
     **/
    static function showTranslations($item)
    {
        global $DB, $CFG_GLPI;

        $rand    = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        if ($canedit) {
            echo "<div id='viewtranslationbasketobj" . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addTranslationbasketobj" . $item->getID() . "$rand() {\n";
            $params = ['type'                      => __CLASS__,
                'parenttype'                => get_class($item),
                $item->getForeignKeyField() => $item->getID(),
                'id'                        => -1];
            Ajax::updateItemJsCode(
                "viewtranslationbasketobj" .  $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
                "<a class='submit btn btn-primary' href='javascript:addTranslationbasketobj" .
                $item->getID() . "$rand();'>" . __('Add a new translation') .
                "</a></div><br>";
        }
        $iterator = $DB->request([
            'FROM'  => getTableForItemType(__CLASS__),
            'WHERE' => [
                'itemtype' => $item->getType(),
                'items_id' => $item->getID(),
                'field'    => ['<>', 'completename']
            ],
            'ORDER' => ['language ASC']
        ]);
        if (count($iterator)) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='left'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='4'>" . __("List of translations", "metademands") . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("Language") . "</th>";
            echo "<th>" .__('Field') . "</th>";
            echo "<th>" . __("Value") . "</th></tr>";
            foreach ($iterator as $data) {
                $onhover = '';
                if ($canedit) {
                    $onhover = "style='cursor:pointer'
                           onClick=\"viewEditTranslationbasketobj"  . $data['id'] . "$rand();\"";
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
                    echo "function viewEditTranslationbasketobj"  . $data['id'] . "$rand() {\n";
                    $params = ['type'                      => __CLASS__,
                        'parenttype'                => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id'                        => $data["id"]];
                    Ajax::updateItemJsCode(
                        "viewtranslation" . $item->getType() . $item->getID() . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo \Dropdown::getLanguageName($data['language']);
                echo "</td><td $onhover>";
                $searchOption = $item->getSearchOptionByField('field', $data['field']);
                if (empty($searchOption)) {
                    if (isset($item->fields["custom_values"]) && !empty($item->fields["custom_values"])) {
                        $custom = FieldParameter::_unserialize($item->fields["custom_values"]);

                        foreach ($custom as $key => $val) {
                            if ("custom" . $key == $data["field"]) {
                                $searchOption['name'] = $val;
                            }
                        }
                    }
                }
                echo $searchOption['name'] . "</td>";
                echo "<td $onhover>" . $data['value'] . "</td>";
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
            echo "<th class='center b'>" . __("No translation has been added yet") . "</th></tr></table>";
        }
        return true;
    }

    public static function getEnumField(): array
    {
        return [
            self::TRANSLATE_FIELD_NAME => __('Name'),
            self::TRANSLATE_FIELD_DESCRIPTION => __('Description'),
        ];
    }


    /**
     * Display translation form
     *
     * @param int $ID field (default -1)
     * @param     $options   array
     *
     * @return bool
     */
    function showForm($ID = -1, $options = [])
    {
        global $CFG_GLPI;

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
        echo "<td>" . __('Language') . "</td>";
        echo "<td>";
        echo Html::hidden('items_id', ['value' => $item->getID()]);
        echo Html::hidden('itemtype', ['value' => get_class($item)]);
        if ($ID > 0) {
            echo Html::hidden('language', ['value' => $this->fields['language']]);
            echo \Dropdown::getLanguageName($this->fields['language']);
        } else {
            $rand   = \Dropdown::showLanguages(
                "language",
                ['display_none' => false,
                'value'        => $_SESSION['glpilanguage']]
            );
            $params = ['language' => '__VALUE__',
                'itemtype' => get_class($item),
                'items_id' => $item->getID()];
//            Ajax::updateItemOnSelectEvent("dropdown_language$rand",
//                "span_fields",
//                PLUGIN_METADEMANDS_WEBDIR . "/ajax/updateTranslationFields.php",
//                $params);
        }
        echo "</td><td colspan='2'>&nbsp;</td></tr>";
        $basket = $options['parent'];
        echo "<tr class='tab_bg_1'><td>" . __('Value') . "</td>";
        echo "<td>";
        \Dropdown::showFromArray(
            'field',
            self::getEnumField(),
            [
            'value' => $this->fields['field']
            ]
        );
        echo "</td>";
        echo "<td>" . __('Translation', 'metademands') . "</td>";
        echo "<td>";
        Html::textarea(['name'            => 'value',
            'value'           => $this->fields["value"],
            'cols'       => 80,
            'rows'       => 3,
            'enable_richtext' => false]);
        echo "</td>";
        echo "</tr>\n";
        $this->showFormButtons($options);
        return true;
    }

    /**
     * Check if an item can be translated
     * It be translated if translation if globally on and item is an instance of CommonDropdown
     * or CommonTreeDropdown and if translation is enabled for this class
     *
     * @param \CommonGLPI $item
     *
     * @return true if item can be translated, false otherwise
     */
    static function canBeTranslated(CommonGLPI $item)
    {

        return ($item instanceof Basketobject);
    }


    /**
     * Return the number of translations for an item
     *
     * @param item
     *
     * @return int number of translations for this item
     */
    static function getNumberOfTranslationsForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            $dbu->getTableForItemType(__CLASS__),
            ["items_id" => $item->getID()]
        );
    }

    /**
     * Returns the translation of the field
     *
     *
     */
    public static function displayTranslatedValue($id, $field, $lang = '')
    {
        global $DB;

        $res = "";
        // Make new database object and fill variables
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_metademands_basketobjecttranslations',
            'WHERE' => [
                'itemtype' => self::getType(),
                'items_id' => $id,
                'field'    => $field,
                'language' => $_SESSION['glpilanguage']
            ]]);
        if ($lang != $_SESSION['glpilanguage'] && $lang != '') {
            $iterator2 = $DB->request([
                'FROM'  => 'glpi_plugin_metademands_basketobjecttranslations',
                'WHERE' => [
                    'itemtype' => self::getType(),
                    'items_id' => $id,
                    'field'    => $field,
                    'language' => $lang
                ]]);
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
}
