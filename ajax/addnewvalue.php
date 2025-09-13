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


use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\Fields\Freetablefield;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_metademands", UPDATE);

if (!isset($_POST['field_id'])) {
    $_POST['field_id'] = 0;
}
switch ($_POST['action']) {
    case 'add':
        $field = new Field();
        $type = "";

        if ($field->getFromDB($_POST['field_id'])) {
            $type = $field->fields['type'];
            if ($type != "freetable") {
                FieldCustomvalue::addNewValue(
                    $_POST['count'],
                    $_POST['display_comment'],
                    $_POST['display_default'],
                    $_POST['field_id']
                );
            } else {
                Freetablefield::addNewValue(
                    $_POST['count'],
                    $_POST['field_id']
                );
            }
        }
        break;
}
