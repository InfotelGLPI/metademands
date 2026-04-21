<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2025 by the Metademands Development Team.

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

Session::checkLoginUser();

header('Content-Type: text/plain; charset=UTF-8');

if (isset($_POST['regex']) && isset($_POST['valeur'])) {
    if (strpos($_POST['valeur'], Dropdown::EMPTY_VALUE) !== false) {
        $_POST['valeur'] = str_replace(Dropdown::EMPTY_VALUE, '', $_POST['valeur']);
    }
    $saved_limit = ini_set('pcre.backtrack_limit', 100000);
    $result = @preg_match($_POST['regex'], $_POST['valeur']);
    ini_set('pcre.backtrack_limit', $saved_limit);
    if ($result === false) {
        echo 'invalid_regex';
    } elseif ($result) {
        echo 'true';
    } else {
        echo htmlspecialchars($_POST['regex'], ENT_QUOTES, 'UTF-8');
        echo htmlspecialchars($_POST['valeur'], ENT_QUOTES, 'UTF-8');
        echo 'false2';
    }
} else {
    echo 'false';
}
