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

$field = new Field();

if (!isset($_POST["fields_id"]) || !$field->getFromDB($_POST["fields_id"])) {
    return;
}

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
