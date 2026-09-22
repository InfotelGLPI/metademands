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
use GlpiPlugin\Metademands\Stepform;

// plugin_metademands_cancelform is the right the plugin defines for exactly this action,
// but the page used to open on plugin_metademands => UPDATE only: a profile holding the
// dedicated right saw the button drawn by Stepform::showWaitingForm() and was refused
// here, which pushed administrators to grant the whole plugin right instead. Both bits
// are accepted now, and each branch below resolves the row: the table carries no
// entities_id, so CommonDBTM::checkEntity() restricts nothing and the ownership has to
// be replayed by Stepform::canCancelForm().
Session::checkSeveralRightsOr([
    'plugin_metademands' => UPDATE,
    'plugin_metademands_cancelform' => READ,
]);

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

if (isset($_POST['delete_form_from_list'])) {

    $stepforms_id = (int) $_POST['plugin_metademands_stepforms_id'];
    $step = new Stepform();
    if (Session::haveRight(Stepform::$rightname, DELETE)) {
        $step->check($stepforms_id, DELETE);
    } elseif (!Stepform::canCancelForm($stepforms_id)) {
        throw new AccessDeniedHttpException();
    }
    $step->deleteAfterCreate($stepforms_id, true);
    Html::redirect(PLUGIN_METADEMANDS_WEBDIR . "/front/stepform.php");

} elseif (isset($_POST['delete_form_from_metademands'])) {

    $stepforms_id = (int) $_POST['plugin_metademands_stepforms_id'];
    $step = new Stepform();
    if (Session::haveRight(Stepform::$rightname, DELETE)) {
        $step->check($stepforms_id, DELETE);
    } elseif (!Stepform::canCancelForm($stepforms_id)) {
        throw new AccessDeniedHttpException();
    }
    $step->deleteAfterCreate($stepforms_id, false);

    Html::back();

}
