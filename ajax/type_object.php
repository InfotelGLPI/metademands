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
use GlpiPlugin\Metademands\Metademand;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

Session::checkRight("plugin_metademands", READ);

global $PLUGIN_HOOKS;

$object = $_POST['object_to_create'] ?? null;

if ($object === null) {
    return;
}

$mode                   = '';
$type_dropdown_html     = '';
$category_dropdown_html = '';
$plugin_html            = '';

if ($object == 'Ticket') {
    $mode = 'ticket';

    // Both helpers write to the standard output: capture them so the widget lands in
    // the field column of the template instead of ahead of the whole response.
    ob_start();
    $rand = \Ticket::dropdownType('type', ['display_emptychoice' => true]);
    Ajax::updateItemOnSelectEvent(
        "dropdown_type$rand",
        "show_category_by_type",
        PLUGIN_METADEMANDS_WEBDIR . "/ajax/dropdownITILCategories.php",
        ['type'             => '__VALUE__',
            'value'            => 0,
            'object_to_create' => $object,
            'entity_restrict'  => $_SESSION['glpiactiveentities']],
    );
    $type_dropdown_html = ob_get_clean();
} elseif ($object == 'Problem' || $object == 'Change') {
    $mode = 'category';

    $criteria = $object == 'Problem' ? ['is_problem' => 1] : ['is_change' => 1];
    $criteria += getEntitiesRestrictCriteria(
        \ITILCategory::getTable(),
        'entities_id',
        $_SESSION['glpiactiveentities'],
        true,
    );

    $dbu        = new DbUtils();
    $categories = [];
    foreach ($dbu->getAllDataFromTable(\ITILCategory::getTable(), $criteria) as $category) {
        $categories[$category['id']] = $category['completename'];
    }

    ob_start();
    \Dropdown::showFromArray(
        'itilcategories_id',
        $categories,
        ['width'    => '100%',
            'multiple' => true,
            'entity'   => $_SESSION['glpiactiveentities']],
    );
    $category_dropdown_html = ob_get_clean();
} elseif (isset($PLUGIN_HOOKS['metademands'])) {
    $mode = 'plugin';

    foreach (array_keys($PLUGIN_HOOKS['metademands']) as $plug) {
        if (Plugin::isPluginActive($plug)) {
            $plugin_html .= (string) Metademand::getPluginUniqueDropdown($plug);
        }
    }
}

TemplateRenderer::getInstance()->display('@metademands/ajax/type_object.html.twig', [
    'mode'                   => $mode,
    'type_dropdown_html'     => $type_dropdown_html,
    'category_dropdown_html' => $category_dropdown_html,
    'plugin_html'            => $plugin_html,
]);
