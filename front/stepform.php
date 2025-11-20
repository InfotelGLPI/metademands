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

include('../../../inc/includes.php');

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Menu;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Stepform;
use GlpiPlugin\Servicecatalog\Main;

if (Session::getCurrentInterface() == 'central') {
    Html::header(Metademand::getTypeName(2), '', "helpdesk", Menu::class);
} else {
    if (Plugin::isPluginActive('servicecatalog')) {
        Main::showDefaultHeaderHelpdesk(__('Continue metademand', 'metademands'));
    } else {
        Html::helpHeader(__('Continue metademand', 'metademands'));
    }
}

$meta = new Metademand();
$stepform = new Stepform();

if ($meta->canView() || Session::haveRight("plugin_metademands_fillform", READ)) {
    $stepform->showPendingForm();
} else {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() != 'central'
    && Plugin::isPluginActive('servicecatalog')) {

    Main::showNavBarFooter('metademands');
}

if (Session::getCurrentInterface() == 'central') {
    Html::footer();
} else {
    Html::helpFooter();
}
