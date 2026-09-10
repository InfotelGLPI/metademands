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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\Metademand;

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

// Mutating the wizard session state must not be reachable anonymously; the right to fill the
// targeted meta-demand is enforced right below, as ajax/addsignature.php already does.
Session::checkLoginUser();

$data_by_free = [];

$datas = $_POST['datas'] ?? null;
if (!is_array($datas)) {
    throw new BadRequestHttpException();
}

$fields_id      = (int) ($datas['fields_id'] ?? 0);
$metademands_id = (int) ($datas['metademands_id'] ?? 0);

// Both identifiers come from the client and are used as raw session keys: bind the write to a
// meta-demand the caller may actually fill in (rights + entity boundary), then make sure the
// targeted field really belongs to it.
$metademand = new Metademand();
if (
    !$metademand->getFromDB($metademands_id)
    || !($metademand->canCreate() || Group::isUserHaveRight($metademands_id))
    || !Session::haveAccessToEntity($metademand->fields['entities_id'], $metademand->fields['is_recursive'])
) {
    throw new AccessDeniedHttpException();
}

$field = new Field();
if (
    !$field->getFromDB($fields_id)
    || (int) $field->fields['plugin_metademands_metademands_id'] !== $metademands_id
) {
    throw new AccessDeniedHttpException();
}

// A free table line is the flat map of scalar cells built by public/scripts/metademands_freelines.js.
// Refuse anything nested and bound both the cell count and the cell length, so the session storage
// cannot be inflated from the client.
$max_cells_per_line = 100;
$max_cell_length    = 255;
$max_lines_per_field = 200;

$normalize_free_line = static function ($line) use ($max_cells_per_line, $max_cell_length) {
    if (!is_array($line) || count($line) > $max_cells_per_line || !isset($line['id'])) {
        throw new BadRequestHttpException();
    }
    $normalized = [];
    foreach ($line as $key => $value) {
        if (!is_scalar($value) && $value !== null) {
            throw new BadRequestHttpException();
        }
        $normalized[$key] = mb_substr((string) $value, 0, $max_cell_length);
    }
    $normalized['id'] = (int) $normalized['id'];

    return $normalized;
};

if (isset($datas['add']) || isset($datas['update'])) {
    $line   = $normalize_free_line($datas['add'] ?? $datas['update']);
    $stored = $_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id] ?? [];
    if (!isset($stored[$line['id']]) && count($stored) >= $max_lines_per_field) {
        throw new BadRequestHttpException();
    }
    $_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$line['id']] = $line;
} elseif (isset($_POST['type']) && $_POST['type'] == 'remove' && isset($datas['remove'])) {
    $remove = (int) $datas['remove'];

    if (isset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$remove])) {
        unset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$remove]);
    }
    if (isset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id])) {
        foreach (($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id]) as $key => $value) {
            if (is_array($value) && isset($value['id']) && (int) $value['id'] === $remove) {
                unset($_SESSION['plugin_metademands'][$metademands_id]['freetables'][$fields_id][$key]);
            }
        }
    }
    if (isset($_SESSION['plugin_metademands'][$metademands_id]['fields'][$fields_id])) {
        foreach (($_SESSION['plugin_metademands'][$metademands_id]['fields'][$fields_id]) as $key => $value) {
            if (is_array($value) && isset($value['id']) && (int) $value['id'] === $remove) {
                unset($_SESSION['plugin_metademands'][$metademands_id]['fields'][$fields_id][$key]);
            }
        }
    }
}
