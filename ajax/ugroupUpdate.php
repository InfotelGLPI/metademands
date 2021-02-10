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
if (strpos($_SERVER['PHP_SELF'], "ugroupUpdate.php")) {
   include('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();
$fieldGroup = new PluginMetademandsField();
if (!isset($_POST['fields_id'])) {
   $fieldGroup->getFromDBByCrit(['link_to_user' => $_POST['id_fielduser'], 'type' => "dropdown_object", 'item' => Group::getType()]);

   $_POST["field"] = "field[" . $fieldGroup->fields['id'] . "]";
} else {
   $fieldGroup->getFromDB($_POST['fields_id']);
}


//chercher les champs de la meta avec param : updatefromthisfield
$groups_id = 0;
if ((isset($_POST['value']) && ($_POST["value"] > 0))) {

   $user = new User();
   if ($user->getFromDB($_POST["value"])) {
      $groups_id = PluginMetademandsField::getUserGroup($_SESSION['glpiactiveentities'],
                                                        $_POST["value"],
                                                        true);
   }

}
//TODO retrieve from field
$cond = [];
if (!empty($fieldGroup->fields['custom_values'])) {
   $options = PluginMetademandsField::_unserialize($fieldGroup->fields['custom_values']);
   foreach ($options as $type_group => $values) {
      $cond[$type_group] = $values;
   }
}

Group::dropdown(['name'      => $_POST["field"],
                 'entity'    => $_SESSION['glpiactiveentities'],
                 'value'     => $groups_id,
                 'readonly'  => true,
                 'condition' => $cond,
                ]);

$_POST['name'] = "group_user";
$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);
