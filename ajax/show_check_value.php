<?php

/**
 * -------------------------------------------------------------------------
 * Metademands plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Metademands.
 *
 * Metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2013-2022 by Metademands plugin team.
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/Metademands
 * -------------------------------------------------------------------------
 */


use GlpiPlugin\Metademands\Condition;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$show_check_value = true;
if(isset($_POST['show_condition'])){
    if($_POST['show_condition'] == Condition::SHOW_CONDITION_EMPTY){
        echo __('Empty', 'metademands');
        $show_check_value = false;
    } else if($_POST['show_condition'] == Condition::SHOW_CONDITION_NOTEMPTY){
        echo __('Not empty', 'metademands');
        $show_check_value = false;
    }
}
if (isset($_POST['fields_id']) && $show_check_value) {
    $fields_id = $_POST['fields_id'];
    Condition::showCheckValue($fields_id);
}

