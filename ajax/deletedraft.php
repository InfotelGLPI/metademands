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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Metademands\Draft;
use GlpiPlugin\Metademands\Draft_Value;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$users_id                          = Session::getLoginUserID();
$plugin_metademands_metademands_id = (int) $_POST['plugin_metademands_metademands_id'];
$draft_id                          = (int) $_POST['drafts_id'];

$self = new Draft();
if (!$self->getFromDB($draft_id) || (int) $self->fields['users_id'] !== $users_id) {
    throw new AccessDeniedHttpException();
}
$self->deleteByCriteria(['id' => $draft_id]);

$values = new Draft_Value();
$values->deleteByCriteria(['plugin_metademands_drafts_id' => $draft_id]);

$drafts = $self->find(['users_id'                          => $users_id,
                       'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id]);
if ($_POST['self_delete'] == true) {
    unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_id']);
    unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_drafts_name']);
    unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['fields']);
}
$entries = [];
foreach ($drafts as $draft) {
    $entries[] = [
        'id'   => (int) $draft['id'],
        'name' => $draft['name'],
        'date' => Html::convDateTime($draft['date']),
    ];
}

TemplateRenderer::getInstance()->display('@metademands/forms/drafts_rows.html.twig', [
    'entries' => $entries,
]);
