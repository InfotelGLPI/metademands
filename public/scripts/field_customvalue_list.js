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
 * "Save all the values" button of the custom values of a field
 * (templates/fields/field_customvalue_list.html.twig).
 *
 * Each value sits in a form of its own, posting name[id], comment[id], is_default[id],
 * icon[id]... to front/fieldcustomvalue.form.php, which updates every id of the batch it
 * receives. The button gathers the fields of every value form into a single form and
 * submits it the usual way, so that the redirection and the messages stay those of GLPI.
 *
 * Loaded as a module (setup.php, ADD_JAVASCRIPT_MODULE); the tab is injected after load,
 * hence the delegated listener.
 */

document.addEventListener('click', (event) => {
    const button = event.target instanceof Element
        ? event.target.closest('[data-md-customvalues-save-all]')
        : null;
    if (button === null) {
        return;
    }
    event.preventDefault();

    const values = button.closest('td')?.querySelector('[data-md-customvalues]');
    if (values === null || values === undefined) {
        return;
    }

    const batch = document.createElement('form');
    batch.method = 'post';
    batch.action = button.dataset.mdCustomvaluesSaveAll;
    batch.hidden = true;

    const append = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        batch.append(input);
    };

    // The field-level inputs (fields_id, type...) are repeated in every value form: kept once
    const seen = new Set();
    values.querySelectorAll('form').forEach((form) => {
        if (form.querySelector('input[name^="id["]') === null) {
            return; // the delete form of a value
        }
        new FormData(form).forEach((value, name) => {
            if (typeof value !== 'string') {
                return;
            }
            if (!name.includes('[')) {
                if (seen.has(name)) {
                    return;
                }
                seen.add(name);
            }
            append(name, value);
        });
    });
    append('update', '');

    document.body.append(batch);
    batch.submit();
});
