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
 * "Wizard creation" tab of a metademand (templates/field_list.html.twig).
 *
 * The list wrapper carries data-md-field-list (metademand id) and the URLs of the block
 * preview and of the field move. Handled here: search and type filter, preview of the
 * selected block kept in sync with the selected field, multiple selection, drag and drop
 * of the fields inside a block and between blocks, add modals (optionally prefilled with
 * a block), purge confirmation.
 *
 * Loaded as a module (setup.php, ADD_JAVASCRIPT_MODULE): the list is a tab of the
 * metademand form, injected after load, hence the MutationObserver at the end.
 */

/* global glpi_confirm, glpi_toast_error, bootstrap */

/**
 * Insert HTML returned by GLPI into a container, running the scripts it carries (select2
 * setups, dynamic dropdowns...): innerHTML never runs them on its own.
 *
 * @param {HTMLElement} container
 * @param {string} html
 */
function injectHtml(container, html) {
    container.innerHTML = html;
    runScripts(container);
}

/**
 * Insert HTML holding its own <form> into a container that sits inside another form (the
 * lines of the fields are in the massive actions one). Parsed through innerHTML, the nested
 * <form> would be dropped and its inputs would join the outer form: parsed in a <template>,
 * it is kept, and its inputs belong to it.
 *
 * @param {HTMLElement} container
 * @param {string} html
 */
function injectForm(container, html) {
    const template = document.createElement('template');
    template.innerHTML = html;
    container.replaceChildren(template.content);
    runScripts(container);
}

/**
 * Run the scripts of inserted HTML: those parsed from a string never run on their own.
 *
 * @param {HTMLElement} container
 */
function runScripts(container) {
    container.querySelectorAll('script').forEach((original) => {
        const script = document.createElement('script');
        Array.from(original.attributes).forEach((attribute) => {
            script.setAttribute(attribute.name, attribute.value);
        });
        script.textContent = original.textContent;
        original.replaceWith(script);
    });
}

/**
 * POST form-encoded data to a GLPI endpoint. The core CSRF check validates same-origin
 * requests from the browser headers; X-Requested-With routes them through its AJAX branch.
 *
 * @param {string} url
 * @param {Record<string, unknown>} data nested objects are encoded as name[key] pairs
 * @returns {Promise<Response>}
 */
function post(url, data) {
    const body = new URLSearchParams();
    const append = (name, value) => {
        if (value !== null && typeof value === 'object') {
            Object.entries(value).forEach(([key, item]) => append(`${name}[${key}]`, item));
        } else {
            body.append(name, value === undefined || value === null ? '' : String(value));
        }
    };
    Object.entries(data).forEach(([name, value]) => append(name, value));

    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
    });
}

/**
 * @param {HTMLElement} list
 * @returns {boolean} whether the preview column is shown
 */
function isPreviewShown(list) {
    const side = list.querySelector('[data-md-field-side]');
    return side !== null && !side.classList.contains('d-none');
}

/**
 * Highlight a field in the preview and bring it into view.
 *
 * @param {HTMLElement} list
 * @param {string|null} id
 */
function highlightPreview(list, id) {
    const body = list.querySelector('[data-md-preview-body]');
    if (body === null) {
        return;
    }
    body.querySelectorAll('.md-preview-selected').forEach((element) => {
        element.classList.remove('md-preview-selected');
    });
    if (!id) {
        return;
    }
    const target = body.querySelector(`[id-field="field${CSS.escape(id)}"]`);
    if (target !== null) {
        target.classList.add('md-preview-selected');
        target.scrollIntoView({block: 'nearest', behavior: 'smooth'});
    }
}

/**
 * Load the preview of a block into the preview column.
 *
 * @param {HTMLElement} list
 * @param {string} rank
 * @returns {Promise<void>}
 */
async function loadPreview(list, rank) {
    const body = list.querySelector('[data-md-preview-body]');
    const title = list.querySelector('[data-md-preview-title]');
    const block = list.querySelector(`[data-md-block="${CSS.escape(rank)}"]`);
    if (body === null || block === null) {
        return;
    }
    list.dataset.mdPreviewRank = rank;
    if (title !== null) {
        title.textContent = block.dataset.mdBlockName ?? '';
    }
    try {
        const response = await post(list.dataset.mdFieldListPreviewUrl, {
            block: rank,
            metademands_id: list.dataset.mdFieldList,
        });
        if (list.dataset.mdPreviewRank !== rank) {
            // Another block was asked for meanwhile
            return;
        }
        injectHtml(body, response.ok ? await response.text() : '');
    } catch {
        body.textContent = '';
    }
    const selected = list.querySelector('.md-field.is-selected');
    highlightPreview(list, selected !== null ? selected.dataset.mdFieldId : null);
}

/**
 * Unfold a block and scroll to it.
 *
 * @param {HTMLElement} list
 * @param {string} rank
 * @returns {HTMLElement|null} the block
 */
function revealBlock(list, rank) {
    const block = list.querySelector(`[data-md-block="${CSS.escape(rank)}"]`);
    if (block === null) {
        return null;
    }
    const body = block.querySelector('.collapse');
    if (body !== null && !body.classList.contains('show')) {
        bootstrap.Collapse.getOrCreateInstance(body, {toggle: false}).show();
    }
    return block;
}

/**
 * Bring the top of a block into view, header included: scrollIntoView() alone slides it
 * under the sticky header of GLPI (header.navbar.sticky-lg-top).
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
 * Select a field: highlighted in the list and in the preview of its block.
 *
 * @param {HTMLElement} list
 * @param {string} id
 * @param {boolean} scroll bring the row into view
 */
function selectField(list, id, scroll) {
    const row = list.querySelector(`#md-field-${CSS.escape(id)}`);
    if (row === null) {
        return;
    }
    list.querySelectorAll('.md-field.is-selected').forEach((element) => {
        element.classList.remove('is-selected');
    });
    row.classList.add('is-selected');

    const rank = row.closest('[data-md-fields]').dataset.mdRank;
    if (scroll) {
        revealBlock(list, rank);
        row.scrollIntoView({block: 'center', behavior: 'smooth'});
    }
    if (isPreviewShown(list)) {
        if (list.dataset.mdPreviewRank !== rank) {
            loadPreview(list, rank);
        } else {
            highlightPreview(list, id);
        }
    }
}

/**
 * Apply the search and the type filter of the toolbar. Drag and drop is suspended while
 * filtering: hidden fields would make the drop position ambiguous.
 *
 * @param {HTMLElement} list
 */
function applyFilter(list) {
    const search = list.querySelector('[data-md-field-search]');
    const type = list.querySelector('[data-md-field-type-filter]');
    const query = search !== null ? search.value.trim().toLowerCase() : '';
    const wanted = type !== null ? type.value : '';
    const filtering = query !== '' || wanted !== '';
    // The line of an open panel may be filtered out
    closeParameters(list);

    list.querySelectorAll('.md-field').forEach((row) => {
        const visible = (query === '' || (row.dataset.mdSearch ?? '').includes(query))
            && (wanted === '' || row.dataset.mdType === wanted);
        row.classList.toggle('d-none', !visible);
    });
    list.querySelectorAll('[data-md-block]').forEach((block) => {
        const any = block.querySelector('.md-field:not(.d-none)') !== null;
        block.classList.toggle('d-none', filtering && !any);
    });

    list.classList.toggle('md-filtering', filtering);
    if (typeof window.sortable === 'function') {
        list.querySelectorAll('[data-md-fields][data-md-sortable-ready]').forEach((container) => {
            window.sortable(container, filtering ? 'disable' : 'enable');
        });
    }
}

/**
 * Show or hide the preview column, the structure taking the room left.
 *
 * @param {HTMLElement} list
 * @param {boolean} shown
 */
function togglePreview(list, shown) {
    const side = list.querySelector('[data-md-field-side]');
    const structure = list.querySelector('[data-md-field-structure]');
    if (side === null || structure === null) {
        return;
    }
    side.classList.toggle('d-none', !shown);
    structure.classList.toggle('col-xl-6', shown);
    structure.classList.toggle('col-xl-10', !shown);
    if (shown) {
        const selected = list.querySelector('.md-field.is-selected');
        const rank = selected !== null
            ? selected.closest('[data-md-fields]').dataset.mdRank
            : (list.dataset.mdPreviewRank ?? list.querySelector('[data-md-block]')?.dataset.mdBlock);
        if (rank) {
            loadPreview(list, rank);
        }
    }
}

/**
 * Show or hide the checkboxes and the bar of the massive actions.
 *
 * @param {HTMLElement} list
 * @param {boolean} enabled
 */
function toggleSelection(list, enabled) {
    list.querySelectorAll('.md-check').forEach((element) => element.classList.toggle('d-none', !enabled));
    list.querySelectorAll('.md-grip').forEach((element) => element.classList.toggle('d-none', enabled));
    list.querySelector('[data-md-field-massive]')?.classList.toggle('d-none', !enabled);
    if (!enabled) {
        list.querySelectorAll('.md-check input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = false;
        });
    }
}

/**
 * Refresh the field counters of the blocks after a move.
 *
 * @param {HTMLElement} list
 */
function updateCounts(list) {
    list.querySelectorAll('[data-md-fields]').forEach((container) => {
        const count = String(container.querySelectorAll('.md-field').length);
        list.querySelectorAll(`[data-md-block-count="${CSS.escape(container.dataset.mdRank)}"]`).forEach((badge) => {
            badge.textContent = count;
        });
    });
}

/**
 * Store a drag and drop: the field is placed after the field preceding it in its new
 * block, or at the top of it. The server renumbers both blocks; on failure the list is
 * reloaded so that it shows what is stored.
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} item moved row
 * @param {HTMLElement} origin container it came from
 * @param {HTMLElement} destination container it was dropped into
 */
async function moveField(list, item, origin, destination) {
    let previous = item.previousElementSibling;
    while (previous !== null && !previous.matches('.md-field')) {
        previous = previous.previousElementSibling;
    }
    const rank = destination.dataset.mdRank;

    let moved = false;
    try {
        const response = await post(list.dataset.mdFieldListMoveUrl, {
            id: item.dataset.mdFieldId,
            rank: rank,
            previous_fields_id: previous !== null ? previous.dataset.mdFieldId : 0,
        });
        moved = response.ok && (await response.json()).success === true;
    } catch {
        moved = false;
    }

    if (!moved) {
        if (typeof glpi_toast_error === 'function') {
            glpi_toast_error(list.dataset.mdFieldListMoveError);
        }
        window.location.reload();
        return;
    }

    updateCounts(list);
    const shown = list.dataset.mdPreviewRank;
    if (isPreviewShown(list) && (shown === rank || shown === origin.dataset.mdRank)) {
        loadPreview(list, shown);
    }
}

/**
 * Make a field mandatory or optional from its badge.
 *
 * @param {HTMLElement} list
 * @param {HTMLButtonElement} button a [data-md-field-mandatory] badge
 */
async function toggleMandatory(list, button) {
    const wanted = button.getAttribute('aria-pressed') !== 'true';
    button.disabled = true;

    let saved = null;
    try {
        const response = await post(list.dataset.mdFieldListMandatoryUrl, {
            id: button.dataset.mdFieldMandatory,
            is_mandatory: wanted ? 1 : 0,
        });
        const result = response.ok ? await response.json() : null;
        saved = result !== null && result.success === true ? result.is_mandatory === 1 : null;
    } catch {
        saved = null;
    }
    button.disabled = false;

    if (saved === null) {
        toastError(list.dataset.mdFieldListMandatoryError);
        return;
    }

    setMandatoryBadge(button, saved);

    // The preview shows the mandatory star: refresh it when it shows this block
    const row = button.closest('.md-field');
    if (row !== null) {
        refreshPreviewOf(list, row);
    }
}

/**
 * Show a mandatory badge in its state.
 *
 * @param {HTMLElement} button a [data-md-field-mandatory] badge
 * @param {boolean} mandatory
 */
function setMandatoryBadge(button, mandatory) {
    button.setAttribute('aria-pressed', mandatory ? 'true' : 'false');
    button.classList.toggle('bg-red-lt', mandatory);
    button.classList.toggle('bg-secondary-lt', !mandatory);
    button.classList.toggle('text-secondary', !mandatory);
    const icon = button.querySelector('i');
    if (icon !== null) {
        icon.className = `ti ${mandatory ? 'ti-asterisk' : 'ti-circle-dashed'} me-1`;
    }
    const label = button.querySelector('span');
    if (label !== null) {
        label.textContent = (mandatory ? button.dataset.mdLabelOn : button.dataset.mdLabelOff) ?? '';
    }
}

/**
 * Show a toast for an error, through the core helper when it is there.
 *
 * @param {string|undefined} message
 */
function toastError(message) {
    if (typeof glpi_toast_error === 'function' && message) {
        glpi_toast_error(message);
    }
}

/**
 * Refresh the preview when it shows the block of a line.
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} row a .md-field line
 */
function refreshPreviewOf(list, row) {
    const rank = row.closest('[data-md-fields]')?.dataset.mdRank;
    if (rank !== undefined && isPreviewShown(list) && list.dataset.mdPreviewRank === rank) {
        loadPreview(list, rank);
    }
}

/**
 * Close the parameter panels of the list (one is open at a time).
 *
 * @param {HTMLElement} list
 */
function closeParameters(list) {
    list.querySelectorAll('[data-md-field-panel]').forEach((panel) => {
        const row = list.querySelector(`.md-field[data-md-field-id="${CSS.escape(panel.dataset.mdFieldPanel)}"]`);
        row?.classList.remove('is-editing');
        row?.querySelector('[data-md-field-parameters]')?.setAttribute('aria-expanded', 'false');
        panel.remove();
    });
}

/**
 * Load the parameter form of a field into its panel.
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} panel
 */
async function loadParameters(list, panel) {
    const body = panel.querySelector('.md-field-panel-body');
    if (body === null) {
        return;
    }
    try {
        const response = await post(list.dataset.mdFieldListParametersUrl, {id: panel.dataset.mdFieldPanel});
        injectForm(body, response.ok ? await response.text() : '');
    } catch {
        body.replaceChildren();
    }
}

/**
 * Open (or close) the panel of the parameters of a field, right under its line. The panel is
 * a sibling of the line, not a child: the lines are the items of the drag and drop.
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} button a [data-md-field-parameters] button
 */
async function toggleParameters(list, button) {
    const row = button.closest('.md-field');
    if (row === null) {
        return;
    }
    const was_open = row.nextElementSibling?.matches('[data-md-field-panel]') === true;
    closeParameters(list);
    if (was_open) {
        return;
    }

    const panel = document.createElement('div');
    panel.className = 'md-field-panel';
    panel.dataset.mdFieldPanel = row.dataset.mdFieldId;

    const header = document.createElement('div');
    header.className = 'md-field-panel-header';
    const title = document.createElement('span');
    title.className = 'md-field-panel-title';
    const icon = document.createElement('i');
    icon.className = 'ti ti-adjustments-horizontal';
    const label = row.querySelector('[data-md-field-label], .md-title');
    title.append(icon, ` ${list.dataset.mdLabelParameters ?? ''} — ${label?.textContent.trim() ?? ''}`);
    const full = document.createElement('a');
    full.className = 'btn btn-sm btn-ghost-secondary ms-auto';
    full.href = label?.getAttribute('href') ?? '#';
    full.textContent = list.dataset.mdLabelFullForm ?? '';
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.dataset.mdFieldPanelClose = '';
    close.setAttribute('aria-label', list.dataset.mdLabelClose ?? '');
    close.title = list.dataset.mdLabelClose ?? '';
    header.append(title, full, close);

    const body = document.createElement('div');
    body.className = 'md-field-panel-body';
    const loading = document.createElement('div');
    loading.className = 'text-secondary small';
    loading.textContent = list.dataset.mdLabelLoading ?? '';
    body.append(loading);

    panel.append(header, body);
    // Its form is saved through Ajax, the panel stays open
    panel.addEventListener('submit', (event) => saveParameters(list, panel, event));
    row.after(panel);
    row.classList.add('is-editing');
    button.setAttribute('aria-expanded', 'true');

    await loadParameters(list, panel);
}

/**
 * Save the parameter form of a panel through Ajax, as its "Parameters" tab posts it to
 * front/fieldparameter.form.php, then reload the panel, the mandatory badge and the preview.
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} panel
 * @param {SubmitEvent} event
 */
async function saveParameters(list, panel, event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    event.preventDefault();
    window.tinymce?.triggerSave();

    let data;
    try {
        data = new FormData(form, event.submitter ?? undefined);
    } catch {
        data = new FormData(form);
    }
    // Enter in a text input submits without a button: the action is the one of the form
    if (!data.has('update') && !data.has('add')) {
        data.append(form.querySelector('[name="add"]') !== null ? 'add' : 'update', '1');
    }

    const buttons = form.querySelectorAll('button[type="submit"]');
    buttons.forEach((button) => { button.disabled = true; });
    let saved = false;
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: data,
            // The front controller answers with a redirection back to the page: not followed
            redirect: 'manual',
        });
        saved = response.type === 'opaqueredirect' || response.ok;
    } catch {
        saved = false;
    }
    buttons.forEach((button) => { button.disabled = false; });

    if (!saved) {
        toastError(list.dataset.mdFieldListParametersError);
        return;
    }
    // The "Item successfully updated" of the core, kept in session by the save
    if (typeof window.displayAjaxMessageAfterRedirect === 'function') {
        window.displayAjaxMessageAfterRedirect();
    }

    const row = list.querySelector(`.md-field[data-md-field-id="${CSS.escape(panel.dataset.mdFieldPanel)}"]`);
    const badge = row?.querySelector('[data-md-field-mandatory]');
    if (badge instanceof HTMLElement && data.has('is_mandatory')) {
        setMandatoryBadge(badge, data.get('is_mandatory') === '1');
    }
    if (row !== null) {
        refreshPreviewOf(list, row);
    }
    // The form shown again from the database: new parameters, values normalized
    await loadParameters(list, panel);
}

/**
 * Turn the label of a field into an input, saved on Enter or when leaving it, cancelled on
 * Escape.
 *
 * @param {HTMLElement} list
 * @param {HTMLElement} row a .md-field line
 */
function startRename(list, row) {
    const label = row.querySelector('[data-md-field-label]');
    if (!(label instanceof HTMLElement) || row.querySelector('.md-rename-input') !== null) {
        return;
    }
    const original = label.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm md-rename-input';
    input.value = original;
    input.setAttribute('aria-label', row.querySelector('[data-md-field-rename]')?.getAttribute('aria-label') ?? '');
    label.hidden = true;
    label.after(input);
    // A draggable line keeps the mouse from placing the caret in the input (Firefox)
    const draggable = row.getAttribute('draggable');
    row.setAttribute('draggable', 'false');
    input.focus();
    input.select();

    let done = false;
    const finish = (name) => {
        if (done) {
            return;
        }
        done = true;
        input.remove();
        label.hidden = false;
        if (draggable === null) {
            row.removeAttribute('draggable');
        } else {
            row.setAttribute('draggable', draggable);
        }
        if (name !== null) {
            label.textContent = name;
            row.dataset.mdSearch = row.dataset.mdSearch.replace(original.toLowerCase(), name.toLowerCase());
            const title = list.querySelector(`[data-md-field-panel="${CSS.escape(row.dataset.mdFieldId)}"] .md-field-panel-title`);
            if (title !== null) {
                title.lastChild.textContent = title.lastChild.textContent.replace(original, name);
            }
        }
    };
    const commit = async () => {
        const name = input.value.trim();
        if (done) {
            return;
        }
        if (name === '' || name === original) {
            finish(null);
            return;
        }
        input.disabled = true;
        let result = null;
        try {
            const response = await post(list.dataset.mdFieldListRenameUrl, {id: row.dataset.mdFieldId, name});
            result = response.ok ? await response.json() : null;
        } catch {
            result = null;
        }
        if (result === null || result.success !== true) {
            toastError(list.dataset.mdFieldListRenameError);
            finish(null);
            return;
        }
        finish(result.name);
        refreshPreviewOf(list, row);
    };

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            // Not the massive actions form the line sits in
            event.preventDefault();
            commit();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            finish(null);
        }
    });
    input.addEventListener('blur', () => commit());
}

/**
 * Drag and drop with the html5sortable library of the core (window.sortable): every
 * block accepts the fields of the others.
 *
 * @param {HTMLElement} list
 */
function bindSortable(list) {
    if (typeof window.sortable !== 'function' || list.querySelector('.md-grip') === null) {
        return;
    }
    const containers = list.querySelectorAll('[data-md-fields]');
    window.sortable(containers, {
        items: '.md-field',
        handle: '.md-grip',
        acceptFrom: '.md-fields',
        forcePlaceholderSize: true,
        placeholderClass: 'md-field-placeholder',
    });
    containers.forEach((container) => {
        container.dataset.mdSortableReady = '1';
        // A panel stays a sibling of its line: closed before the line moves away from it
        container.addEventListener('sortstart', () => closeParameters(list));
        container.addEventListener('sortupdate', (event) => {
            const detail = event.detail;
            // Reported to both lists on a move between blocks: stored once, by the one it landed in
            if (detail.destination.container !== container) {
                return;
            }
            moveField(list, detail.item, detail.origin.container, container);
        });
    });
}

/**
 * Load a field form into its modal, then show the modal. A block button prefills the
 * block of the new field.
 *
 * @param {HTMLElement} button a [data-md-field-add] button
 */
async function openAddModal(button) {
    const modal = document.getElementById(button.dataset.mdFieldAdd);
    if (modal === null) {
        return;
    }
    const body = modal.querySelector('.modal-body');
    try {
        const response = await post(button.dataset.mdFieldAddUrl, JSON.parse(button.dataset.mdFieldAddParams));
        injectHtml(body, response.ok ? await response.text() : '');
    } catch {
        return;
    }

    const rank = button.dataset.mdFieldAddRank;
    const select = rank ? body.querySelector('select[name="rank"]') : null;
    if (select !== null) {
        // Selected once the modal is shown: the select2 of the block and the reload of the
        // "display field after" list are only set up by the scripts of the form by then.
        modal.addEventListener('shown.bs.modal', () => {
            // Dropdown::showNumber() loads its choices through Ajax: only the current value
            // exists as an option, the block has to be added before it can be selected
            if (!Array.from(select.options).some((option) => option.value === rank)) {
                select.add(new Option(rank, rank));
            }
            select.value = rank;
            select.dispatchEvent(new Event('change', {bubbles: true}));

            // The block was chosen by the button: read only. Not disabled, a disabled select
            // would not be posted and the field would be created without its block; the
            // select2 that stands for it on screen is made inert instead.
            const widget = select.nextElementSibling;
            if (widget !== null && widget.classList.contains('select2')) {
                widget.classList.add('pe-none', 'opacity-75');
                widget.querySelectorAll('[tabindex]').forEach((element) => element.setAttribute('tabindex', '-1'));
                widget.setAttribute('aria-readonly', 'true');
            }
        }, {once: true});
    }
    bootstrap.Modal.getOrCreateInstance(modal).show();
}

/**
 * @param {HTMLElement} list
 */
function initList(list) {
    if (list.dataset.mdFieldListInit) {
        return;
    }
    list.dataset.mdFieldListInit = '1';

    bindSortable(list);

    list.querySelector('[data-md-field-search]')?.addEventListener('input', () => applyFilter(list));
    list.querySelector('[data-md-field-type-filter]')?.addEventListener('change', () => applyFilter(list));
    list.querySelector('[data-md-field-toggle-select]')?.addEventListener('change', (event) => {
        toggleSelection(list, event.target.checked);
    });

    const previewToggle = list.querySelector('[data-md-field-toggle-preview]');
    if (previewToggle !== null) {
        // Off when the setup of the metademand is opened: the preview loads the whole form,
        // shown on demand only
        previewToggle.checked = false;
        previewToggle.addEventListener('change', () => togglePreview(list, previewToggle.checked));
        togglePreview(list, previewToggle.checked);
    }

    // Reopen the "new field" modal when front/field.form.php redirected here after "add another"
    const params = new URLSearchParams(window.location.search);
    if (params.get('open_add_field') === list.dataset.mdFieldList) {
        params.delete('open_add_field');
        const query = params.toString();
        history.replaceState(null, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash);
        const button = list.querySelector('[data-md-field-add-new]');
        if (button !== null) {
            openAddModal(button);
        }
    }
}

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }
    const list = target.closest('[data-md-field-list]');
    if (list === null) {
        return;
    }

    const parameters = target.closest('[data-md-field-parameters]');
    if (parameters !== null) {
        event.preventDefault();
        toggleParameters(list, parameters);
        return;
    }

    if (target.closest('[data-md-field-panel-close]') !== null) {
        event.preventDefault();
        closeParameters(list);
        return;
    }

    const rename = target.closest('[data-md-field-rename]');
    if (rename !== null) {
        event.preventDefault();
        const row = rename.closest('.md-field');
        if (row !== null) {
            startRename(list, row);
        }
        return;
    }

    const mandatory = target.closest('[data-md-field-mandatory]');
    if (mandatory !== null) {
        event.preventDefault();
        if (!mandatory.disabled) {
            toggleMandatory(list, mandatory);
        }
        return;
    }

    const add = target.closest('[data-md-field-add]');
    if (add !== null) {
        event.preventDefault();
        openAddModal(add);
        return;
    }

    const purge = target.closest('button[data-md-field-purge]');
    if (purge !== null) {
        event.preventDefault();
        const message = document.createElement('div');
        message.textContent = purge.dataset.mdFieldPurgeConfirm ?? '';
        glpi_confirm({
            message: message.innerHTML,
            confirm_callback: () => purge.form.requestSubmit(purge),
        });
        return;
    }

    const goto = target.closest('[data-md-field-goto]');
    if (goto !== null) {
        event.preventDefault();
        selectField(list, goto.dataset.mdFieldGoto, true);
        return;
    }

    const blockGoto = target.closest('[data-md-block-goto]');
    if (blockGoto !== null) {
        event.preventDefault();
        const block = revealBlock(list, blockGoto.dataset.mdBlockGoto);
        if (block !== null) {
            goToBlock(block);
        }
        if (isPreviewShown(list)) {
            loadPreview(list, blockGoto.dataset.mdBlockGoto);
        }
        return;
    }

    const blockPreview = target.closest('[data-md-block-preview]');
    if (blockPreview !== null) {
        event.preventDefault();
        if (!isPreviewShown(list)) {
            const toggle = list.querySelector('[data-md-field-toggle-preview]');
            if (toggle !== null) {
                toggle.checked = true;
            }
            togglePreview(list, true);
        }
        loadPreview(list, blockPreview.dataset.mdBlockPreview);
        return;
    }

    // A field of the preview selects its row
    const previewed = target.closest('[data-md-preview-body] [id-field]');
    if (previewed !== null) {
        const id = (previewed.getAttribute('id-field') ?? '').replace(/^field/, '').replace(/-2$/, '');
        if (id !== '') {
            selectField(list, id, true);
        }
        return;
    }

    // A click on a row selects it, unless it hits one of its controls
    const row = target.closest('.md-field');
    if (row !== null && target.closest('a, button, input, label, .md-grip, .md-check') === null) {
        selectField(list, row.dataset.mdFieldId, false);
    }
});

/**
 * @param {ParentNode} root
 */
function scan(root) {
    if (root instanceof Element && root.matches('[data-md-field-list]')) {
        initList(root);
    }
    root.querySelectorAll('[data-md-field-list]').forEach(initList);
}

scan(document);
new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node instanceof Element) {
                scan(node);
            }
        });
    });
}).observe(document.body, {childList: true, subtree: true});
