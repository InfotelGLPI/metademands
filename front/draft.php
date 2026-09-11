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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Servicecatalog\Main;
use GlpiPlugin\Metademands\Draft;

// The list itself is scoped by plugin_metademands_addDefaultWhere(), but nothing
// checked that the requester may read drafts at all before the search ran.
Session::checkRight(Draft::$rightname, READ);

if (Plugin::isPluginActive('servicecatalog') && Session::getCurrentInterface() != 'central') {

    Main::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

    TemplateRenderer::getInstance()->display('@metademands/forms/draft_new_button.html.twig', [
        'url'   => PLUGIN_METADEMANDS_WEBDIR . '/front/draftcreation.php',
        'label' => __('New draft', 'metademands'),
    ]);

    Search::show(Draft::class);

    if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
