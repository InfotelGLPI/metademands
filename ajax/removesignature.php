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
use GlpiPlugin\Metademands\Fields\Signature;
use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\Metademand;

header("Content-Type: text/html; charset=UTF-8");

Html::header_nocache();

// Same gate as its twin ajax/addsignature.php, which uploads the very file this route
// deletes: this was the only endpoint of the plugin carrying no authorization check at
// all. The session table read below is a functional filter, not an authorization one --
// it only proves the caller uploaded that file -- so a profile stripped of every right
// of the plugin still ran the body instead of being refused upstream. Placed before any
// read of $_POST so that widening the source of the path later cannot outrun the guard.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

$ok = false;

if (isset($_POST['datasign']) && isset($_POST['metademands_id'])) {
    $metademands_id = (int) $_POST['metademands_id'];

    // Replay the entity boundary of the write path: the caller must still be allowed to
    // fill in the meta-demand the signature belongs to, in an entity it may reach. The
    // pad posts the same metademands_id to both routes, so anything the add accepted the
    // remove accepts too.
    $metademands = new Metademand();
    if (
        !$metademands->getFromDB($metademands_id)
        || !($metademands->canCreate() || Group::isUserHaveRight($metademands_id))
        || !Session::haveAccessToEntity($metademands->fields['entities_id'], $metademands->fields['is_recursive'])
    ) {
        throw new AccessDeniedHttpException();
    }

    $datasign = (string) $_POST['datasign'];
    // Only delete a signature this user actually created (tracked in session at
    // upload time). Prevents deleting another user's signature via a forged path.
    // deletePicture() additionally confines removal to GLPI_PICTURE_DIR.
    if (
        Signature::isOwnUpload($datasign)
        && !str_contains($datasign, '..')
    ) {
        Toolbox::deletePicture($datasign);
        Signature::forgetUpload($datasign);
        $ok = true;
    }
}

echo $ok;
