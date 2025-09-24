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

namespace GlpiPlugin\Metademands;

use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Html;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Config
 */
class Config extends CommonDBTM
{
    public static $rightname = 'plugin_metademands';

    private static $instance;

    public function __construct()
    {
        global $DB;

        if ($DB->tableExists($this->getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Plugin setup', 'metademands');
    }

    public function getName($options = [])
    {
        return _n('Meta-Demand', 'Meta-Demands', 2, 'metademands');
    }


    public static function getIcon()
    {
        return "ti ti-share";
    }

    public static function canView(): bool
    {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }


    /**
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == __CLASS__) {
            $item->showConfigForm();
        }
        return true;
    }


    /**
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(self::getTypeName());
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
        //      $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab(Tools::class, $ong, $options);
        $this->addStandardTab(CheckSchema::class, $ong, $options);

        return $ong;
    }

    /**
     * @return bool
     */
    public function showConfigForm()
    {
        if (!$this->canCreate() || !$this->canView()) {
            return false;
        }

        $config = Config::getInstance();

        echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL(Config::class) . "'>";

        echo "<div align='center'><table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='6'>" . __('Configuration of the meta-demand plugin', 'metademands') . "</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Enable the update / add of simple ticket to metademand', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('simpleticket_to_metademand', $config['simpleticket_to_metademand']);
        echo "</td>";

        echo "<td>";
        echo __("Enable display metademands via icons", 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo("display_type", $config['display_type']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Parent ticket tag', 'metademands');
        echo "</td>";
        echo "<td>";
        $parent_ticket_tag =  isset($config["parent_ticket_tag"]) ? stripslashes($config["parent_ticket_tag"]) : "";
        echo Html::input('parent_ticket_tag', ['value' => $parent_ticket_tag, 'size' => 40]);
        echo "</td>";

        echo "<td>";
        echo __('Son ticket tag', 'metademands');
        echo "</td>";
        echo "<td>";
        $son_ticket_tag =  isset($config["son_ticket_tag"]) ? stripslashes($config["son_ticket_tag"]) : "";
        echo Html::input('son_ticket_tag', ['value' => $son_ticket_tag, 'size' => 40]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Childs tickets get parent content', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('childs_parent_content', $config['childs_parent_content']);
        echo "</td>";

        echo "<td>";
        echo __('Create PDF', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('create_pdf', $config['create_pdf']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Use drafts', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('use_draft', $config['use_draft']);
        echo "</td>";

        echo "<td>";
//        echo __('Show only differences between last form and new form in ticket content', 'metademands');
        echo "</td>";
        echo "<td>";
//        \Dropdown::showYesNo('show_form_changes', $config['show_form_changes']);
        echo Html::hidden('show_form_changes', ['value' =>0]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Language Tech', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showLanguages("languageTech", ['value' => $config['languageTech']]);
        echo "</td>";

        echo "<td>";
        echo __('Display metademands list into ServiceCatalog plugin', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('display_buttonlist_servicecatalog', $config['display_buttonlist_servicecatalog']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Allow adding groups by regex', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('add_groups_with_regex', $config['add_groups_with_regex']);
        echo "</td>";

        echo "<td>";
        echo __('See top metademands', 'metademands');
        echo "</td>";
        echo "<td>";
        \Dropdown::showYesNo('see_top', $config['see_top']);
        echo "</td>";

        echo "</tr>";


        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Icon for incidents', 'metademands');
        echo "</td>";
        echo "<td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon_incident',
            [$config['icon_incident'] => $config['icon_incident']],
            [
                'id' => $icon_selector_id,
                'selected' => $config['icon_incident'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

        echo "</td>";

        echo "<td>";
        echo __('Icon for requests', 'metademands');
        echo "</td>";
        echo "<td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon_request',
            [$config['icon_request'] => $config['icon_request']],
            [
                'id' => $icon_selector_id,
                'selected' => $config['icon_request'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>";
        echo __('Icon for problems', 'metademands');
        echo "</td>";
        echo "<td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon_problem',
            [$config['icon_problem'] => $config['icon_problem']],
            [
                'id' => $icon_selector_id,
                'selected' => $config['icon_problem'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

        echo "</td>";

        echo "<td>";
        echo __('Icon for changes', 'metademands');
        echo "</td>";
        echo "<td>";
        $icon_selector_id = 'icon_' . mt_rand();
        echo Html::select(
            'icon_change',
            [$config['icon_change'] => $config['icon_change']],
            [
                'id' => $icon_selector_id,
                'selected' => $config['icon_change'],
                'style' => 'width:175px;',
            ]
        );

        echo Html::script('js/modules/Form/WebIconSelector.js');
        echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

        echo "</td>";
        echo "</tr>";

        if ($config['display_buttonlist_servicecatalog'] == 1) {

            echo "<tr><th colspan='6'>" . __('Configuration of the Service Catalog plugin', 'metademands') . "</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>" . __('Title for Service Catalog widget', 'metademands') . "</td>";
            echo "<td colspan='2'>";
            Html::textarea(['name'            => 'title_servicecatalog',
                'value'           => $config['title_servicecatalog'],
                'enable_richtext' => false,
                'cols'            => 80,
                'rows'            => 3]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>" . __('Comment for Service Catalog widget', 'metademands') . "</td>";
            echo "<td colspan='2'>";
            Html::textarea(['name'            => 'comment_servicecatalog',
                'value'           => $config['comment_servicecatalog'],
                'enable_richtext' => false,
                'cols'            => 80,
                'rows'            => 3]);
            echo "</td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2'>";
            echo __('Icon for Service Catalog widget', 'metademands');
            echo "</td>";
            echo "<td colspan='2'>";
            $icon_selector_id = 'icon_' . mt_rand();
            echo Html::select(
                'fa_servicecatalog',
                [$config['fa_servicecatalog'] => $config['fa_servicecatalog']],
                [
                    'id' => $icon_selector_id,
                    'selected' => $config['fa_servicecatalog'],
                    'style' => 'width:175px;',
                ]
            );

            echo Html::script('js/modules/Form/WebIconSelector.js');
            echo Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

            echo "</td>";
            echo "</tr>";
        }
        echo "<tr><td class='tab_bg_2 center' colspan='6'>";
        echo Html::submit(_sx('button', 'Update'), ['name' => 'update_config', 'class' => 'btn btn-primary']);
        echo "</td></tr>";

        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * @return bool|mixed
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $temp = new Config();

            $data = $temp->getConfigFromDB();
            if ($data) {
                self::$instance = $data;
            }
        }

        return self::$instance;
    }

    /**
     * getConfigFromDB : get all configs in the database
     *
     * @param array $options
     *
     * @return bool|mixed
     */
    public function getConfigFromDB($options = [])
    {
        $table = $this->getTable();
        $where = [];
        if (isset($options['where'])) {
            $where = $options['where'];
        }
        $dbu        = new DbUtils();
        $dataConfig = $dbu->getAllDataFromTable($table, $where);
        if (count($dataConfig) > 0) {
            return array_shift($dataConfig);
        }

        return false;
    }
}
