<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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
 * Class PluginMetademandsMenu
 */
class PluginMetademandsMenu extends CommonDBTM {

   static $rightname = 'plugin_metademands';

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
   }

   function showMenu() {
      global $CFG_GLPI;

      echo "<div align='center'>";
      echo "<table class='tab_cadre' cellpadding='5' height='150'>";
      echo "<tr>";
      echo "<th colspan='2'>".PluginMetademandsMetademand::getTypeName(2)."</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1' style='background-color:white;'>";

      //Enter Metademand
      $metademands = new PluginMetademandsMetademand();
      $data = $metademands->listMetademands(true);

      if (count($data)) {
         echo "<td class='center'>";
         echo "<a href=\"./wizard.form.php?step=1\">";
         echo "<img src='".$CFG_GLPI["root_doc"]."/plugins/metademands/pics/metademands.png' alt=\"".__('Enter a demand', 'metademands')."\">";
         echo "<br>".__('Enter a demand', 'metademands')."</a>";
         echo "</td>";
      } else {
         echo "<td class='center'>";
         echo __('No metademands available', 'metademands');
         echo "</td>";
      }
      if ($this->canCreate()) {
         //Configure metademand
         echo "<td class='center'>";
         echo "<a href=\"./metademand.php\">";
         echo "<i class='fas fa-cogs fa-5x' style='color:#000' title=\"".__('Configure demands', 'metademands')."\"></i><br>";
         echo "<br>".__('Configure demands', 'metademands')."</a>";
         echo "</td>";
      }

      echo "</tr>";
      echo "</table></div>";
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['helpdesk']['types']['PluginMetademandsMetademand'])) {
         unset($_SESSION['glpimenu']['helpdesk']['types']['PluginMetademandsMetademand']);
      }
      if (isset($_SESSION['glpimenu']['helpdesk']['content']['pluginmetademandsmetademand'])) {
         unset($_SESSION['glpimenu']['helpdesk']['content']['pluginmetademandsmetademand']);
      }
   }
}