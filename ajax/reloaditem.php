<?php

/*
 -------------------------------------------------------------------------
 Servicecatalog plugin for GLPI
 Copyright (C) 2018-2022 by the Servicecatalog Development Team.

 https://github.com/InfotelGLPI/servicecatalog
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Servicecatalog.

 Servicecatalog is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Servicecatalog is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Servicecatalog. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Field;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['action'])
    && $_POST['action'] == 'reloaditem') {
    if (isset($_POST["type"])) {
        if (in_array($_POST['type'], Field::$field_withobjects)) {
            echo __('Object', 'metademands') . "&nbsp;";
            Field::dropdownFieldItems($_POST['type'], ['with_empty_value' => true]);
        }
    }
}
