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

$ok = false;

if (isset($_POST['datasign']) && isset($_POST['metademands_id'])) {
    $datasign = (string) $_POST['datasign'];
    // Only delete a signature this user actually created (tracked in session at
    // upload time). Prevents deleting another user's signature via a forged path.
    // deletePicture() additionally confines removal to GLPI_PICTURE_DIR.
    if (
        isset($_SESSION['plugin_metademands']['signatures'][$datasign])
        && !str_contains($datasign, '..')
    ) {
        Toolbox::deletePicture($datasign);
        unset($_SESSION['plugin_metademands']['signatures'][$datasign]);
        $ok = true;
    }
}

echo $ok;
