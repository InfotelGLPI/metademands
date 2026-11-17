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
 * "Save all the values" and "Save the new values" buttons of the custom values of a field
 * (templates/fields/field_customvalue_list.html.twig).
 *
 * Each value sits in a form of its own, posting to front/fieldcustomvalue.form.php, which
 * handles every value of the batch it receives. The buttons gather the fields of these forms
 * into a single form and submit it the usual way, so that the redirection and the messages
 * stay those of GLPI.
 *
 * Loaded as a module (setup.php, ADD_JAVASCRIPT_MODULE); the tab is injected after load,
 * hence the delegated listeners.
 */

/**
 * @param {Event} event
 * @param {string} selector
 * @returns {?HTMLElement}
 */
function clickedButton(event, selector) {
    return event.target instanceof Element ? event.target.closest(selector) : null;
}

/**
 * Post the fields of the given value forms at once. The field-level inputs (fields_id,
 * type...), repeated in every value form, are kept once.
 *
 * @param {string} action front/fieldcustomvalue.form.php
 * @param {HTMLFormElement[]} forms
 * @param {string} submit name of the submit button the controller expects (update, add)
 * @param {function(string, HTMLFormElement): string} rename name posted for an input
 */
function submitBatch(action, forms, submit, rename = (name) => name) {
    const batch = document.createElement('form');
    batch.method = 'post';
    batch.action = action;
    batch.hidden = true;

    const seen = new Set();
    forms.forEach((form) => {
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
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = rename(name, form);
            input.value = value;
            batch.append(input);
        });
    });
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = submit;
    input.value = '';
    batch.append(input);

    document.body.append(batch);
    batch.submit();
}

/*
 * Existing values: name[id], comment[id], is_default[id], icon[id]... updated by id
 */
document.addEventListener('click', (event) => {
    const button = clickedButton(event, '[data-md-customvalues-save-all]');
    if (button === null) {
        return;
    }
    event.preventDefault();

    const values = button.closest('td')?.querySelector('[data-md-customvalues]');
    if (values === null || values === undefined) {
        return;
    }

    // Not the delete form of a value, nor the form of a value added and not saved yet
    const forms = [...values.querySelectorAll('form')]
        .filter((form) => form.querySelector('input[name^="id["]') !== null);
    submitBatch(button.dataset.mdCustomvaluesSaveAll, forms, 'update');
});

/*
 * Values added with "Add" and not saved yet (ajax/addnewvalue.php,
 * templates/fields/field_customvalue_add.html.twig): custom_values[rank],
 * comment_values[rank], default_values[rank], icon[rank]... added by rank. Saving one of
 * them alone reloads the page and loses the others. The rows left without a value are
 * skipped, the others take consecutive ranks from the first new one.
 */
document.addEventListener('click', (event) => {
    const button = clickedButton(event, '[data-md-customvalues-add-all]');
    if (button === null) {
        return;
    }
    event.preventDefault();

    const container = button.closest('table')?.querySelector('#show_custom_fields');
    if (container === null || container === undefined) {
        return;
    }

    const name_input = (form) => form.querySelector('input[name^="custom_values["]');
    const rank_of = (form) => parseInt(form.querySelector('input[name="rank"]')?.value ?? '', 10);
    const forms = [...container.querySelectorAll('form')]
        .filter((form) => name_input(form) !== null && !Number.isNaN(rank_of(form)));
    const filled = forms.filter((form) => name_input(form).value.trim() !== '');
    if (filled.length === 0) {
        return;
    }

    const first_rank = Math.min(...forms.map(rank_of));
    const new_ranks = new Map(filled.map((form, index) => [form, first_rank + index]));
    submitBatch(button.dataset.mdCustomvaluesAddAll, filled, 'add', (name, form) => {
        return name.replace(`[${rank_of(form)}]`, `[${new_ranks.get(form)}]`);
    });
});
