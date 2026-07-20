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
use GlpiPlugin\Metademands\Form;

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

$KO = true;
$users_id                          = Session::getLoginUserID();
$form_id                           = (int) $_POST['plugin_metademands_forms_id'];

$form = new Form();
if (!$form->getFromDB($form_id) || (int) $form->fields['users_id'] !== $users_id) {
    throw new AccessDeniedHttpException();
}

if (isset($_POST['save_model'])) {
    $input = [
        'is_model' => 1,
        'is_private' => $_POST['is_private'],
        'id' => $_POST['plugin_metademands_forms_id']];

    $form->update($input);
    $KO = false;
}

if ($KO === false) {
    echo 0;
} else {
    echo $KO;
}
