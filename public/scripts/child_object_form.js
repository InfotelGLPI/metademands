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
 * Child object of a metademand (templates/task_form.html.twig): the object to create is chosen
 * among cards, then its settings are shown beside the tags of the metademand
 * (templates/forms/child_tags_panel.html.twig). A card sets the "taskType" dropdown, kept
 * hidden, whose change reloads the settings (Ajax::updateItemOnSelectEvent(), jQuery handler
 * that native events reach).
 *
 * A click on a tag inserts it in the title or in the description, the last one used; the tags
 * the created objects would not resolve are listed under the settings.
 *
 * Loaded as a module (setup.php, ADD_JAVASCRIPT_MODULE); the modal is loaded after the page,
 * hence the delegated listeners.
 */

/** A tag, as Task::getUnknownTags() reads it */
const TAG_PATTERN = /#([A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*)#/g;

/** Text the tags go into, by modal: 'name' (title) or 'content' (description) */
const TARGETS = new WeakMap();

/**
 * @param {string} text
 * @returns {string} lower case, without accents
 */
function normalize(text) {
    return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

/**
 * @param {Element} child
 * @returns {?HTMLTextAreaElement}
 */
function contentTextarea(child) {
    const textarea = child.querySelector('textarea[name="content"]');
    return textarea instanceof HTMLTextAreaElement ? textarea : null;
}

/**
 * @param {Element} child
 * @returns {?Object} the TinyMCE editor of the description
 */
function contentEditor(child) {
    const textarea = contentTextarea(child);
    return textarea !== null && textarea.id && window.tinymce ? window.tinymce.get(textarea.id) : null;
}

/**
 * @param {Element} child
 * @param {'name'|'content'} target
 */
function setTarget(child, target) {
    TARGETS.set(child, target);
    const panel = child.querySelector('[data-md-child-tags-panel]');
    const into = child.querySelector('[data-md-tag-into]');
    if (panel instanceof HTMLElement && into !== null) {
        into.textContent = target === 'name' ? panel.dataset.mdLabelName : panel.dataset.mdLabelContent;
    }
    child.querySelectorAll('[data-md-tag-target]').forEach((element) => {
        element.classList.toggle('md-child-target', element.dataset.mdTagTarget === target);
    });
}

/**
 * List the tags of the title and the description the created objects would not resolve
 *
 * @param {Element} child
 */
function checkUnknown(child) {
    const panel = child.querySelector('[data-md-child-tags-panel]');
    const box = child.querySelector('[data-md-tag-unknown]');
    if (!(panel instanceof HTMLElement) || !(box instanceof HTMLElement)) {
        return;
    }
    let known;
    try {
        known = new Set(JSON.parse(panel.dataset.mdKnownTags || '[]').map(String));
    } catch {
        return;
    }
    const editor = contentEditor(child);
    const content = editor !== null
        ? editor.getContent({format: 'text'})
        : (contentTextarea(child)?.value ?? '').replace(/<[^>]*>/g, ' ');
    const text = (child.querySelector('input[name="name"]')?.value ?? '') + ' ' + content;

    const unknown = new Set();
    for (const match of text.matchAll(TAG_PATTERN)) {
        if (!known.has(match[1])) {
            unknown.add(`#${match[1]}#`);
        }
    }
    box.hidden = unknown.size === 0;
    const list = box.querySelector('[data-md-tag-unknown-list]');
    if (list !== null) {
        list.textContent = [...unknown].join(', ');
    }
}

/**
 * Follow the description editor, initialised after the settings are loaded
 *
 * @param {Element} child
 * @param {number} attempts
 */
function bindEditor(child, attempts = 20) {
    const editor = contentEditor(child);
    if (editor === null) {
        if (attempts > 0 && contentTextarea(child) !== null) {
            setTimeout(() => bindEditor(child, attempts - 1), 250);
        }
        return;
    }
    if (editor.mdTagsBound) {
        return;
    }
    editor.mdTagsBound = true;
    editor.on('focus', () => setTarget(child, 'content'));
    editor.on('input change keyup SetContent', () => checkUnknown(child));
    checkUnknown(child);
}

/**
 * Set the modal up, once: the settings reloaded when the object changes are followed too
 *
 * @param {Element} child
 */
function initChild(child) {
    setTarget(child, TARGETS.get(child) ?? 'content');
    bindEditor(child);
    checkUnknown(child);
    if (child.mdChildObserved) {
        return;
    }
    child.mdChildObserved = true;
    const settings = child.querySelector('#show_add_task_form');
    if (settings !== null) {
        let pending = null;
        new MutationObserver(() => {
            clearTimeout(pending);
            pending = setTimeout(() => initChild(child), 50);
        }).observe(settings, {childList: true});
    }
}

/**
 * @param {Element} child
 * @param {1|2} step 1: the object, 2: its settings
 */
function showStep(child, step) {
    child.querySelector('[data-md-child-picker]')?.toggleAttribute('hidden', step !== 1);
    child.querySelector('[data-md-child-settings]')?.toggleAttribute('hidden', step !== 2);
    child.classList.toggle('md-child-picking', step === 1);
}

/**
 * @param {Element} child
 * @param {HTMLElement} card
 */
function chooseObject(child, card) {
    const type = card.dataset.mdChildCard ?? '';
    const select = child.querySelector('select[name="taskType"]');
    if (select instanceof HTMLSelectElement && select.value !== type) {
        select.value = type;
        select.dispatchEvent(new Event('change', {bubbles: true}));
    }

    const icon = child.querySelector('[data-md-child-chosen-icon]');
    if (icon !== null) {
        icon.className = 'md-type-icon md-child-family-icon';
        icon.innerHTML = '';
        const glyph = document.createElement('i');
        glyph.className = 'ti ' + (card.dataset.mdChildIcon ?? '');
        icon.append(glyph);
        const source = card.querySelector('.md-type-icon');
        if (source !== null) {
            icon.style.background = getComputedStyle(source).backgroundColor;
            icon.style.color = getComputedStyle(source).color;
        }
    }
    const name = child.querySelector('[data-md-child-chosen-name]');
    if (name !== null) {
        name.textContent = card.dataset.mdChildName ?? '';
    }
    child.querySelector('[data-md-child-layout]')
        ?.classList.toggle('md-child-no-tags', card.dataset.mdChildTags !== '1');

    showStep(child, 2);
    initChild(child);
}

/**
 * @param {Element} child
 * @param {string} tag
 */
function insertTag(child, tag) {
    if ((TARGETS.get(child) ?? 'content') === 'name') {
        const input = child.querySelector('input[name="name"]');
        if (input instanceof HTMLInputElement) {
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? start;
            input.value = input.value.slice(0, start) + tag + input.value.slice(end);
            input.focus();
            input.setSelectionRange(start + tag.length, start + tag.length);
        }
    } else {
        const editor = contentEditor(child);
        if (editor !== null) {
            editor.focus();
            editor.insertContent(tag);
        } else {
            const textarea = contentTextarea(child);
            if (textarea !== null) {
                textarea.value += tag;
            }
        }
    }
    checkUnknown(child);
}

/**
 * Copy a tag: the clipboard API needs a secure context, the selection does not
 *
 * @param {HTMLElement} button
 * @param {string} tag
 */
async function copyTag(button, tag) {
    let copied = false;
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(tag);
            copied = true;
        } catch {
            copied = false;
        }
    }
    if (!copied) {
        // Inside the panel: the focus of the modal stays in it
        const textarea = document.createElement('textarea');
        textarea.value = tag;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        button.after(textarea);
        textarea.select();
        try {
            copied = document.execCommand('copy');
        } catch {
            copied = false;
        }
        textarea.remove();
    }
    if (copied) {
        const glyph = button.querySelector('i');
        glyph?.classList.replace('ti-copy', 'ti-check');
        setTimeout(() => glyph?.classList.replace('ti-check', 'ti-copy'), 1200);
    }
}

/**
 * @param {Element} panel
 * @param {string} query
 */
function filterTags(panel, query) {
    const wanted = normalize(query.trim());
    let shown = 0;
    panel.querySelectorAll('[data-md-tag-group]').forEach((group) => {
        let count = 0;
        group.querySelectorAll('[data-md-tag]').forEach((row) => {
            const hit = wanted === '' || normalize(row.textContent).includes(wanted);
            row.toggleAttribute('hidden', !hit);
            count += hit ? 1 : 0;
        });
        group.toggleAttribute('hidden', count === 0);
        shown += count;
    });
    panel.querySelector('[data-md-tag-none]')?.toggleAttribute('hidden', shown > 0);
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }
    const child = event.target.closest('[data-md-child]');
    if (child === null) {
        return;
    }

    const card = event.target.closest('[data-md-child-card]');
    if (card instanceof HTMLButtonElement) {
        event.preventDefault();
        if (!card.disabled) {
            chooseObject(child, card);
        }
        return;
    }
    if (event.target.closest('[data-md-child-change]') !== null) {
        event.preventDefault();
        showStep(child, 1);
        return;
    }
    const copy = event.target.closest('[data-md-tag-copy]');
    const row = event.target.closest('[data-md-tag]');
    if (copy instanceof HTMLElement && row instanceof HTMLElement) {
        event.preventDefault();
        copyTag(copy, row.dataset.mdTag ?? '');
        return;
    }
    if (row instanceof HTMLElement) {
        event.preventDefault();
        insertTag(child, row.dataset.mdTag ?? '');
    }
});

// Enter or space on a tag: inserted as on a click
document.addEventListener('keydown', (event) => {
    if ((event.key !== 'Enter' && event.key !== ' ') || !(event.target instanceof HTMLElement)
        || !event.target.matches('[data-md-tag]')) {
        return;
    }
    const child = event.target.closest('[data-md-child]');
    if (child !== null) {
        event.preventDefault();
        insertTag(child, event.target.dataset.mdTag ?? '');
    }
});

document.addEventListener('input', (event) => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    if (event.target.matches('[data-md-tag-search]')) {
        const panel = event.target.closest('[data-md-child-tags-panel]');
        if (panel !== null) {
            filterTags(panel, event.target.value);
        }
        return;
    }
    const child = event.target.closest('[data-md-child]');
    if (child !== null && event.target.name === 'name') {
        checkUnknown(child);
    }
});

// The title takes the tags once used
document.addEventListener('focusin', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.name === 'name') {
        const child = event.target.closest('[data-md-child]');
        if (child !== null) {
            setTarget(child, 'name');
        }
    }
});

// The description editor is an iframe: the page loses the focus when it gets it
window.addEventListener('blur', () => {
    const active = document.activeElement;
    if (active instanceof HTMLIFrameElement) {
        const child = active.closest('[data-md-child]');
        if (child !== null) {
            setTarget(child, 'content');
        }
    }
});

document.addEventListener('shown.bs.modal', (event) => {
    if (event.target instanceof Element) {
        event.target.querySelectorAll('[data-md-child]').forEach((child) => initChild(child));
    }
});
