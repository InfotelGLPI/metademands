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

use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Wizard;

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'], "utooltipUpdate.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkLoginUser();
$fieldUser = new Field();

if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']]) && !isset($_POST['value'])) {
    $_POST['value'] = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'][$_POST['id_fielduser']];
}

$content = " ";
$user = new User();
if (isset($_POST['value']) && $_POST["value"] > 0) {

    $user_id = $_POST['value'];
    $field_id = $_POST['id_fielduser'];
    $user_tooltip = new User();
    if ($user_id > 0 && $user_tooltip->getFromDB($user_id)) {
        $display = "alert-info";
        $color = "#000";
        $class = "class='alert $display alert-dismissible fade show informations'";
        echo "<br><br><div $class style='display:flex;align-items: center;'>";
        echo "<div style='color: $color;'>";
        Wizard::showUserInformations($user_tooltip);
        echo "</div>";
        echo "</div>";
    }
}

$_POST['name'] = "tooltip_user";
$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);
