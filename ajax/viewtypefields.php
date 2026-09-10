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

if (!isset($_POST['step'])) {
    $_POST['step'] = 'default';
}

switch ($_POST['step']) {
    case 'order':
        $fields = new Field();

        $params['rank'] = $_POST['rank'];
        $params['id'] = $_POST['fields_id'];
        $params['plugin_metademands_fields_id'] = $_POST['previous_fields_id'];
        $params['plugin_metademands_metademands_id'] = $_POST["metademands_id"];
        $fields->showOrderDropdown($params);
        break;
    case 'object':
        global $CFG_GLPI;
        if ($_POST['type'] === 'text') {
            TemplateRenderer::getInstance()->display('@metademands/ajax/link_to_user_field.html.twig');
        } else {
            // The second label only makes sense for the two interval types. The legacy
            // script interpolated the posted type into the JS source to decide that.
            $show_label2 = in_array($_POST['type'], ['datetime_interval', 'date_interval'], true)
                ? 'inline'
                : 'none';
            echo Html::scriptBlock(
                "document.getElementById('show_label2').style.display = '$show_label2';",
            );

            $randItem = Field::dropdownFieldItems(
                $_POST["type"],
                ['value' => $_POST['item'], 'rand' => $_POST["rand"]],
            );
            $paramsItem = [
                'value' => '__VALUE__',
                'item' => '__VALUE__',
                'type' => $_POST['type'],
                'metademands_id' => $_POST["metademands_id"],
            ];

            Ajax::updateItemOnSelectEvent(
                'dropdown_item' . $randItem,
                "show_values",
                PLUGIN_METADEMANDS_WEBDIR .
                "/ajax/viewtypefields.php?id=" . $_POST['metademands_id'],
                $paramsItem,
            );
        }
        break;
    case 'listfieldbytype':
        $fields = new Field();
        $crit = ["type" => $_POST['value']];
        $rand = Field::dropdown(['name' => "existing_field_id", "condition" => $crit]);
        $params = ['fields_id' => '__VALUE__'];
        Ajax::updateItemOnSelectEvent(
            'dropdown_existing_field_id' . $rand,
            "show_fields_infos",
            PLUGIN_METADEMANDS_WEBDIR .
            "/ajax/viewfieldinfos.php",
            $params,
        );
        break;
    default:
        break;
}
