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

use PluginOrdermaterialMetademand;
use PluginOrderfollowupMetademand;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['action'])) {

    switch ($_POST['action']) {
        case "loadTotalrow" :

            if (isset($_POST['quantity'])
                && $_POST['quantity'] > 0) {
                $totalrow = $_POST['quantity'];
                if (Plugin::isPluginActive('ordermaterial')) {
                    $ordermaterialmeta = new PluginOrdermaterialMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']])
                        && isset($_POST['estimated_price']) && $_POST['estimated_price'] > 0) {
                        $totalrow = $_POST['quantity'] * $_POST['estimated_price'];

                    }
                    if (isset($_POST['estimated_price']) && $_POST['estimated_price'] > 0) {
                        echo Html::formatNumber($totalrow, false, 2);
                        echo " €";
                    } else {
                        echo $totalrow;
                    }
                }
                if (Plugin::isPluginActive('orderfollowup')) {
                    $ordermaterialmeta = new PluginOrderfollowupMetademand();
                    if ($ordermaterialmeta->getFromDBByCrit(['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']])
                        && isset($_POST['unit_price']) && $_POST['unit_price'] > 0) {
                        $totalrow = $_POST['quantity'] * $_POST['unit_price'];

                    }
                    if (isset($_POST['unit_price']) && $_POST['unit_price'] > 0) {
                        echo Html::formatNumber($totalrow, false, 2);
                        echo " €";
                    } else {
                        echo $totalrow;
                    }
                }

                echo "<input class='form-check-input' type='hidden' check='" . $_POST['check'] . "' name='" . $_POST['name'] . "' key='" . $_POST['key'] . "' id='" . $_POST['name'] . "' value='" . $_POST['key'] . "'>";
//                if (!isset($_SESSION['plugin_metademands']['total_order'])) {
//                    $_SESSION['plugin_metademands']['total_order'] = $totalrow;
//                } else {
//                    $_SESSION['plugin_metademands']['total_order'] += $totalrow;
//                }
            }

            break;
        case "loadGrandTotal" :
//            if (isset($_SESSION['plugin_metademands']['total_order'])) {
//                echo $_SESSION['plugin_metademands']['total_order']." €";
//            } else {
//                echo "0 €";
//            }
            break;
    }
}
