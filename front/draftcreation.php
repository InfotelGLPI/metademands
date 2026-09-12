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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Servicecatalog\Main;

// Page guard. This controller listed the active meta-demands of the caller's entities
// -- their names alone often describe internal processes -- to any authenticated
// helpdesk user, including a profile holding none of the plugin rights. The entity
// restriction inside listMetademandsForDraft() bounds what is disclosed but gates
// nothing on the profile. Same three rights as the other self-service entry points,
// and placed before the servicecatalog test so it applies whatever that plugin's state.
Session::checkSeveralRightsOr([
    'plugin_metademands' => READ,
    'plugin_metademands_createmeta' => READ,
    'plugin_metademands_fillform' => READ,
]);

if (Plugin::isPluginActive('servicecatalog') && Session::getCurrentInterface() != 'central') {
    $meta = new Metademand();
    $option['empty_value'] = true;
    $listMetademand = $meta->listMetademandsForDraft($option);

    if (isset($_REQUEST['metademands_id'])) {

        if (isset($_SESSION['plugin_metademands'][$_REQUEST['metademands_id']]['plugin_metademands_drafts_id'])) {
            $draft_id = $_SESSION['plugin_metademands'][$_REQUEST['metademands_id']]['plugin_metademands_drafts_id'];
            header('Location: ' . PLUGIN_METADEMANDS_WEBDIR . "/front/draft.form.php?id=$draft_id");
        } else {
            header('Location: ' . PLUGIN_METADEMANDS_WEBDIR . "/front/draft.php");
        }

    } else {

        Main::showDefaultHeaderHelpdesk(__('Your drafts', 'metademands'));

        $new_draft = __("New draft", 'metademands');
        $draft_name = __('Draft name', 'metademands');
        $metademand_name = ucfirst(_n('form', 'forms', 1, 'metademands'));
        $confirmation = __('Add');

        unset($_SESSION['plugin_metademands']);

        echo TemplateRenderer::getInstance()->render(
            '@metademands/draftcreation.html.twig',
            [
                'listMetademand' => $listMetademand,
                'path' => PLUGIN_METADEMANDS_WEBDIR,
                '_users_id_requester' => Session::getLoginUserID(),
                'new_draft' => $new_draft,
                'draft_name' => $draft_name,
                'metademand_name' => $metademand_name,
                'confirmation' => $confirmation,
                '_glpi_csrf_token' => Session::getNewCSRFToken(),
            ],
        );

        if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            Html::footer();
        } else {
            Html::helpFooter();
        }
    }
}
