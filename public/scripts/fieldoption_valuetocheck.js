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
 * re-attached one handler per rendered cell, which made the reload fire once per
 * past render.
 *
 * The option list (field_option_options.html.twig) carries the modal the option form
 * is loaded into: data-md-option-url / -params / -reload-params on the modal, and
 * data-md-option-open / -id / -title on the add button and on each option row.
 */
(function () {
    'use strict';

    /**
     * Show the option modal and load the option form into its body.
     * jQuery load() runs the scripts of the returned form.
     *
     * @param {HTMLElement} modal
     * @param {Object} params
     */
    function loadOptionForm(modal, params) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
        $(modal.querySelector('.modal-body')).load(modal.dataset.mdOptionUrl, params);
    }

    // Add button and option rows: open the option form, blank (id -1) or existing.
    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-md-option-open]');
        if (opener === null || event.target.closest('[data-md-option-noopen]') !== null) {
            return;
        }
        const modal = document.getElementById(opener.dataset.mdOptionOpen);
        if (modal === null) {
            return;
        }

        modal.querySelector('.modal-title').textContent = opener.dataset.mdOptionTitle;
        loadOptionForm(modal, Object.assign(
            JSON.parse(modal.dataset.mdOptionParams),
            {id: parseInt(opener.dataset.mdOptionId, 10)}
        ));
    });

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

    // Reload the option form, displayed in the modal of the option list, with the
    // values currently selected.
    function reload(cell, value) {
        const modal = cell.closest('[data-md-option-modal]').get(0);
        if (modal === undefined) {
            return;
        }
        const option = buildOption(cell, value);

        loadOptionForm(modal, Object.assign(JSON.parse(modal.dataset.mdOptionReloadParams), {
            id: option[0],
            check_value: option[1],
            plugin_metademands_tasks_id: option[2],
            fields_link: option[3],
            hidden_link: option[4],
            hidden_block: option[5],
            childs_blocks: option[6],
            users_id_validate: option[7],
            checkbox_id: option[8],
            check_type_value: option[9],
            assign_tech_group: option[10]
        }));
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
