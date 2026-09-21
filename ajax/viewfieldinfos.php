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

Session::checkRight("plugin_metademands", UPDATE);

$fields_id = (int) ($_POST["fields_id"] ?? 0);

// The field dropdowns that drive this endpoint carry an empty choice: posting it
// is a legitimate no-op, not an access error.
if ($fields_id <= 0) {
    return;
}

$field = new Field();

// Field is a CommonDBChild of Metademand: check() resolves the parent and applies
// checkEntity() on it. The right bit above is global to the plugin and carries no
// entity, so without this the whole rendering below -- label, type, default and
// custom values of the field -- was readable for any posted identifier, including
// fields belonging to meta-demands of another entity. Same guard as
// ajax/show_check_value.php.
$field->check($fields_id, READ);

$params                 = Field::getAllParamsFromField($field);
$params['is_mandatory'] = 0;

// getFieldInput() echoes the widget internally but the parent_field case returns a
// string: capture both, otherwise the example is printed ahead of the card.
ob_start();
$example_ret  = Field::getFieldInput([], $params, false, 0, 0, false, "");
$example_html = ob_get_clean();
if (is_string($example_ret)) {
    $example_html .= $example_ret;
}

TemplateRenderer::getInstance()->display('@metademands/forms/field_informations.html.twig', [
    'type_name'    => Field::getFieldTypesName($field->fields["type"]),
    'example_html' => $example_html,
]);
