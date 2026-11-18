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
 * Options of a field: the modal of the option list and the option form loaded into it.
 *
 * The option list (field_option_options.html.twig) carries the modal: data-md-option-url /
 * -params / -reload-params on the modal, data-md-option-open / -id / -title on the add
 * button and on each rule, plus data-md-option-value on the "Add" shortcut of a value
 * without option, which preselects that value.
 *
 * The option form (field_option_form.html.twig) is reloaded whenever its value to check
 * changes, with the values currently chosen: the widget of the value carries
 * data-md-option-id / -check-type / -tech-group (field_value_to_check_cell.html.twig).
 *
 * Its actions are cards (field_option_link.html.twig): a card switched on enables the
 * fieldset of its settings; switched off, the settings are disabled, so not posted, and
 * the fieldset of its resets, which empty the action, is enabled instead.
 *
 * Every listener is delegated on the document, so it also covers the forms loaded later.
 */

/**
 * Show the option modal and load the option form into its body.
 * jQuery load() runs the scripts of the returned form (core dropdowns).
 *
 * @param {HTMLElement} modal
 * @param {Object} params
 */
function loadOptionForm(modal, params) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
    $(modal.querySelector('.modal-body')).load(modal.dataset.mdOptionUrl, params, () => {
        const form = modal.querySelector('[data-md-option-form]');
        if (form !== null) {
            updateSummary(form);
        }
    });
}

/**
 * Value of a named control of the option form, or '' when it is disabled: the settings of
 * an action switched off are not kept.
 *
 * @param {ParentNode} root
 * @param {string} selector
 * @returns {string|string[]}
 */
function enabledValue(root, selector) {
    const control = root.querySelector(selector);
    if (control === null || control.matches(':disabled')) {
        return '';
    }
    if (control.multiple) {
        return Array.from(control.selectedOptions, (option) => option.value);
    }
    return control.value;
}

/**
 * Switch an action of the option form on or off.
 *
 * @param {HTMLElement} card
 * @param {boolean} active
 */
function toggleAction(card, active) {
    const form = card.closest('[data-md-option-form]') ?? document;
    const key  = CSS.escape(card.dataset.mdAction);

    card.classList.toggle('active', active);
    card.setAttribute('aria-pressed', active ? 'true' : 'false');
    form.querySelectorAll(`[data-md-action-panel="${key}"]`).forEach((panel) => {
        panel.disabled = !active;
        panel.hidden   = !active;
    });
    form.querySelectorAll(`[data-md-action-reset="${key}"]`).forEach((reset) => {
        reset.disabled = active;
    });
}

/**
 * Text of the value to check currently chosen in the option form.
 *
 * @param {HTMLElement} form
 * @returns {string}
 */
function chosenValue(form) {
    const widget = form.querySelector('.dropdown-valuetocheck');
    if (widget === null) {
        return '';
    }
    const select = widget.querySelector('select');
    if (select !== null) {
        return Array.from(select.selectedOptions, (option) => option.textContent.trim()).join(', ');
    }
    return widget.querySelector('input[name="check_value"]')?.value ?? '';
}

/**
 * Write the rule as it will apply, below the form: "« field » is not value → actions".
 *
 * @param {HTMLElement} form
 */
function updateSummary(form) {
    // "is not" a special value (not null value…) means nothing: FieldOption ignores it
    const not_radio    = form.querySelector('input[name="is_negated"][value="1"]');
    const value_select = form.querySelector('.dropdown-valuetocheck select[name="check_value"]');
    if (not_radio !== null && value_select !== null) {
        const special = parseInt(value_select.value, 10) <= 0;
        not_radio.disabled = special;
        if (special && not_radio.checked) {
            form.querySelector('input[name="is_negated"][value="0"]').checked = true;
        }
    }

    const summary = form.querySelector('[data-md-option-summary]');
    const operator = form.querySelector('input[name="is_negated"]:checked');
    form.classList.toggle('md-option-negated', operator !== null && operator.value === '1');
    if (summary === null) {
        return;
    }

    const actions = Array.from(
        form.querySelectorAll('.md-action-card.active .md-action-name'),
        (name) => name.textContent.trim()
    );

    const field = document.createElement('strong');
    field.textContent = `« ${form.dataset.mdFieldName ?? ''} »`;
    const rule = operator !== null
        ? form.querySelector(`label[for="${CSS.escape(operator.id)}"]`)?.textContent.trim() ?? ''
        : `· ${form.querySelector('.md-option-condition-label')?.textContent.trim() ?? ''} :`;
    const value = document.createElement('strong');
    value.textContent = chosenValue(form);
    const arrow = document.createElement('i');
    arrow.className = 'ti ti-arrow-right';
    arrow.setAttribute('aria-hidden', 'true');
    const then = document.createElement('span');
    if (actions.length > 0) {
        then.textContent = actions.join(', ');
    } else {
        then.className = 'text-secondary fst-italic';
        then.textContent = summary.dataset.mdNoAction ?? '';
    }
    summary.replaceChildren(field, ` ${rule} `, value, ' ', arrow, ' ', then);
}

// Add button, rules and "Add" shortcuts of the list: open the option form, blank (id -1)
// or existing; a shortcut preselects its value.
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-md-option-open]');
    if (opener === null || event.target.closest('[data-md-option-noopen]') !== null) {
        return;
    }
    const modal = document.getElementById(opener.dataset.mdOptionOpen);
    if (modal === null) {
        return;
    }

    const params = Object.assign(
        JSON.parse(modal.dataset.mdOptionParams),
        {id: parseInt(opener.dataset.mdOptionId, 10)}
    );
    if (opener.dataset.mdOptionValue !== undefined) {
        params.check_value = opener.dataset.mdOptionValue;
    }
    modal.querySelector('.modal-title').textContent = opener.dataset.mdOptionTitle;
    loadOptionForm(modal, params);
});

// Action cards
document.addEventListener('click', (event) => {
    const card = event.target.closest('[data-md-action]');
    if (card === null) {
        return;
    }
    toggleAction(card, !card.classList.contains('active'));
    const form = card.closest('[data-md-option-form]');
    if (form !== null) {
        updateSummary(form);
    }
});

// "is / is not"
document.addEventListener('change', (event) => {
    const form = event.target.closest('[data-md-option-form]');
    if (form !== null && event.target.matches('input[name="is_negated"]')) {
        updateSummary(form);
    }
});

/**
 * Reload the option form, displayed in the modal of the option list, with the values
 * currently chosen.
 *
 * @param {HTMLElement} widget element carrying data-md-option-id / -check-type / -tech-group
 * @param {string|string[]} value value to check
 */
function reload(widget, value) {
    const modal = widget.closest('[data-md-option-modal]');
    const form  = widget.closest('[data-md-option-form]');
    if (modal === null || form === null) {
        return;
    }
    const with_check_type = widget.dataset.mdCheckType === '1';
    const with_tech_group = widget.dataset.mdTechGroup === '1';
    const list = (selector) => {
        const values = enabledValue(form, selector);
        return JSON.stringify(Array.isArray(values) ? values : []);
    };

    loadOptionForm(modal, Object.assign(JSON.parse(modal.dataset.mdOptionReloadParams), {
        id: parseInt(widget.dataset.mdOptionId, 10),
        check_value: value,
        is_negated: form.querySelector('input[name="is_negated"]:checked')?.value ?? 0,
        plugin_metademands_tasks_id: enabledValue(form, 'select[name="plugin_metademands_tasks_id"]'),
        fields_link: enabledValue(form, 'select[name="fields_link"]'),
        hidden_link: enabledValue(form, 'select[name="hidden_link"]'),
        hidden_block: enabledValue(form, 'select[name="hidden_block"]'),
        hidden_block_same_block: enabledValue(form, 'select[name="hidden_block_same_block"]'),
        childs_blocks: list('select[name="childs_blocks[][]"]'),
        users_id_validate: enabledValue(form, 'select[name="users_id_validate"]'),
        itilvalidationtemplates_id: enabledValue(form, 'select[name="itilvalidationtemplates_id"]'),
        checkbox_id: enabledValue(form, 'select[name="checkbox_id"]'),
        check_type_value: with_check_type
            ? form.querySelector('select[name="check_type_value"]')?.value ?? 0
            : 0,
        assign_tech_group: with_tech_group
            ? list('select[name="assign_tech_group[]"], select[name="assign_tech_group"]')
            : 0,
        // Validator fields: a multiple select, kept as a JSON list
        validation_fields_ids: list('select[name="validation_fields_ids[]"]'),
        // Actions switched on, kept on even without a value yet
        active_actions: JSON.stringify(Array.from(
            form.querySelectorAll('.md-action-card.active'),
            (card) => card.dataset.mdAction
        ))
    }));
}

// Plain value: the widget holds a dropdown, its own value is the one to check.
// select2 only triggers jQuery events.
$(document).on('change', '.dropdown-valuetocheck select', function () {
    reload(this.closest('.dropdown-valuetocheck'), $(this).val());
});

// Regex value: the widget holds a text input validated by its own button.
document.addEventListener('click', (event) => {
    const button = event.target.closest('.dropdown-valuetocheck button.btn-success');
    if (button === null) {
        return;
    }
    const widget = button.closest('.dropdown-valuetocheck');
    reload(widget, widget.querySelector('input[name="check_value"]')?.value ?? '');
});

// Switching between plain value and regex: the value is read from whichever widget the
// value to check currently shows.
$(document).on('change', '.md-option-check-type select[name="check_type_value"]', function () {
    const form  = this.closest('[data-md-option-form]') ?? document;
    const value = form.querySelector('.dropdown-valuetocheck [name="check_value"]')?.value ?? 0;
    reload(this.closest('.md-option-check-type'), value);
});
