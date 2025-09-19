<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2022 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
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
    $filename = "sign-" . $_POST['metademands_id'] . "-" . $login . ".png";
    $filepath = GLPI_TMP_DIR . '/' . $filename;
    if (file_put_contents($filepath, $decoded_image)) {
        $ok = true;
    }
    $prefix   = '';
    $dest = Toolbox::savePicture($filepath, $prefix);

}

echo $dest;
