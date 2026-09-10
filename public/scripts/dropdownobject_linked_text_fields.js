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
 * Text fields fed by a "dropdown object / User" field.
 *
 * The wizard renders its fields through Ajax, so the handler is delegated on the
 * document instead of being bound when the widget is inserted. The configuration
 * travels as a data attribute emitted by
 * templates/fields/dropdownobject_user.html.twig, next to the select it belongs to.
 */
$(function () {
    $(document).on('change', "[id-field] select", function () {
        const config = $(this)
            .closest('[id-field]')
            .find('[data-metademands-linked-text-fields]')
            .data('metademandsLinkedTextFields');

        if (!config || !config.targets) {
            return;
        }

        $.ajax({
            url: config.url,
            data: {id: $(this).val()},
            success: function (response) {
                const values = typeof response === 'string' ? JSON.parse(response) : response;

                config.targets.forEach(function (target) {
                    const input = $("[id-field='" + target.id_field + "'] input");

                    input.val(values[target.response_key] ?? '');
                    input.trigger('input');
                });
            },
        });
    });
});
