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

/**
 * Option row of a field link: reload the option form whenever the "value to check"
 * cell changes.
 *
 * The parameters that used to be interpolated into a per-cell inline <script> now
 * travel as data-* attributes on the wrapping <td> (see the shared
 * field_value_to_check_cell.html.twig template). Field types differ on the last two
 * entries of the payload, hence the two independent flags:
 *  - data-md-check-type: the type also exposes a check_type_value dropdown
 *  - data-md-tech-group: the type also exposes an assign_tech_group selector
 *
 * Handlers are delegated on the document, so a single registration covers every cell,
 * including the ones injected later by the AJAX option modal - the inline version
 * re-attached one handler per rendered cell, which made reloadviewOption() fire once
 * per past render.
 */
/* global reloadviewOption */
(function () {
    'use strict';

    function buildOption(cell, value) {
        var with_check_type = cell.attr('data-md-check-type') === '1';
        var with_tech_group = cell.attr('data-md-tech-group') === '1';

        return [
            parseInt(cell.attr('data-md-option-id'), 10),
            value,
            $('select[name="plugin_metademands_tasks_id"]').val(),
            $('select[name="fields_link"]').val(),
            $('select[name="hidden_link"]').val(),
            $('select[name="hidden_block"]').val(),
            JSON.stringify($('select[name="childs_blocks[][]"]').val()),
            $('select[name="users_id_validate"]').val(),
            $('select[name="checkbox_id"]').val(),
            with_check_type ? $('select[name="check_type_value"]').val() : 0,
            with_tech_group
                ? JSON.stringify($('select[name="assign_tech_group[]"], select[name="assign_tech_group"]').val())
                : 0
        ];
    }

    function reload(cell, value) {
        if (typeof reloadviewOption === 'function') {
            reloadviewOption(buildOption(cell, value));
        }
    }

    // Plain value: the cell holds a dropdown, its own value is the one to check.
    $(document).on('change', 'td.dropdown-valuetocheck select', function () {
        var cell = $(this).closest('td.dropdown-valuetocheck');
        reload(cell, $(this).val());
    });

    // Regex value: the cell holds a text input validated by its own button.
    $(document).on('click', 'td.dropdown-valuetocheck button.btn-success', function () {
        var cell = $(this).closest('td.dropdown-valuetocheck');
        reload(cell, cell.find('input[name=check_value]').val());
    });

    // Switching between plain value and regex: the value is read from whichever
    // widget the sibling cell currently shows.
    $(document).on('change', 'td select[name=check_type_value]', function () {
        var sibling = $('td.dropdown-valuetocheck').first();
        var value   = 0;

        if (sibling.find('select[name=check_value]').length > 0) {
            value = sibling.find('select[name=check_value]').val();
        } else if (sibling.find('input[name=check_value]').length > 0) {
            value = sibling.find('input[name=check_value]').val();
        }

        reload($(this).closest('td'), value);
    });
})();
