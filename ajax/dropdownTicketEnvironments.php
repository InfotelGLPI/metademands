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

if (strpos($_SERVER['PHP_SELF'], "dropdownTicketEnvironments.php")) {
   include ('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();

   Session::checkLoginUser();
}
if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

$itilenvironment = new PluginMetademandsITILEnvironment();
$opt = ['entity' => $_POST["entity_restrict"]];

if (!isset($_POST["used"])) {
   $_POST["used"] = [];
}

$opt['used'] = $_POST["used"];

if (($_POST['itilapplications_id'] == 0 || $_POST['itilapplications_id'] == 1)) {
   $opt['value'] = PluginMetademandsITILEnvironment::NO_OBJECT;
   $itilenvironment_data = $itilenvironment->find('`entities_id` = '.$_POST['entity_restrict']);
   foreach ($itilenvironment_data as $id => $values) {
      $opt['used'][] = $id;
   }
} else if (($_POST['itilapplications_id'] != 0 && $_POST['itilapplications_id'] != 1)) {
   $opt['value'] =  0;
   $opt['used'][]  =  PluginMetademandsITILEnvironment::NO_OBJECT;
}

Dropdown::show('PluginMetademandsITILEnvironment', $opt);
