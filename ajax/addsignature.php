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
use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\Metademand;

header("Content-Type: text/html; charset=UTF-8");

Html::header_nocache();

// Uploading a picture into the GLPI document tree must not be reachable anonymously.
// Gate on the same rights as the wizard entry point (see front/wizard.form.php): this
// route serves the wizard and the step form, both of which already require one of them.
// It replaces a Session::checkLoginUser() that was dead code on a routed GLPI 11 entry
// point, authentication being enforced by the framework.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

$dest = false;

// Refuse a payload larger than what a signature pad can legitimately produce, before decoding it.
$signature_max_bytes = 2 * 1024 * 1024;

if (isset($_POST['datasign']) && !empty($_POST['datasign'])) {
    $metademands_id = (int) ($_POST['metademands_id'] ?? 0);

    // Bind the write to a meta-demand the caller may actually fill in, with the same
    // access check as ajax/createmetademands.php (rights + entity boundary).
    $metademands = new Metademand();
    if (
        !$metademands->getFromDB($metademands_id)
        || !($metademands->canCreate() || Group::isUserHaveRight($metademands_id))
        || !Session::haveAccessToEntity($metademands->fields['entities_id'], $metademands->fields['is_recursive'])
    ) {
        throw new AccessDeniedHttpException();
    }

    // Only accept the exact data URL the signature pad produces: a strict base64 PNG payload.
    $datasign = (string) $_POST['datasign'];
    if (strlen($datasign) > $signature_max_bytes || !str_starts_with($datasign, 'data:image/png;base64,')) {
        throw new BadRequestHttpException();
    }
    $encoded_image = substr($datasign, strlen('data:image/png;base64,'));
    $decoded_image = base64_decode($encoded_image, true);
    if ($decoded_image === false || $decoded_image === '') {
        throw new BadRequestHttpException();
    }

    $login = Session::getLoginUserID();
    $filename = "sign-" . $metademands_id . "-" . $login . ".png";
    $filepath = GLPI_TMP_DIR . '/' . $filename;
    if (file_put_contents($filepath, $decoded_image) === false) {
        throw new BadRequestHttpException();
    }

    $prefix   = '';
    try {
        // Toolbox::savePicture() validates the real mime type and moves the file when it succeeds;
        // the temporary copy must not survive a rejection either.
        $dest = Toolbox::savePicture($filepath, $prefix);
    } finally {
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }

    if ($dest !== false) {
        // Remember signatures created by this user so that only they may delete
        // them later (see removesignature.php) — prevents cross-user deletion.
        $_SESSION['plugin_metademands']['signatures'][$dest] = true;
    }
}

echo $dest;
