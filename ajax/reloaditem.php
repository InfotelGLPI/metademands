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
use GlpiPlugin\Metademands\Field;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Gate on the same rights as the wizard entry point (see front/wizard.form.php):
// every ajax/ route of the plugin carries its own authorization.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

if (($_POST['action'] ?? null) !== 'reloaditem'
    || !isset($_POST["type"])
    || !in_array($_POST['type'], Field::$field_withobjects)) {
    return;
}

// Belt and braces: the plugin branch of dropdownFieldItems() may write to the output
// buffer rather than honour 'display'.
ob_start();
$returned = Field::dropdownFieldItems($_POST['type'], [
    'with_empty_value' => true,
    'display'          => false,
]);
$item_dropdown_html = ob_get_clean() . (is_string($returned) ? $returned : '');

TemplateRenderer::getInstance()->display('@metademands/ajax/reload_item.html.twig', [
    'item_dropdown_html' => $item_dropdown_html,
]);
