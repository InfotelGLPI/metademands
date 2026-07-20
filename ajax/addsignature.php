<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

header("Content-Type: text/html; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

$dest = false;

if (isset($_POST['datasign']) && !empty($_POST['datasign'])) {

    $encoded_image = explode(",", $_POST['datasign'])[1];
    $decoded_image = base64_decode($encoded_image);

    $login = Session::getLoginUserID();
    $metademands_id = (int) ($_POST['metademands_id'] ?? 0);
    $filename = "sign-" . $metademands_id . "-" . $login . ".png";
    $filepath = GLPI_TMP_DIR . '/' . $filename;
    if (file_put_contents($filepath, $decoded_image)) {
        $ok = true;
    }
    $prefix   = '';
    $dest = Toolbox::savePicture($filepath, $prefix);

    if ($dest !== false) {
        // Remember signatures created by this user so that only they may delete
        // them later (see removesignature.php) — prevents cross-user deletion.
        $_SESSION['plugin_metademands']['signatures'][$dest] = true;
    }
}

echo $dest;
