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

header("Content-Type: application/json; charset=UTF-8");

Html::header_nocache();

Session::checkLoginUser();

use Glpi\RichText\RichText;
use GlpiPlugin\Metademands\Condition;

$predicate = '';

if (isset($_POST['metademands_id'])
    && isset($_POST['field'])) {
    $fields = $_POST['field'];

    $tab = Condition::conditionsTab($_POST['metademands_id']);

    foreach ($tab as $key => $value) {
        if (array_key_exists($value['fields_id'], $fields)) {
            if (!is_array($fields[$value['fields_id']])) {
                $tab[$key]['value'] = RichText::getTextFromHtml($fields[$value['fields_id']]);
            } else {
                $tab[$key]['value'] = $fields[$value['fields_id']];
            }
        } else {
            $tab[$key]['value'] = '';
        }
    }

    $checked_tab = [];
    $result = '';

    $actual_group = 0;

    if (count($tab) > 0) {
        foreach ($tab as $key => $condition) {
            $result = (int) Condition::verifyCondition($condition);
            if (!empty($predicate) && $actual_group == $condition['order']) {
                $predicate .= ' ' . Condition::showPhpLogic($condition['show_logic']);
            } elseif (empty($predicate)) {
                $predicate = '(';
            } elseif ($actual_group != $condition['order']) {
                $predicate .= ") " . Condition::showPhpLogic($condition['show_logic']) . "( ";
            }
            $actual_group = $condition['order'];
            $predicate .= " $result ";
        }
        $predicate .= ")";
    }
}
// Safe boolean evaluation (replaces eval()): the predicate only ever contains
// booleans (0/1), && , || and parentheses. Parse with a shunting-yard to RPN and
// evaluate natively, honouring PHP precedence (&& tighter than ||) — no code exec.
$evaluate = static function (string $expr): bool {
    preg_match_all('/&&|\|\||[01()]/', $expr, $matches);
    $prec = ['||' => 1, '&&' => 2];
    $output = [];
    $ops = [];
    foreach ($matches[0] as $token) {
        if ($token === '0' || $token === '1') {
            $output[] = ($token === '1');
        } elseif ($token === '&&' || $token === '||') {
            while (!empty($ops) && end($ops) !== '(' && $prec[end($ops)] >= $prec[$token]) {
                $output[] = array_pop($ops);
            }
            $ops[] = $token;
        } elseif ($token === '(') {
            $ops[] = $token;
        } elseif ($token === ')') {
            while (!empty($ops) && end($ops) !== '(') {
                $output[] = array_pop($ops);
            }
            array_pop($ops); // discard the matching '('
        }
    }
    while (!empty($ops)) {
        $output[] = array_pop($ops);
    }

    $stack = [];
    foreach ($output as $token) {
        if (is_bool($token)) {
            $stack[] = $token;
            continue;
        }
        $right = array_pop($stack);
        $left = array_pop($stack);
        $stack[] = ($token === '&&') ? ($left && $right) : ($left || $right);
    }

    return (bool) array_pop($stack);
};

$result_bool = false;
if (!empty($predicate)) {
    $result_bool = $evaluate($predicate);
}
echo json_encode($result_bool);
