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

use Glpi\Exception\Http\BadRequestHttpException;

// The response feeds the value of a form input, never HTML: state it explicitly so the
// browser cannot be talked into sniffing it as a document.
header('Content-Type: text/plain; charset=UTF-8');

Session::checkRight('config', UPDATE);

$authldap = new AuthLdap();
if (!$authldap->getFromDB((int) ($_POST['value'] ?? 0))) {
    // An unknown id used to build a filter out of empty columns.
    throw new BadRequestHttpException();
}

$filter         = "(" . $authldap->getField("login_field") . "=*)";
$ldap_condition = $authldap->getField('condition');

echo "(& $filter $ldap_condition)";
