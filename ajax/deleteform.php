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
use GlpiPlugin\Metademands\Form;
use GlpiPlugin\Metademands\Form_Value;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$users_id                          = Session::getLoginUserID();
$plugin_metademands_metademands_id = (int) $_POST['plugin_metademands_metademands_id'];
$form_id                           = (int) $_POST['forms_id'];

$self = new Form();
if (!$self->getFromDB($form_id) || (int) $self->fields['users_id'] !== $users_id) {
    throw new AccessDeniedHttpException();
}
$self->deleteByCriteria(['id' => $form_id]);

$values = new Form_Value();
$values->deleteByCriteria(['plugin_metademands_forms_id' => $form_id]);

$forms = $self->find(['users_id'                          => $users_id,
    'plugin_metademands_metademands_id' => $plugin_metademands_metademands_id,
    'is_model' => 1]);
if ($_POST['self_delete'] == true) {
    unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_id']);
    unset($_SESSION['plugin_metademands'][$plugin_metademands_metademands_id]['plugin_metademands_forms_name']);
}
$entries = [];
foreach ($forms as $form) {
    $entries[] = [
        'id'   => (int) $form['id'],
        'name' => $form['name'],
        'date' => Html::convDateTime($form['date']),
    ];
}

TemplateRenderer::getInstance()->display('@metademands/forms/private_models_rows.html.twig', [
    'entries' => $entries,
]);
