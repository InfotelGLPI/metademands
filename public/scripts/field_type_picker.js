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
 * Type picker of the new field form (templates/field_form.html.twig): the type is chosen
 * among cards grouped by family, then the settings of the field are shown. A card sets the
 * "type" dropdown of the form, kept hidden, whose change still reloads the object selector
 * (Ajax::updateItemOnSelectEvent(), jQuery handlers that native events reach). The custom
 * values of a checkbox, a radio button or a specific dropdown are typed in the same form
 * (FieldCustomvalue::addInitialValues()).
 *
 * Loaded as a module (setup.php, ADD_JAVASCRIPT_MODULE); the form is injected into a modal
 * after load, hence the delegated listeners.
 */

/** Types never mandatory: the "Mandatory" switch is hidden for them */
const TITLE_TYPES = ['title', 'title-block', 'informations'];
/** Types whose additional label (the label of the end) is mandatory */
const INTERVAL_TYPES = ['date_interval', 'datetime_interval'];
/** Types whose custom values are typed in the form, see FieldCustomvalue::hasInitialValues() */
const VALUE_TYPES = ['checkbox', 'radio'];
/** The same once "custom values" (other) is chosen as their object */
const VALUE_ITEM_TYPES = ['dropdown_meta', 'dropdown_multiple'];
/** Types with one default value */
const SINGLE_DEFAULT_TYPES = ['radio', 'dropdown_meta'];

/**
 * Show the custom values of the types that use them; hidden ones are disabled, so not posted.
 *
 * @param {Element} form
 * @param {?string} item the object, null to read it from the form
 */
function refreshValues(form, item = null) {
    const fieldset = form.querySelector('[data-md-new-values]');
    if (!(fieldset instanceof HTMLFieldSetElement)) {
        return;
    }
    const type = form.querySelector('select[name="type"]')?.value ?? '';
    const object = item ?? form.querySelector('[name="item"]')?.value ?? '';
    const shown = VALUE_TYPES.includes(type)
        || (VALUE_ITEM_TYPES.includes(type) && object === 'other');
    fieldset.hidden = !shown;
    fieldset.disabled = !shown;
}

/**
 * Add a custom value row after the given one, or at the end
 *
 * @param {Element} fieldset
 * @param {?Element} after
 * @param {string} name
 * @returns {?HTMLInputElement} the input of the value
 */
function addValueRow(fieldset, after = null, name = '') {
    const template = fieldset.querySelector('template[data-md-new-value-template]');
    const list = fieldset.querySelector('[data-md-new-values-list]');
    if (!(template instanceof HTMLTemplateElement) || list === null) {
        return null;
    }
    const index = parseInt(fieldset.dataset.mdNewValuesNext ?? '0', 10) || 0;
    fieldset.dataset.mdNewValuesNext = String(index + 1);

    const holder = document.createElement('div');
    holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).trim();
    const row = holder.firstElementChild;
    if (row === null) {
        return null;
    }
    if (after !== null) {
        after.after(row);
    } else {
        list.append(row);
    }
    const input = row.querySelector('[data-md-new-value-name]');
    if (input instanceof HTMLInputElement) {
        input.value = name;
        return input;
    }
    return null;
}

/**
 * What the additional label and the comments become for the type (Field::getTextHints())
 *
 * @param {Element} form
 */
function refreshTextHints(form) {
    let hints = {};
    try {
        hints = JSON.parse(form.dataset.mdTextHints || '{}');
    } catch {
        return;
    }
    const type = form.querySelector('select[name="type"], input[name="type"]')?.value ?? '';
    const hint = hints[type] ?? hints[''] ?? {};
    form.querySelectorAll('[data-md-text-hint]').forEach((element) => {
        element.textContent = hint[element.dataset.mdTextHint] ?? '';
    });
}

/** All the blocks of a "Block" dropdown, before any of them is left out */
const ALL_BLOCKS = new WeakMap();

/**
 * Leave the given blocks out of a "Block" dropdown (its select2 reads the options each
 * time it opens), the others back in their order; the first block left is chosen when the
 * chosen one goes. Nothing is left out when no block would remain.
 *
 * @param {HTMLSelectElement} select
 * @param {string[]} left_out
 */
function filterTitledBlocks(select, left_out) {
    if (!ALL_BLOCKS.has(select)) {
        ALL_BLOCKS.set(select, Array.from(select.options));
    }
    let wanted = ALL_BLOCKS.get(select).filter((option) => !left_out.includes(option.value));
    if (wanted.length === 0) {
        wanted = ALL_BLOCKS.get(select);
    }
    const value = select.value;
    if (wanted.length !== select.options.length || wanted.some((option, index) => select.options[index] !== option)) {
        select.replaceChildren(...wanted);
    }
    if (wanted.some((option) => option.value === value)) {
        select.value = value;
    } else {
        select.value = wanted[0].value;
        // The "Display field after" list and the select2 follow the block
        select.dispatchEvent(new Event('change', {bubbles: true}));
    }
}

/**
 * One title per block: the "Block title" card is not offered when the block was chosen by
 * its button and already has a title; otherwise the "Block" dropdown of a block title only
 * offers the blocks without one. Should a titled block still be chosen, it is warned about
 * and the field cannot be added (Field::prepareInputForAdd() refuses it too).
 *
 * @param {Element} form
 */
function refreshBlockTitle(form) {
    if (!form.hasAttribute('data-md-titled-blocks')) {
        return;
    }
    let titled = [];
    try {
        titled = JSON.parse(form.dataset.mdTitledBlocks || '[]');
    } catch {
        return;
    }
    const rank_select = form.querySelector('select[name="rank"]');
    const type = form.querySelector('select[name="type"]')?.value ?? '';
    if (rank_select instanceof HTMLSelectElement && rank_select.dataset.mdRankLocked !== '1') {
        filterTitledBlocks(rank_select, type === 'title-block' ? titled.map((block) => String(block.rank)) : []);
    }
    const rank = rank_select?.value ?? '';
    const existing = titled.find((block) => String(block.rank) === rank);

    const card = form.querySelector('[data-md-type-card="title-block"]');
    if (card instanceof HTMLButtonElement) {
        const taken = rank_select?.dataset.mdRankLocked === '1' && existing !== undefined;
        card.disabled = taken;
        card.querySelector('[data-md-type-taken]')?.toggleAttribute('hidden', !taken);
    }

    const clash = type === 'title-block' && existing !== undefined;
    const warning = form.querySelector('[data-md-titled-warning]');
    if (warning !== null) {
        warning.hidden = !clash;
        const text = warning.querySelector('[data-md-titled-warning-text]');
        if (text !== null && existing !== undefined) {
            text.textContent = (form.dataset.mdTitledMessage ?? '')
                .replace('%1$s', String(existing.rank))
                .replace('%2$s', String(existing.title));
        }
    }
    form.querySelectorAll('button[type="submit"][name="add"], button[type="submit"][name="add_another"]')
        .forEach((button) => {
            button.disabled = clash;
        });
}

/**
 * @param {Element} form
 * @param {1|2} step 1: the type, 2: the settings
 */
function showStep(form, step) {
    form.querySelector('[data-md-type-picker]')?.toggleAttribute('hidden', step !== 1);
    form.querySelectorAll('[data-md-field-settings]').forEach((part) => part.toggleAttribute('hidden', step !== 2));
}

/**
 * @param {string} text
 * @returns {string} lower case, without accents
 */
function normalize(text) {
    return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

/**
 * @param {Element} form
 * @param {HTMLElement} card
 */
function chooseType(form, card) {
    if (card instanceof HTMLButtonElement && card.disabled) {
        return;
    }
    const type = card.dataset.mdTypeCard;

    const chosen = form.querySelector('.md-type-chosen');
    if (chosen !== null) {
        chosen.className = chosen.className.replace(/\bmd-type-family-\S+/g, '').trim()
            + ' md-type-family-' + card.dataset.mdTypeFamily;
        const icon = chosen.querySelector('[data-md-type-chosen-icon] i');
        if (icon !== null) {
            icon.className = 'ti ' + card.dataset.mdTypeIcon;
        }
        const name = chosen.querySelector('[data-md-type-chosen-name]');
        if (name !== null) {
            name.textContent = card.dataset.mdTypeName;
        }
    }

    form.querySelector('[data-md-field-mandatory]')?.toggleAttribute('hidden', TITLE_TYPES.includes(type));
    // The additional label of an interval is mandatory, the comments of an information are its content
    if (INTERVAL_TYPES.includes(type) || type === 'informations') {
        form.querySelector('[data-md-field-more]')?.setAttribute('open', '');
    }

    const select = form.querySelector('select[name="type"]');
    if (select instanceof HTMLSelectElement && select.value !== type) {
        select.value = type;
        select.dispatchEvent(new Event('change', {bubbles: true}));
    }
    // The object selector reloads with its empty choice
    refreshValues(form, '');
    refreshTextHints(form);
    refreshBlockTitle(form);

    showStep(form, 2);
    form.querySelector('input[name="name"]')?.focus();
}

/**
 * @param {Element} picker
 * @param {string} query
 * @returns {HTMLElement[]} the cards left
 */
function filterTypes(picker, query) {
    const wanted = normalize(query.trim());
    const shown = [];
    picker.querySelectorAll('[data-md-type-group]').forEach((group) => {
        let count = 0;
        group.querySelectorAll('[data-md-type-card]').forEach((card) => {
            const hit = wanted === '' || normalize(card.textContent).includes(wanted);
            card.toggleAttribute('hidden', !hit);
            if (hit) {
                count++;
                shown.push(card);
            }
        });
        group.toggleAttribute('hidden', count === 0);
    });
    picker.querySelector('[data-md-type-none]')?.toggleAttribute('hidden', shown.length > 0);

    return shown;
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }
    const form = event.target.closest('[data-md-field-form]');
    if (form === null) {
        return;
    }

    const card = event.target.closest('[data-md-type-card]');
    if (card instanceof HTMLElement) {
        event.preventDefault();
        chooseType(form, card);
        return;
    }

    if (event.target.closest('[data-md-type-change]') !== null) {
        event.preventDefault();
        showStep(form, 1);
        form.querySelector('[data-md-type-search]')?.focus();
        return;
    }

    const fieldset = event.target.closest('[data-md-new-values]');
    if (fieldset === null) {
        return;
    }
    if (event.target.closest('[data-md-new-value-add]') !== null) {
        event.preventDefault();
        addValueRow(fieldset)?.focus();
        return;
    }
    const remove = event.target.closest('[data-md-new-value-remove]');
    if (remove !== null) {
        event.preventDefault();
        const row = remove.closest('[data-md-new-value]');
        if (fieldset.querySelectorAll('[data-md-new-value]').length > 1) {
            row?.remove();
        } else {
            // The last row is emptied instead
            row?.querySelectorAll('input').forEach((input) => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
        }
    }
});

// The object of a specific or multiple dropdown, changed natively (select2: see below)
document.addEventListener('change', (event) => {
    if (event.target instanceof HTMLSelectElement && event.target.matches('[data-md-field-form] select[name="item"]')) {
        refreshValues(event.target.closest('[data-md-field-form]'));
    }
});

// One default value for a radio button or a specific dropdown
document.addEventListener('change', (event) => {
    if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-md-new-value-default]')
        || !event.target.checked) {
        return;
    }
    const form = event.target.closest('[data-md-field-form]');
    const type = form?.querySelector('select[name="type"]')?.value ?? '';
    if (SINGLE_DEFAULT_TYPES.includes(type)) {
        event.target.closest('[data-md-new-values]')?.querySelectorAll('[data-md-new-value-default]').forEach((box) => {
            if (box !== event.target) {
                box.checked = false;
            }
        });
    }
});

// A pasted list gives one value per line
document.addEventListener('paste', (event) => {
    if (!(event.target instanceof HTMLInputElement) || !event.target.matches('[data-md-new-value-name]')) {
        return;
    }
    const lines = (event.clipboardData?.getData('text') ?? '').split(/\r?\n/).map((line) => line.trim()).filter((line) => line !== '');
    if (lines.length < 2) {
        return;
    }
    event.preventDefault();
    const fieldset = event.target.closest('[data-md-new-values]');
    let row = event.target.closest('[data-md-new-value]');
    let input = event.target;
    if (event.target.value.trim() === '') {
        event.target.value = lines.shift() ?? '';
    }
    lines.forEach((line) => {
        input = addValueRow(fieldset, row, line) ?? input;
        row = input.closest('[data-md-new-value]');
    });
    input.focus();
});

// The object of a specific dropdown: select2 fires jQuery change events only
if (typeof window.jQuery === 'function') {
    window.jQuery(document).on('change', '[data-md-field-form] select[name="item"]', function () {
        refreshValues(this.closest('[data-md-field-form]'));
    });
    // The block (also set by the "Add a field in this block" button, natively) and the type
    // of an existing field
    window.jQuery(document).on('change', '[data-md-field-form] select[name="rank"]', function () {
        refreshBlockTitle(this.closest('[data-md-field-form]'));
    });
    window.jQuery(document).on('change', '[data-md-field-form] select[name="type"]', function () {
        refreshTextHints(this.closest('[data-md-field-form]'));
    });
}

// A form given back after a refused add opens on its settings: its block is checked at once
document.addEventListener('shown.bs.modal', (event) => {
    if (event.target instanceof Element) {
        event.target.querySelectorAll('[data-md-field-form]').forEach((form) => {
            refreshTextHints(form);
            refreshBlockTitle(form);
        });
    }
});

document.addEventListener('input', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.matches('[data-md-type-search]')) {
        filterTypes(event.target.closest('[data-md-type-picker]'), event.target.value);
    }
});

// Enter in a custom value: a new value after it, the form is not submitted
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' || event.isComposing
        || !(event.target instanceof HTMLInputElement)
        || !event.target.matches('[data-md-new-value-name]')) {
        return;
    }
    event.preventDefault();
    addValueRow(event.target.closest('[data-md-new-values]'), event.target.closest('[data-md-new-value]'))?.focus();
});

// Enter in the search: the only type left is chosen, the form is never submitted from there
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter'
        || !(event.target instanceof HTMLInputElement)
        || !event.target.matches('[data-md-type-search]')) {
        return;
    }
    event.preventDefault();
    const shown = filterTypes(event.target.closest('[data-md-type-picker]'), event.target.value)
        .filter((card) => !(card instanceof HTMLButtonElement && card.disabled));
    if (shown.length === 1) {
        chooseType(event.target.closest('[data-md-field-form]'), shown[0]);
    }
});
