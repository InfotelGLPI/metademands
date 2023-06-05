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
 * Class PluginMetademandsConfig
 */
class PluginMetademandsConfig extends CommonDBTM {

   static $rightname = 'plugin_metademands';

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


   static function canView() {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   /**
    * @return bool
    */
   static function canCreate() {
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
            switch ($tabnum) {
                case 1:
                    $item->showConfigForm();
                    break;
            }
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
        if (!$withtemplate) {
            $ong[1] = __('Setup', 'metademands');
            return $ong;
        }
        return '';
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
        $this->addStandardTab('PluginMetademandsTools', $ong, $options);
        $this->addStandardTab('PluginMetademandsCheckSchema', $ong, $options);

        return $ong;
    }

   /**
    * @return bool
    */
   function showConfigForm() {
      if (!$this->canCreate() || !$this->canView()) {
         return false;
      }

      $config = PluginMetademandsConfig::getInstance();

      echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL('PluginMetademandsConfig') . "'>";

      echo "<div align='center'><table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='6'>" . __('Configuration of the meta-demand plugin', 'metademands') . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Enable the update / add of simple ticket to metademand', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('simpleticket_to_metademand', $config['simpleticket_to_metademand']);
      echo "</td>";

      echo "<td>";
      echo __("Enable display metademands via icons", 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("display_type", $config['display_type']);
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
      Dropdown::showYesNo('childs_parent_content', $config['childs_parent_content']);
      echo "</td>";

      echo "<td>";
      echo __('Create PDF', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('create_pdf', $config['create_pdf']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>";
      echo __('Use drafts', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('use_draft', $config['use_draft']);
      echo "</td>";

      echo "<td>";
      echo __('Show only differences between last form and new form in ticket content', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('show_form_changes', $config['show_form_changes']);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>";
      echo __('Language Tech', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showLanguages("languageTech", ['value' => $config['languageTech']]);
      echo "</td>";

      echo "<td>";
      echo __('Display metademands list into ServiceCatalog plugin', 'metademands');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('display_buttonlist_servicecatalog', $config['display_buttonlist_servicecatalog']);
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
               'id'       => $icon_selector_id,
               'selected' => $config['fa_servicecatalog'],
               'style'    => 'width:175px;'
            ]
         );

         echo Html::script('js/Forms/FaIconSelector.js');
         echo Html::scriptBlock(<<<JAVASCRIPT
         $(
            function() {
               var icon_selector = new GLPI.Forms.FaIconSelector(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
            }
         );
JAVASCRIPT
         );

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
   public static function getInstance() {
      if (!isset(self::$instance)) {
         $temp = new PluginMetademandsConfig();

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
   function getConfigFromDB($options = []) {
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
