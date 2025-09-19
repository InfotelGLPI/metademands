<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

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

use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;

header("Content-Type: text/html; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();
$KO          = false;
$fields = new Field();
$fields->getFromDB($_POST['checkbox_id_val']);

$arrayValues = [];
$field_custom = new FieldCustomvalue();
if ($customs = $field_custom->find(["plugin_metademands_fields_id" => $fields->getID()], "rank")) {
    if (count($customs) > 0) {
        foreach ($customs as $custom) {
            $arrayValues[$custom['id']] = $custom['name'];
        }
    }
}

\Dropdown::showFromArray('checkbox_value',$arrayValues,['display_emptychoice'=> false]);
