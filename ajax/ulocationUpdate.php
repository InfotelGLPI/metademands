<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "ulocationUpdate.php")) {
   include('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();
$fieldUser = new PluginMetademandsField();
if (!isset($_POST['fields_id'])) {
   $fieldUser->getFromDBByCrit(['link_to_user' => $_POST['id_fielduser'], 'type' => "dropdown", 'item' => Location::getType()]);

   $_POST["field"] = "field[" . $fieldUser->fields['id'] . "]";
}


$locations_id = 0;
if ((isset($_POST['value']) && ($_POST["value"] > 0))) {

   $user = new User();
   if ($user->getFromDB($_POST["value"])) {
      $locations_id = $user->fields['locations_id'];
   }

}
Location::dropdown(['name'  => $_POST["field"],
                    'value' => $locations_id]);

$_POST['name'] = "location_user";
$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);
