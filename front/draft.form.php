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

use GlpiPlugin\Metademands\Draft;

Session::checkLoginUser();

use GlpiPlugin\Servicecatalog\Main;

if (Plugin::isPluginActive('servicecatalog')
    && Session::getCurrentInterface() != 'central') {

    Main::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    $draft_id = 0;

    if (isset($_GET['id'])) {
        $draft_id = $_GET['id'];
    }

    if ($draft_id > 0) {

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
