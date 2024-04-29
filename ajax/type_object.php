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

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

if ($_POST['object_to_create'] != NULL) {
   $object = $_POST['object_to_create'];

   if ($object == 'Ticket') {
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . _n('Type', 'Types', 1) . "</td>";
      echo "<td>";
      $opt  = [
         'display_emptychoice' => true,
      ];
      $rand = Ticket::dropdownType('type', $opt);

      $params = ['type'             => '__VALUE__',
                 'value'            => 0,
                 'object_to_create' => $object,
                 'entity_restrict'  => $_SESSION['glpiactiveentities']];

      Ajax::updateItemOnSelectEvent("dropdown_type$rand", "show_category_by_type",
                                    PLUGIN_METADEMANDS_WEBDIR. "/ajax/dropdownITILCategories.php",
                                    $params);
      echo "</td>";

      echo "<td>" . __('Category') . "</td>";
      echo "<td>";

      echo "<span id='show_category_by_type'>";
      echo "</span>";
      echo "</td>";
      echo "</tr>";
   } elseif ($object == 'Problem' || $object == 'Change') {
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'></td>";
      echo "</td>";

      echo "<td>" . __('Category') . "</td>";
      echo "<td>";

      if ($object == 'Problem') {
         $criteria = ['is_problem' => 1];
      } elseif ($object == 'Change') {
         $criteria = ['is_change' => 1];
      }


      $criteria += getEntitiesRestrictCriteria(
         \ITILCategory::getTable(),
         'entities_id',
         $_SESSION['glpiactiveentities'],
         true
      );

      $dbu    = new DbUtils();
      $result = $dbu->getAllDataFromTable(ITILCategory::getTable(), $criteria);
      $temp   = [];
      foreach ($result as $item) {
         $temp[$item['id']] = $item['completename'];
      }
      Dropdown::showFromArray('itilcategories_id', $temp,
                              ['width'    => '100%',
                               'multiple' => true,
                               'entity'   => $_SESSION['glpiactiveentities']]);
      echo "</td>";
      echo "</tr>";
   } else {
       //TODO ELCH Add Hook for define linked category
   }
}

