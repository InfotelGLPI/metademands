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

use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Stepform;
use GlpiPlugin\Servicecatalog\Main;

// Settled before the header is emitted, so a refusal is rendered instead of a half
// written page: the exception used to be raised once the menu and the page frame of
// the plugin had already been sent, on a 200 response. The condition is the one of
// front/nextGroup.form.php, the twin entry point of the same workflow, so a profile
// allowed to advance a step is not refused the list of those very steps.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_fillform' => READ,
]);

if (Session::getCurrentInterface() == 'central') {
    Html::header(Metademand::getTypeName(2), '', "helpdesk", Menu::class);
} else {
    if (Plugin::isPluginActive('servicecatalog')) {
        Main::showDefaultHeaderHelpdesk(__('Continue metademand', 'metademands'));
    } else {
        Html::helpHeader(__('Continue metademand', 'metademands'));
    }
}

$stepform = new Stepform();
$stepform->showPendingForm();

if (Session::getCurrentInterface() != 'central'
    && Plugin::isPluginActive('servicecatalog')) {

    Main::showNavBarFooter('metademands');
}

if (Session::getCurrentInterface() == 'central') {
    Html::footer();
} else {
    Html::helpFooter();
}
