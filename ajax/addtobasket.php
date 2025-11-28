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

use GlpiPlugin\Metademands\Basketline;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Fields\Basket;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;
use GlpiPlugin\Metademands\Group;


//Add Ajax fields loaded by ulocationUpdate.php etc..
if (isset($_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'])) {
    $session_fields = $_SESSION['plugin_metademands'][$_POST['metademands_id']]['fields'];
    foreach ($session_fields as $name => $session_field) {
        $_POST['field'][$name] = $session_field;
    }
}

$wizard = new Wizard();
$metademands = new Metademand();
$fields = new Field();

$KO = false;
$step = Metademand::STEP_SHOW;

$checks = [];
$content = [];
$data = $fields->find([
    'plugin_metademands_metademands_id' => $_POST['form_metademands_id'],
//        'is_basket' => 1
]);
//Clean $post & $data & $_POST
//$dataOld = $data;
//$post = $_POST['field'];
// Double appel for prevent order fields
//FieldOption::unsetHidden($data, $post);
//FieldOption::unsetHidden($dataOld, $post);
//$_POST['field'] = $post;

foreach ($data as $id => $value) {
    if ($value['type'] == 'radio') {
        if (!isset($_POST['field'][$id])) {
            $_POST['field'][$id] = null;
        }
    }
    if ($value['type'] == 'checkbox') {
        if (!isset($_POST['field'][$id])) {
            $_POST['field'][$id] = 0;
        }
    }
    if ($value['type'] == 'informations'
        || $value['type'] == 'title') {
        if (!isset($_POST['field'][$id])) {
            $_POST['field'][$id] = 0;
        }
    }
    if ($value['item'] == 'ITILCategory_Metademands') {
        $_POST['field'][$id] = $_POST['field_plugin_servicecatalog_itilcategories_id'] ?? 0;
        $_SESSION['plugin_metademands'][$_POST['form_metademands_id']]['fields'][$id] = $_POST['field'][$id];
    }

    $checks[] = Wizard::checkvalues($value, $id, $_POST, 'field');
}

foreach ($checks as $check) {
    if ($check['result'] == true) {
        $KO = true;
    }
    $content = array_merge($content, $check['content']);
}

if ($KO === false && count($content) > 0) {
    $basketline = new Basketline();
    $basketline->addToBasket($content, $_POST['form_metademands_id']);
} else {
    Session::addMessageAfterRedirect(__("There is a problem with the basket", "metademands"), false, ERROR);
}
echo $KO;
