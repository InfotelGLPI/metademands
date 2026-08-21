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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Servicecatalog\Main;

if (Plugin::isPluginActive('servicecatalog')
    && Session::getCurrentInterface() != 'central') {

    Main::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    $draft_id = 0;

    if (isset($_GET['id'])) {
        $draft_id = (int) $_GET['id'];
    }

    if ($draft_id > 0) {

        // Drafts are personal: mirror the AJAX siblings and require ownership
        // before loading/rendering, so an authenticated requester cannot read
        // another user's draft by incrementing the id (IDOR).
        $draft = new Draft();
        if (!$draft->getFromDB($draft_id)
            || (int) $draft->fields['users_id'] !== Session::getLoginUserID()) {
            throw new AccessDeniedHttpException();
        }

        $datas = Draft::loadDatasDraft($draft_id);
        Draft::showDraft($datas);

    } else {
        echo __(
            'No draft available for this form',
            'metademands'
        );
    }

    if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
