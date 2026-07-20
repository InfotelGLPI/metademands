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

Session::checkLoginUser();

header('Content-Type: text/plain; charset=UTF-8');

if (isset($_POST['regex']) && isset($_POST['valeur'])) {
    if (strpos($_POST['valeur'], Dropdown::EMPTY_VALUE) !== false) {
        $_POST['valeur'] = str_replace(Dropdown::EMPTY_VALUE, '', $_POST['valeur']);
    }
    // Both pattern and subject are client-supplied: cap their length and tighten
    // the PCRE backtracking/recursion limits so a pathological (ReDoS) pattern
    // aborts quickly (reported as invalid_regex) instead of burning CPU.
    if (strlen($_POST['regex']) > 500 || strlen($_POST['valeur']) > 2000) {
        echo 'invalid_regex';
        return;
    }
    $saved_bt = ini_set('pcre.backtrack_limit', 10000);
    $saved_rc = ini_set('pcre.recursion_limit', 1000);
    $result = @preg_match($_POST['regex'], $_POST['valeur']);
    ini_set('pcre.backtrack_limit', $saved_bt);
    ini_set('pcre.recursion_limit', $saved_rc);
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
