<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Orderfollowup\Metademand;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Gate on the same rights as the wizard entry point (see front/wizard.form.php):
// every ajax/ route of the plugin carries its own authorization.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

if (($_POST['action'] ?? null) !== 'loadTotalrow'
    || !isset($_POST['quantity'])
    || $_POST['quantity'] <= 0) {
    return;
}

// The two order plugins price a basket line the same way, each with its own column
// name for the unit price. The legacy code tested them in two independent `if`, so a
// site running both printed the total twice.
$totalrow    = $_POST['quantity'];
$unit_price  = null;
$has_pricing = false;

if (Plugin::isPluginActive('ordermaterial')) {
    $has_pricing = true;
    $unit_price  = $_POST['estimated_price'] ?? null;
    $order_meta  = new PluginOrdermaterialMetademand();
} elseif (Plugin::isPluginActive('orderfollowup')) {
    $has_pricing = true;
    $unit_price  = $_POST['unit_price'] ?? null;
    $order_meta  = new Metademand();
}

if ($has_pricing
    && $unit_price > 0
    && $order_meta->getFromDBByCrit(['plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']])) {
    $totalrow = $_POST['quantity'] * $unit_price;
}

TemplateRenderer::getInstance()->display('@metademands/forms/totalrow.html.twig', [
    'show_total' => $has_pricing,
    'is_price'   => $unit_price > 0,
    'total'      => $unit_price > 0 ? Html::formatNumber($totalrow, false, 2) : (string) $totalrow,
    'check'      => $_POST['check'] ?? '',
    'name'       => $_POST['name'] ?? '',
    'key'        => $_POST['key'] ?? '',
]);
