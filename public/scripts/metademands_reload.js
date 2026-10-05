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
 * Reload one or several containers through Ajax, replacing the inline
 * <script> functions Ajax::updateItemJsCode() used to generate (Step visibility
 * editor, next group of a step, entity of a ticket task).
 *
 * The trigger carries a data-md-reload attribute: a JSON list of
 * {target, url, params}, target being the id of the container to fill.
 *  - data-md-reload-event="click": the element itself is clicked;
 *  - data-md-reload-event="change": a select inside the element changes, and
 *    every "__VALUE__" parameter takes the selected value.
 *
 * jQuery load() is kept on purpose: it posts the parameters exactly like the
 * legacy code did, and the core CSRF check validates it. Delegated
 * jQuery handlers are required for the change case, since select2 fires a
 * jQuery event that native listeners do not receive.
 */

(function () {
    'use strict';

    /**
     * @param {Element} trigger
     * @param {string|null} value selected value, substituted to "__VALUE__"
     */
    function reload(trigger, value) {
        let reloads;

        try {
            reloads = JSON.parse(trigger.dataset.mdReload || '[]');
        } catch (e) {
            return;
        }

        reloads.forEach(function (reload) {
            const params = {};

            Object.keys(reload.params || {}).forEach(function (key) {
                params[key] = (reload.params[key] === '__VALUE__' && value !== null)
                    ? value
                    : reload.params[key];
            });

            $(document.getElementById(reload.target)).load(reload.url, params);
        });
    }

    $(document).on('click', '[data-md-reload-event="click"]', function (event) {
        event.preventDefault();
        reload(this, null);
    });

    $(document).on('change', '[data-md-reload-event="change"] select', function () {
        reload(this.closest('[data-md-reload-event="change"]'), $(this).val());
    });
})();
