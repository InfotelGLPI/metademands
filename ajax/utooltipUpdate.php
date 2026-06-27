<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
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

// Quand users_id est 0 (ex. initialisation select2), calculer la valeur par défaut
// depuis les paramètres du champ transmis par Dropdownobject::showWizardField()
if (empty($_POST['users_id']) || (int)$_POST['users_id'] === 0) {
    if (!empty($_POST['default_use_id_requester']) && (int)$_POST['default_use_id_requester'] === 1) {
        $_POST['users_id'] = Session::getLoginUserID();
    } elseif (!empty($_POST['default_use_id_requester_supervisor']) && (int)$_POST['default_use_id_requester_supervisor'] === 1) {
        $supervisorUser = new User();
        $supervisorUser->getFromDB(Session::getLoginUserID());
        $_POST['users_id'] = $supervisorUser->fields['users_id_supervisor'] ?? 0;
    }
}

$content = " ";
$user = new User();
if (isset($_POST['users_id']) && $_POST["users_id"] > 0) {

    $user_id = $_POST['users_id'];
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

$_POST['name'] = "tooltip_user" . $_POST["id_fielduser"];
$_POST['rand'] = "";
Ajax::commonDropdownUpdateItem($_POST);
