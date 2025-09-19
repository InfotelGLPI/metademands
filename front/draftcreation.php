<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Servicecatalog\Main;

Session::checkLoginUser();

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
            ]
        );

        if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            Html::footer();
        } else {
            Html::helpFooter();
        }
    }
}
