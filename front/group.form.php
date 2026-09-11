<?php

/**
 * -------------------------------------------------------------------------
 * metademands plugin for GLPI
 * Copyright (C) 2018-2026 by the metademands Development Team.
 *
 * https://github.com/InfotelGLPI/metademands
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of metademands.
 *
 * metademands is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * metademands is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with metademands. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Metademands\Group;
use GlpiPlugin\Metademands\GroupConfig;
use GlpiPlugin\Metademands\Metademand;

// Longest client-supplied pattern accepted by the group matching below.
$regex_value_max_length = 500;

$group = new Group();

if (isset($_POST["add_groups"])) {
    if (isset($_POST['groups_id'])) {
        $group->check(-1, UPDATE, $_POST);

        // check() authorises the parent meta-demand, not the posted values. Replay the very
        // criteria the regex_value branch below uses to build its own candidate list, so a
        // forged groups_id[] cannot attach a group living outside the caller's entity scope.
        $dbu = new DbUtils();
        $posted_groups_id = array_unique(array_map('intval', (array) $_POST['groups_id']));

        $allowed_groups = [];
        if (count($posted_groups_id) > 0) {
            $core_group = new \Group();
            $allowed_groups = $core_group->find(
                array_merge(
                    [\Group::getTable() . '.id' => $posted_groups_id],
                    $dbu->getEntitiesRestrictCriteria(\Group::getTable(), '', '', true),
                ),
            );
        }

        if (count($allowed_groups) < count($posted_groups_id)) {
            Session::addMessageAfterRedirect(
                $group->getErrorMessage(ERROR_RIGHT),
                false,
                ERROR,
            );
        }

        //add groups
        foreach ($allowed_groups as $allowed_group) {
            $group->add(['groups_id' => $allowed_group['id'],
                'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id']]);
        }
    }
    if (isset($_POST['regex_value']) && !empty($_POST['regex_value'])) {
        // This branch writes just like the one above, so it needs the same guard: check()
        // resolves the parent meta-demand and applies the UPDATE right and its entity boundary.
        $group->check(-1, UPDATE, $_POST);

        $regex = (string) $_POST['regex_value'];
        if (strlen($regex) > $regex_value_max_length) {
            Session::addMessageAfterRedirect(
                __('The regular expression is invalid', 'metademands'),
                false,
                ERROR,
            );
            Html::back();
        }

        // Tighten the PCRE limits around the loop so a catastrophically backtracking pattern
        // aborts instead of burning the worker's CPU over every group, and treat a false
        // return as an invalid pattern rather than as a silent non-match.
        $saved_backtrack_limit = ini_set('pcre.backtrack_limit', 10000);
        $saved_recursion_limit = ini_set('pcre.recursion_limit', 1000);

        // The core Group is the one that owns a `name`: the plugin Group imported above is the
        // meta-demand/group association table, whose rows carry no name at all, so matching
        // against it compared the pattern to an undefined key.
        $dbu = new DbUtils();
        $core_group = new \Group();
        $groups = $core_group->find(
            $dbu->getEntitiesRestrictCriteria(\Group::getTable(), '', '', true),
        );

        $invalid_regex = false;
        foreach ($groups as $g) {
            $res = @preg_match($regex, (string) $g['name']);
            if ($res === false) {
                $invalid_regex = true;
                break;
            }
            if ($res === 1) {
                $group->add([
                    'plugin_metademands_metademands_id' => $_POST['plugin_metademands_metademands_id'],
                    'groups_id'                         => $g['id'],
                ]);
            }
        }

        ini_set('pcre.backtrack_limit', $saved_backtrack_limit);
        ini_set('pcre.recursion_limit', $saved_recursion_limit);

        if ($invalid_regex) {
            Session::addMessageAfterRedirect(
                __('The regular expression is invalid', 'metademands'),
                false,
                ERROR,
            );
        }
    }
    Html::back();
} elseif (isset($_POST["define_visibility"])) {
    // Editing a meta-demand's group visibility is a configuration change on that meta-demand.
    // Session::checkRight() only tested a global right bit with no notion of entity, so a
    // manager holding UPDATE on entity A could rewrite the visibility of a form owned by
    // entity B. check() loads the posted meta-demand and enforces both the right and the
    // entity boundary, exactly like the add_groups branch above does through its child.
    $metademands_id = (int) ($_POST['plugin_metademands_metademands_id'] ?? 0);
    $metademand = new Metademand();
    $metademand->check($metademands_id, UPDATE);

    $groupconfig = new GroupConfig();
    if (!$groupconfig->getFromDBByCrit(['plugin_metademands_metademands_id' => $metademands_id])) {
        $groupconfig->add(['visibility' => $_POST['visibility'],
            'plugin_metademands_metademands_id' => $metademands_id]);
    } else {
        $id = $groupconfig->getID();
        $groupconfig->update(['id' => $id,
            'visibility' => $_POST['visibility'],
            'plugin_metademands_metademands_id' => $metademands_id]);
    }
    Html::back();
}
