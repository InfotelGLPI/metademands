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
 * Layout of the wizard (templates/wizard_metademands.html.twig, data-md-wizard-layout).
 *
 * Progressive enhancement only: the form, its conditions, its validation and its step flow
 * stay those of public/scripts/metademands.js. This module reads the DOM they maintain and
 * adds, next to it:
 *  - one page form (.tab-nostep): a sticky summary of the blocks, with the mandatory fields
 *    left in each of them and a meter for the whole form;
 *  - step by step form (.tab-step): numbered steps in the tab bar, done ones ticked, and a
 *    "Step x of y" progress bar;
 *  - yes / no fields of the "buttons" display (select.yesno.md-yesno-buttons, also in the
 *    preview): two buttons driving the original select, which keeps its name, its value and
 *    the change event the conditions listen to.
 *
 * Every write is skipped when it would not change anything: the mutation observer that
 * follows the legacy scripts then never wakes up on its own changes.
 */

const WIZARD_SELECTOR = '.md-wizard[data-md-wizard-layout]';
const BLOCK_SELECTOR = ':scope > [bloc-id^="bloc"]';
/** Yes / no fields of the "buttons" display (Fields\Yesno::BUTTONS_DISPLAY) */
const YESNO_SELECTOR = 'select.yesno.md-yesno-buttons';
/** Elements added by this module, ignored by its mutation observer */
const OWN_ATTRIBUTE = 'data-md-own';

/** @type {WeakMap<HTMLElement, {scheduled: boolean, summary: ?HTMLElement, steps: ?HTMLElement, tocKey: string}>} */
const states = new WeakMap();

/**
 * sprintf() subset of the translated labels: %s and %1$s.
 *
 * @param {string} format
 * @param {...(string|number)} values
 * @returns {string}
 */
function format(format, ...values) {
    let next = 0;
    return format.replace(/%(?:(\d+)\$)?s/g, (match, position) => {
        const value = position !== undefined ? values[Number(position) - 1] : values[next++];
        return value === undefined ? match : String(value);
    });
}

/**
 * @param {HTMLElement} element
 * @param {string} text
 */
function setText(element, text) {
    if (element.textContent !== text) {
        element.textContent = text;
    }
}

/**
 * @param {HTMLElement} element
 * @returns {boolean} whether the element is rendered (not hidden by itself or an ancestor)
 */
function isShown(element) {
    // A block folded by the chevron of its title (data-md-folded, public/scripts/wizard_form.js)
    // only hides its content from view: its fields are shown, unless hidden by themselves
    // between the field and the folded content, or with the block itself. They read as
    // "Optional" as soon as the block was folded.
    const folded = element.closest('[data-md-folded]');
    if (folded !== null && folded !== element) {
        for (let node = element; node !== null && node !== folded; node = node.parentElement) {
            if (getComputedStyle(node).display === 'none') {
                return false;
            }
        }
        return folded.parentElement === null || isShown(folded.parentElement);
    }
    if (typeof element.checkVisibility === 'function') {
        return element.checkVisibility();
    }
    return element.offsetParent !== null;
}

/* -------------------------------------------------------------------------
 * Mandatory fields
 * ---------------------------------------------------------------------- */

/**
 * @param {HTMLElement} field wrapper of a field ([id-field])
 * @returns {boolean} whether the field currently shows its mandatory mark
 */
function isMandatory(field) {
    // A switch (yes / no of the "switch" display) always holds an answer, "no" by default:
    // never a mandatory field left
    if (field.querySelector('input[isswitch]') !== null) {
        return false;
    }
    const mark = field.querySelector('.metademands_wizard_red');
    return mark !== null && mark.textContent.includes('*') && isShown(field);
}

/**
 * Whether a field holds an answer: an indicator for the summary only, the validation of
 * the form stays plugin_metademands_wizard_validateForm().
 *
 * @param {HTMLElement} field wrapper of a field ([id-field])
 * @returns {boolean}
 */
function isFilled(field) {
    // Free table: answered by its validated lines only, not by the line being typed (the
    // check of plugin_metademands_wizard_validateForm() and of the server)
    if (field.querySelector('table[id^="freetable_table"]') !== null) {
        return field.querySelector('tr[name="data"][id^="line_"]') !== null;
    }
    for (const control of field.querySelectorAll('input, select, textarea')) {
        if (control.disabled) {
            continue;
        }
        if (control instanceof HTMLSelectElement) {
            const values = [...control.selectedOptions].map((option) => option.value);
            if (values.some((value) => value !== '' && value !== '0')) {
                return true;
            }
            continue;
        }
        if (control instanceof HTMLTextAreaElement) {
            const editor = window.tinymce?.get(control.id);
            // The comment of the field shown in the empty editor (#placeholder, see initRichText()
            // of wizard_form.js) is no answer: it was read as one, a mandatory textarea with a
            // comment counted as filled
            if (editor?.dom?.get('placeholder')) {
                continue;
            }
            const text = editor ? editor.getContent({format: 'text'}) : control.value;
            if (text.trim() !== '') {
                return true;
            }
            continue;
        }
        switch (control.type) {
            case 'checkbox':
            case 'radio':
                if (control.checked) {
                    return true;
                }
                break;
            case 'file':
                if (control.files !== null && control.files.length > 0) {
                    return true;
                }
                break;
            case 'button':
            case 'submit':
            case 'reset':
                break;
            case 'hidden':
                // Uploaded files and date pickers keep their value in a hidden input, and so
                // does a read-only field (its widget is disabled, the hidden field[N] input
                // carries the value posted); the other hidden inputs are technical ones
                if ((control.classList.contains('flatpickr-input') || /^(_|field\[)/.test(control.name))
                    && control.value !== '' && control.value !== '0') {
                    return true;
                }
                break;
            default:
                if (control.value.trim() !== '') {
                    return true;
                }
        }
    }
    return false;
}

/**
 * Whether isFilled() is sure of the answer of a field: plain inputs, lists and choices. A
 * file, a signature or a free table keep their answer elsewhere (uploads, canvas, lines):
 * they never hold the send button, the validation of the form decides for them.
 *
 * @param {HTMLElement} field wrapper of a field ([id-field])
 * @returns {boolean}
 */
function isCheckedSurely(field) {
    // A free table: its validated lines tell for sure
    if (field.querySelector('table[id^="freetable_table"]') !== null) {
        return true;
    }
    if (field.querySelector('input[type="file"], canvas, table, .fileupload, [data-uploader-name]') !== null) {
        return false;
    }
    return field.querySelector('input:not([type="hidden"]), select, textarea') !== null
        || field.querySelector('input[type="hidden"][name^="field["]') !== null;
}

/**
 * @param {HTMLElement} field wrapper of a field ([id-field])
 * @returns {string} its label, without the mandatory mark nor the help
 */
function fieldLabel(field) {
    const label = field.querySelector('.metademand-label');
    if (label === null) {
        return '';
    }
    const clone = /** @type {HTMLElement} */ (label.cloneNode(true));
    clone.querySelectorAll('.metademands_wizard_red, .form-help, a, i, script').forEach((element) => element.remove());
    return clone.textContent.replace(/\s+/g, ' ').trim();
}

/**
 * @param {HTMLElement} root
 * @returns {{total: number, filled: number, missing: HTMLElement[]}} the mandatory fields
 *          shown, those filled, and the wrappers of those left
 */
function countMandatory(root) {
    let total = 0;
    let filled = 0;
    /** @type {HTMLElement[]} */
    const missing = [];
    root.querySelectorAll('[id-field^="field"]').forEach((field) => {
        if (isMandatory(/** @type {HTMLElement} */ (field))) {
            total++;
            if (isFilled(/** @type {HTMLElement} */ (field))) {
                filled++;
            } else {
                missing.push(/** @type {HTMLElement} */ (field));
            }
        }
    });
    return {total, filled, missing};
}

/**
 * Bring a field into view and give it the focus.
 *
 * @param {HTMLElement} field wrapper of a field ([id-field])
 */
function goToField(field) {
    field.scrollIntoView({behavior: 'smooth', block: 'center'});
    const control = field.querySelector('input:not([type="hidden"]), select, textarea, button');
    if (control instanceof HTMLElement) {
        control.focus({preventScroll: true});
    }
}

/**
 * Bring the top of a block into view, header (title-block) included: scrollIntoView() alone
 * slides it under the sticky header of GLPI (header.navbar.sticky-lg-top).
 *
 * @param {HTMLElement} block
 */
function goToBlock(block) {
    const header = document.querySelector('header.navbar');
    let offset = 16;
    if (header instanceof HTMLElement && ['sticky', 'fixed'].includes(getComputedStyle(header).position)) {
        offset += header.getBoundingClientRect().height;
    }
    window.scrollTo({
        top: window.scrollY + block.getBoundingClientRect().top - offset,
        behavior: 'smooth',
    });
}

/**
 * Tooltip of a count of mandatory fields: the labels of those left.
 *
 * @param {HTMLElement} element
 * @param {HTMLElement[]} missing
 */
function setMissingTitle(element, missing) {
    const title = missing.map(fieldLabel).filter((label) => label !== '').join(', ');
    if (element.title !== title) {
        element.title = title;
    }
}

/**
 * Header of a block: the title-block field, which sits in its own field wrapper
 * (<div id-field="fieldN" class="col-md-12 form-group">). Marked md-block-header, its wrapper
 * md-block-header-wrap, for public/css/metademands.css.
 *
 * @param {HTMLElement} block
 * @returns {?HTMLElement}
 */
function blockHeader(block) {
    const header = block.querySelector(':scope > .card-header, :scope > [id-field] > .card-header');
    if (!(header instanceof HTMLElement)) {
        return null;
    }
    if (!header.classList.contains('md-block-header')) {
        header.classList.add('md-block-header');
    }
    const wrapper = header.parentElement;
    if (wrapper !== null && wrapper !== block && !wrapper.classList.contains('md-block-header-wrap')) {
        wrapper.classList.add('md-block-header-wrap');
    }
    return header;
}

/**
 * Next to the title of each block: its mandatory fields, ticked once all are filled, or
 * "Optional".
 *
 * @param {HTMLElement} wizard
 * @param {HTMLElement} block
 * @param {{total: number, filled: number, missing: HTMLElement[]}} counts
 */
function refreshBlockMeta(wizard, block, counts) {
    const header = blockHeader(block);
    if (header === null) {
        return;
    }
    let meta = header.querySelector(':scope > .md-block-meta');
    if (!(meta instanceof HTMLElement)) {
        meta = document.createElement('span');
        meta.className = 'md-block-meta';
        meta.setAttribute(OWN_ATTRIBUTE, '');
        header.append(meta);
    }
    const complete = counts.total > 0 && counts.missing.length === 0;
    let text = wizard.dataset.mdLabelOptional ?? '';
    if (counts.total > 0) {
        text = (complete ? '✓ ' : '') + format(
            (counts.total === 1 ? wizard.dataset.mdLabelMandatoryOne : wizard.dataset.mdLabelMandatoryOther) ?? '%s',
            counts.total,
        );
    }
    setText(meta, text);
    if (meta.classList.contains('is-complete') !== complete) {
        meta.classList.toggle('is-complete', complete);
    }
    setMissingTitle(meta, counts.missing);
}

/**
 * In the actions at the bottom: the number of sections of a one page form, the step of a
 * step by step one.
 *
 * @param {HTMLElement} wizard
 * @param {string} text
 */
function refreshFooterState(wizard, text) {
    const actions = wizard.querySelector('.form-sc-group > .center > div');
    if (actions === null) {
        return;
    }
    let state = actions.querySelector(':scope > .md-footer-state');
    if (!(state instanceof HTMLElement)) {
        state = document.createElement('span');
        state.className = 'md-footer-state';
        state.setAttribute(OWN_ATTRIBUTE, '');
        actions.prepend(state);
    }
    setText(state, text);
}

/* -------------------------------------------------------------------------
 * One page form: summary of the blocks
 * ---------------------------------------------------------------------- */

/**
 * @param {HTMLElement} block
 * @param {HTMLElement} wizard
 * @returns {string}
 */
function blockTitle(block, wizard) {
    const heading = block.querySelector('.card-header .card-title, h4');
    if (heading !== null) {
        const clone = /** @type {HTMLElement} */ (heading.cloneNode(true));
        clone.querySelectorAll('.form-help, a, i, script').forEach((element) => element.remove());
        const text = clone.textContent.replace(/\s+/g, ' ').trim();
        if (text !== '') {
            return text;
        }
    }
    const rank = (block.getAttribute('bloc-id') ?? '').replace('bloc', '');
    return format(wizard.dataset.mdLabelBlock ?? '%s', rank);
}

/**
 * @param {HTMLElement} wizard
 * @param {HTMLElement} container .tab-nostep
 * @returns {HTMLElement}
 */
function createSummary(wizard, container) {
    const summary = document.createElement('aside');
    summary.className = 'card md-summary';
    summary.setAttribute(OWN_ATTRIBUTE, '');

    const header = document.createElement('div');
    header.className = 'card-header py-2';
    const title = document.createElement('h4');
    title.className = 'card-title m-0';
    title.textContent = wizard.dataset.mdLabelSummary ?? '';
    header.append(title);

    const list = document.createElement('nav');
    list.className = 'list-group list-group-flush md-summary-list';

    const footer = document.createElement('div');
    footer.className = 'card-footer py-2 small text-secondary';
    const meterText = document.createElement('div');
    meterText.className = 'md-summary-meter-text';
    const meter = document.createElement('div');
    meter.className = 'progress progress-sm mt-1';
    const bar = document.createElement('div');
    bar.className = 'progress-bar';
    bar.style.width = '0%';
    meter.append(bar);
    footer.append(meterText, meter);

    summary.append(header, list, footer);

    list.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('[data-md-summary-block]') : null;
        if (link === null) {
            return;
        }
        event.preventDefault();
        const block = container.querySelector(`:scope > [bloc-id="${CSS.escape(link.dataset.mdSummaryBlock)}"]`);
        if (!(block instanceof HTMLElement)) {
            return;
        }
        // On the count of the fields left: straight to the first of them
        const onCount = event.target instanceof Element && event.target.closest('.md-summary-count') !== null;
        const first = onCount ? countMandatory(block).missing[0] : undefined;
        if (first !== undefined) {
            goToField(first);
        } else {
            goToBlock(block);
        }
    });

    container.prepend(summary);
    return summary;
}

/**
 * @param {HTMLElement} wizard
 */
function refreshSummary(wizard) {
    const state = states.get(wizard);
    const container = wizard.querySelector('.tab-nostep');
    if (container === null || state === undefined) {
        return;
    }
    const blocks = [...container.querySelectorAll(BLOCK_SELECTOR)].filter(isShown);
    const entries = blocks.map((block) => ({
        id: block.getAttribute('bloc-id') ?? '',
        title: blockTitle(block, wizard),
        counts: countMandatory(block),
    }));
    entries.forEach((entry, index) => refreshBlockMeta(wizard, blocks[index], entry.counts));
    // The mandatory fields left hold the send button of the request, when the check is sure
    const missing = entries.flatMap((entry) => entry.counts.missing);
    state.mandatoryMissing = wizard.dataset.mdCanSend !== undefined && missing.every(isCheckedSurely)
        ? missing
        : [];
    refreshFooterState(wizard, format(
        (blocks.length === 1 ? wizard.dataset.mdLabelSectionsOne : wizard.dataset.mdLabelSectionsOther) ?? '%s',
        blocks.length,
    ));

    // Numbers and summary only make sense with several blocks shown
    const enabled = blocks.length > 1;
    if (container.classList.contains('md-has-summary') !== enabled) {
        container.classList.toggle('md-has-summary', enabled);
    }
    if (container.classList.contains('md-numbered') !== enabled) {
        container.classList.toggle('md-numbered', enabled);
    }
    if (!enabled) {
        return;
    }
    if (state.summary === null || !state.summary.isConnected) {
        state.summary = createSummary(wizard, container);
        state.tocKey = '';
    }

    const list = /** @type {HTMLElement} */ (state.summary.querySelector('.md-summary-list'));

    // The links are rebuilt only when the shown blocks change
    const key = entries.map((entry) => `${entry.id}:${entry.title}`).join('|');
    if (key !== state.tocKey) {
        state.tocKey = key;
        list.replaceChildren(...entries.map((entry, index) => {
            const link = document.createElement('a');
            link.href = '#';
            link.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2';
            link.dataset.mdSummaryBlock = entry.id;
            const number = document.createElement('span');
            number.className = 'md-summary-number';
            number.textContent = String(index + 1);
            const text = document.createElement('span');
            text.className = 'md-summary-text';
            const label = document.createElement('span');
            label.className = 'd-block text-truncate';
            label.textContent = entry.title;
            label.title = entry.title;
            // The mandatory fields left, by their label
            const missing = document.createElement('span');
            missing.className = 'md-summary-missing d-block text-truncate text-danger';
            text.append(label, missing);
            const count = document.createElement('span');
            count.className = 'md-summary-count ms-auto';
            link.append(number, text, count);
            return link;
        }));
    }

    let total = 0;
    let filled = 0;
    entries.forEach((entry, index) => {
        total += entry.counts.total;
        filled += entry.counts.filled;
        const link = /** @type {HTMLElement} */ (list.children[index]);
        const count = /** @type {HTMLElement} */ (link.querySelector('.md-summary-count'));
        const left = entry.counts.total - entry.counts.filled;
        const complete = entry.counts.total > 0 && left === 0;
        let text = '';
        if (complete) {
            text = '✓';
        } else if (left > 0) {
            text = format(
                (left === 1 ? wizard.dataset.mdLabelLeftOne : wizard.dataset.mdLabelLeftOther) ?? '%s',
                left,
            );
        }
        setText(count, text);
        if (count.classList.contains('text-success') !== complete) {
            count.classList.toggle('text-success', complete);
        }
        if (count.classList.contains('text-danger') !== left > 0) {
            count.classList.toggle('text-danger', left > 0);
        }
        if (complete) {
            if (count.title !== (wizard.dataset.mdLabelComplete ?? '')) {
                count.title = wizard.dataset.mdLabelComplete ?? '';
            }
        } else {
            // The fields left, by their label: a click on the count goes to the first one
            setMissingTitle(count, entry.counts.missing);
        }
        const missing = /** @type {HTMLElement} */ (link.querySelector('.md-summary-missing'));
        const labels = entry.counts.missing.map(fieldLabel).filter((name) => name !== '').join(', ');
        setText(missing, labels);
        if (missing.title !== labels) {
            missing.title = labels;
        }
    });

    const meterText = /** @type {HTMLElement} */ (state.summary.querySelector('.md-summary-meter-text'));
    const bar = /** @type {HTMLElement} */ (state.summary.querySelector('.progress-bar'));
    setText(meterText, format(wizard.dataset.mdLabelFilled ?? '%1$s / %2$s', filled, total));
    const width = `${total > 0 ? Math.round(filled / total * 100) : 100}%`;
    if (bar.style.width !== width) {
        bar.style.width = width;
    }
    // Every mandatory field filled: the meter turns green
    const complete = filled >= total;
    if (bar.classList.contains('bg-success') !== complete) {
        bar.classList.toggle('bg-success', complete);
    }
    refreshActiveBlock(wizard);
}

/**
 * Highlight in the summary the block at the top of the screen.
 *
 * @param {HTMLElement} wizard
 */
function refreshActiveBlock(wizard) {
    const summary = states.get(wizard)?.summary;
    const container = wizard.querySelector('.tab-nostep');
    if (summary === null || summary === undefined || container === null) {
        return;
    }
    const blocks = [...container.querySelectorAll(BLOCK_SELECTOR)].filter(isShown);
    let active = blocks[0];
    for (const block of blocks) {
        if (block.getBoundingClientRect().top <= 120) {
            active = block;
        }
    }
    const activeId = active?.getAttribute('bloc-id') ?? '';
    summary.querySelectorAll('[data-md-summary-block]').forEach((link) => {
        const isActive = link.getAttribute('data-md-summary-block') === activeId;
        if (link.classList.contains('active') !== isActive) {
            link.classList.toggle('active', isActive);
        }
    });
}

/* -------------------------------------------------------------------------
 * Step by step form: stepper and progress
 * ---------------------------------------------------------------------- */

/**
 * @param {HTMLElement} wizard
 */
function refreshSteps(wizard) {
    const state = states.get(wizard);
    const steps = [...wizard.querySelectorAll('.tab-step')];
    if (state === undefined || steps.length === 0) {
        return;
    }
    // The steps the flow can show: the block of a hidden step is set to display: none
    const reachable = steps.filter((step) => {
        const block = step.firstElementChild;
        return block instanceof HTMLElement && block.style.display !== 'none';
    });
    const current = steps.find((step) => step.style.display === 'block');
    const index = current !== undefined ? reachable.indexOf(current) : -1;
    // A new step reached: its fields join the known ones of the partial check
    state.lastStep = current !== undefined ? steps.indexOf(current) : -1;

    if (state.steps === null || !state.steps.isConnected) {
        state.steps = document.createElement('div');
        state.steps.className = 'md-steps-progress';
        state.steps.setAttribute(OWN_ATTRIBUTE, '');
        const text = document.createElement('div');
        text.className = 'md-steps-text small text-secondary';
        const meter = document.createElement('div');
        meter.className = 'progress progress-sm';
        const bar = document.createElement('div');
        bar.className = 'progress-bar';
        meter.append(bar);
        state.steps.append(text, meter);
        const anchor = wizard.querySelector('.tabs-container') ?? steps[0];
        if (anchor.classList.contains('tabs-container')) {
            anchor.after(state.steps);
        } else {
            anchor.before(state.steps);
        }
    }

    const position = index >= 0 ? index + 1 : 1;
    const text = /** @type {HTMLElement} */ (state.steps.querySelector('.md-steps-text'));
    const bar = /** @type {HTMLElement} */ (state.steps.querySelector('.progress-bar'));
    const step_text = format(wizard.dataset.mdLabelStep ?? '%1$s / %2$s', position, Math.max(reachable.length, 1));
    // Shown above the steps only: not repeated in the actions at the bottom, whose state
    // stays as an empty spacer pushing the buttons to the right
    setText(text, step_text);
    refreshFooterState(wizard, '');
    // Only the block of the current step is shown: its fields are the ones counted
    const current_block = current?.firstElementChild;
    let current_left = 0;
    if (current_block instanceof HTMLElement) {
        const counts = countMandatory(current_block);
        current_left = counts.missing.length;
        refreshBlockMeta(wizard, current_block, counts);
    }
    const width = `${Math.round(position / Math.max(reachable.length, 1) * 100)}%`;
    if (bar.style.width !== width) {
        bar.style.width = width;
    }
    // Last step with its mandatory fields filled (the previous ones were checked to reach
    // it): the bar turns green, as the meter of a one page form
    const complete = position >= reachable.length && current_left === 0;
    if (bar.classList.contains('bg-success') !== complete) {
        bar.classList.toggle('bg-success', complete);
    }

    // Tab bar: the visible tabs before the active one are done
    const links = [...wizard.querySelectorAll('.tabs-container a.tablinks')]
        .filter((link) => link instanceof HTMLElement && link.style.display !== 'none');
    let activeIndex = links.findIndex((link) => link.classList.contains('active'));
    if (activeIndex < 0 && current !== undefined) {
        const rank = (current.firstElementChild?.getAttribute('bloc-id') ?? '').replace('bloc', '');
        activeIndex = links.findIndex((link) => link.id === `ablock${rank}`);
    }
    links.forEach((link, linkIndex) => {
        const done = activeIndex >= 0 && linkIndex < activeIndex;
        if (link.classList.contains('md-step-done') !== done) {
            link.classList.toggle('md-step-done', done);
        }
    });
}

/* -------------------------------------------------------------------------
 * Yes / no buttons
 * ---------------------------------------------------------------------- */

/**
 * @param {HTMLSelectElement} select
 * @param {HTMLElement} group
 */
function syncYesNo(select, group) {
    group.querySelectorAll('button').forEach((button) => {
        const active = button.dataset.mdValue === select.value;
        if (button.classList.contains('active') !== active) {
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
        if (button.disabled !== select.disabled) {
            button.disabled = select.disabled;
        }
    });
    const invalid = select.classList.contains('invalid');
    if (group.classList.contains('is-invalid') !== invalid) {
        group.classList.toggle('is-invalid', invalid);
    }
}

/**
 * @param {HTMLSelectElement} select
 */
function enhanceYesNo(select) {
    if (select.dataset.mdYesno !== undefined || select.multiple) {
        return;
    }
    const choices = [...select.options].filter((option) => option.value !== '' && option.value !== '0');
    if (choices.length === 0) {
        return;
    }
    select.dataset.mdYesno = '';

    const group = document.createElement('div');
    group.className = 'btn-group md-yesno';
    group.setAttribute('role', 'group');
    group.setAttribute(OWN_ATTRIBUTE, '');
    // Yes (2) before No (1), as they are read
    choices.sort((a, b) => Number(b.value) - Number(a.value)).forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        // Colours of public/css/metademands.css (.md-yesno), as in the mockup
        button.className = 'btn';
        button.dataset.mdValue = option.value;
        button.textContent = option.text;
        button.setAttribute('aria-pressed', 'false');
        group.append(button);
    });
    const empty = [...select.options].find((option) => option.value === '' || option.value === '0');

    group.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest('button') : null;
        if (button === null || select.disabled) {
            return;
        }
        // A second click on the answer of an optional field clears it
        let value = button.dataset.mdValue ?? '';
        if (value === select.value && empty !== undefined && !select.required) {
            value = empty.value;
        }
        select.value = value;
        // Listened to by the conditions (jQuery handlers receive native events) and select2
        select.dispatchEvent(new Event('change', {bubbles: true}));
        syncYesNo(select, group);
    });
    select.addEventListener('change', () => syncYesNo(select, group));
    new MutationObserver(() => syncYesNo(select, group))
        .observe(select, {attributes: true, attributeFilter: ['class', 'disabled']});

    select.after(group);
    syncYesNo(select, group);
}

/* -------------------------------------------------------------------------
 * Conditions of the metademand
 * ---------------------------------------------------------------------- */

/**
 * Step by step form: whether the steps already filled in break the conditions of the
 * metademand, whatever the next ones hold. ajax/condition.php evaluates the conditions with
 * only the fields of the steps reached known (known_fields): with the others true, the
 * conditions can no longer be met when it is false; with the others false, they are met for
 * good when it is true. The answer is kept in the state and applied by
 * refreshConditionsMessage(), so that "Next" gives way to the message without waiting for
 * the last step.
 *
 * @param {HTMLElement} wizard
 */
async function checkStepConditions(wizard) {
    const state = states.get(wizard);
    const url = wizard.dataset.mdConditionsUrl;
    const form = wizard.closest('form') ?? document.getElementById('wizard_form');
    const steps = [...wizard.querySelectorAll('.tab-step')];
    if (state === undefined || url === undefined || !(form instanceof HTMLFormElement) || steps.length === 0) {
        return;
    }
    const current = steps.findIndex((step) => step.style.display === 'block');
    const known = new Set();
    steps.slice(0, current + 1).forEach((step) => {
        step.querySelectorAll('[id-field^="field"]').forEach((field) => {
            const match = /^field(\d+)$/.exec(field.getAttribute('id-field') ?? '');
            if (match !== null) {
                known.add(match[1]);
            }
        });
    });

    window.tinymce?.triggerSave();
    const body = new URLSearchParams();
    for (const [name, value] of new FormData(form)) {
        if (typeof value === 'string') {
            body.append(name, value);
        }
    }
    known.forEach((id) => body.append('known_fields[]', id));

    const token = document.querySelector('meta[property="glpi:csrf_token"]')?.getAttribute('content');
    // Only the latest answer counts: the request it replaces is aborted
    state.conditionsAbort?.abort();
    const abort = new AbortController();
    state.conditionsAbort = abort;
    let result;
    try {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            signal: abort.signal,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? {'X-Glpi-Csrf-Token': token} : {}),
            },
            body: body.toString(),
        });
        if (!response.ok) {
            return;
        }
        result = await response.json();
    } catch {
        // Aborted for a newer one, or no answer: the legacy check of the last step remains
        return;
    }
    if (state.conditionsAbort !== abort) {
        return;
    }
    // "Hidden unless" (0): the button needs the conditions met; "Displayed unless" (1): met,
    // they hide it
    const blocked = wizard.dataset.mdConditionsShowButton === '1'
        ? Boolean(result?.pessimistic)
        : !result?.optimistic;
    if (state.conditionsBlocked !== blocked) {
        state.conditionsBlocked = blocked;
        // Applied right away, not on the next frame
        refreshConditionsMessage(wizard);
    }
}

/**
 * Run checkStepConditions() as soon as an answer or the step changes. The answers are
 * compared by their values, whatever changed them: a select2 list or a legacy script fire
 * no native change event. A short delay gathers the keys typed in a row.
 *
 * @param {HTMLElement} wizard
 */
function watchStepConditions(wizard) {
    const state = states.get(wizard);
    const form = wizard.closest('form') ?? document.getElementById('wizard_form');
    if (state === undefined || wizard.dataset.mdConditions === undefined
        || !(form instanceof HTMLFormElement) || wizard.querySelector('.tab-step') === null) {
        return;
    }
    const values = [];
    for (const [name, value] of new FormData(form)) {
        if (typeof value === 'string') {
            values.push(`${name}=${value}`);
        }
    }
    const signature = `${state.lastStep}|${values.join('&')}`;
    if (signature === state.conditionsSignature) {
        return;
    }
    state.conditionsSignature = signature;
    clearTimeout(state.conditionsTimer);
    state.conditionsTimer = setTimeout(() => checkStepConditions(wizard), 150);
}

/**
 * In place of the send button that plugin_metademands_wizard_checkConditions() hides while the
 * request does not meet the conditions of the metademand: a message saying so. The button is
 * recognised by its title (the "conditions_submit" one of data-md-titles), as the legacy
 * script does; not while the form is being sent, when the loader hides it too.
 *
 * @param {HTMLElement} wizard
 */
function refreshConditionsMessage(wizard) {
    const state = states.get(wizard);
    const button = wizard.querySelector('#nextBtn');
    if (state === undefined || !(button instanceof HTMLElement) || button.parentElement === null) {
        return;
    }

    // The conditions of the metademand hide the send button (legacy check)
    let hidden_by_conditions = false;
    if (wizard.dataset.mdConditions !== undefined) {
        let titles = {};
        try {
            titles = JSON.parse(button.dataset.mdTitles ?? '{}');
        } catch {
            titles = {};
        }
        const label = titles.conditions_submit?.label ?? '';
        hidden_by_conditions = label !== ''
            && button.style.display === 'none'
            && button.textContent.trim() === label.trim();
    }
    // A step by step form broken by its steps already filled, a one page form whose mandatory
    // fields are not all filled: "Next" / the send button hidden by the CSS
    const broken_steps = state.conditionsBlocked === true;
    const incomplete = state.mandatoryMissing.length > 0;
    const hide_next = broken_steps || incomplete;
    if (wizard.classList.contains('md-conditions-blocked') !== hide_next) {
        wizard.classList.toggle('md-conditions-blocked', hide_next);
    }
    // Sending: the legacy scripts show the loader with an inline display (jQuery show()); its
    // "hidden" class is styled nowhere, so its visibility tells nothing
    const loader = document.getElementById('ajax_loader');
    const sending = loader !== null && loader.style.display !== '' && loader.style.display !== 'none';
    const blocked = (hidden_by_conditions || hide_next) && !sending;

    let message = button.parentElement.querySelector(':scope > .md-conditions-message');
    if (!blocked) {
        message?.remove();
        return;
    }
    if (!(message instanceof HTMLElement)) {
        // A button: with mandatory fields left, it leads to the first of them
        message = document.createElement('button');
        message.type = 'button';
        message.setAttribute('form', '');
        message.className = 'md-conditions-message';
        message.setAttribute(OWN_ATTRIBUTE, '');
        message.setAttribute('aria-live', 'polite');
        const icon = document.createElement('i');
        icon.className = 'ti ti-lock';
        icon.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.textContent = wizard.dataset.mdLabelConditions ?? '';
        message.append(icon, text);
        message.addEventListener('click', () => {
            const first = states.get(wizard)?.mandatoryMissing[0];
            if (first !== undefined) {
                goToField(first);
            }
        });
        button.after(message);
    }
    // The fields left, by their label
    const title = state.mandatoryMissing.map(fieldLabel).filter((name) => name !== '').join(', ');
    if (message.title !== title) {
        message.title = title;
    }
    if (message.classList.contains('is-actionable') !== incomplete) {
        message.classList.toggle('is-actionable', incomplete);
    }
}

/* -------------------------------------------------------------------------
 * Wiring
 * ---------------------------------------------------------------------- */

/**
 * @param {HTMLElement} wizard
 */
function refresh(wizard) {
    wizard.querySelectorAll(YESNO_SELECTOR).forEach((select) => {
        enhanceYesNo(/** @type {HTMLSelectElement} */ (select));
        const group = select.parentElement?.querySelector(':scope > .md-yesno');
        if (group instanceof HTMLElement) {
            // Values set by the legacy scripts through jQuery fire no native event
            syncYesNo(/** @type {HTMLSelectElement} */ (select), group);
        }
    });
    refreshSummary(wizard);
    refreshSteps(wizard);
    watchStepConditions(wizard);
    refreshConditionsMessage(wizard);
}

/**
 * The yes / no fields of the "buttons" display, wherever they are: the wizard, but also the
 * preview of the "Wizard creation" tab, loaded through Ajax.
 */
function enhanceAllYesNo() {
    document.querySelectorAll(`${YESNO_SELECTOR}:not([data-md-yesno])`)
        .forEach((select) => enhanceYesNo(/** @type {HTMLSelectElement} */ (select)));
}

/**
 * @param {HTMLElement} wizard
 */
function schedule(wizard) {
    const state = states.get(wizard);
    if (state === undefined || state.scheduled) {
        return;
    }
    state.scheduled = true;
    requestAnimationFrame(() => {
        state.scheduled = false;
        if (wizard.isConnected) {
            refresh(wizard);
        }
    });
}

function scheduleAll() {
    document.querySelectorAll(WIZARD_SELECTOR).forEach((wizard) => schedule(/** @type {HTMLElement} */ (wizard)));
}

/**
 * @param {HTMLElement} wizard
 */
function init(wizard) {
    if (states.has(wizard)) {
        return;
    }
    states.set(wizard, {
        scheduled: false,
        summary: null,
        steps: null,
        tocKey: '',
        // Partial check of the conditions in a step by step form (checkStepConditions())
        conditionsBlocked: false,
        conditionsTimer: 0,
        conditionsSignature: '',
        /** @type {?AbortController} */
        conditionsAbort: null,
        lastStep: -1,
        /** @type {HTMLElement[]} mandatory fields left of a one page form (refreshSummary()) */
        mandatoryMissing: [],
    });
    wizard.classList.add('md-wizard-layout');

    ['input', 'change', 'focusin', 'click'].forEach((type) => {
        wizard.addEventListener(type, () => schedule(wizard));
    });
    // The mandatory count of a block leads to its first field left
    wizard.addEventListener('click', (event) => {
        const meta = event.target instanceof Element ? event.target.closest('.md-block-meta') : null;
        const block = meta?.closest('[bloc-id^="bloc"]');
        if (block instanceof HTMLElement) {
            const first = countMandatory(block).missing[0];
            if (first !== undefined) {
                goToField(first);
            }
        }
    });
    // Conditions show and hide blocks and fields, mandatory marks come and go
    new MutationObserver((mutations) => {
        const foreign = mutations.some((mutation) => {
            const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;
            return target !== null && target.closest(`[${OWN_ATTRIBUTE}]`) === null;
        });
        if (foreign) {
            schedule(wizard);
        }
    }).observe(wizard, {subtree: true, childList: true, attributes: true, attributeFilter: ['style', 'class']});

    refresh(wizard);
}

function initAll() {
    document.querySelectorAll(WIZARD_SELECTOR).forEach((wizard) => init(/** @type {HTMLElement} */ (wizard)));
}

// select2 and the date pickers open their lists at the end of the body
document.addEventListener('mouseup', scheduleAll);
document.addEventListener('keyup', scheduleAll);
document.addEventListener('scroll', () => {
    document.querySelectorAll(WIZARD_SELECTOR).forEach((wizard) => refreshActiveBlock(/** @type {HTMLElement} */ (wizard)));
}, {capture: true, passive: true});

// The service catalogue replaces the whole .md-wizard through Ajax, the "Wizard creation"
// tab loads its preview the same way
new MutationObserver(() => {
    if (document.querySelector(`${WIZARD_SELECTOR}:not(.md-wizard-layout)`) !== null) {
        initAll();
    }
    if (document.querySelector(`${YESNO_SELECTOR}:not([data-md-yesno])`) !== null) {
        enhanceAllYesNo();
    }
}).observe(document.body, {childList: true, subtree: true});

initAll();
enhanceAllYesNo();
