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
 * Display order of the references of a basket field, in the options of the field
 * (templates/fields/field_basket_order.html.twig).
 *
 * The rows are reordered with html5sortable (window.sortable, loaded by the core) and each
 * drop posts the whole list, in its new order, to ajax/basketobject_order.php, which numbers
 * the references from 1. The options tab is injected after load: the lists are bound as
 * they appear.
 *
 * Loaded as a module (setup.php, ADD_JAVASCRIPT_MODULE).
 */

const SELECTOR = '[data-md-basket-order]';

/**
 * Number the rows as they are now displayed.
 *
 * @param {HTMLElement} list
 */
const renumber = (list) => {
    list.querySelectorAll(':scope > tr[data-md-id] [data-md-position]').forEach((badge, index) => {
        badge.textContent = String(index + 1);
    });
};

/**
 * @param {HTMLElement} list
 */
const save = async (list) => {
    const body = new URLSearchParams();
    body.set('types_id', list.dataset.mdTypesId ?? '0');
    list.querySelectorAll(':scope > tr[data-md-id]').forEach((row) => {
        body.append('basketobjects_id[]', row.dataset.mdId ?? '0');
    });

    let success = false;
    try {
        // GLPI 11 checks the CSRF token of an AJAX POST in the X-Glpi-Csrf-Token header
        // (the core ajaxSend hook only covers jQuery)
        const token = document.querySelector('meta[property="glpi:csrf_token"]')?.getAttribute('content');
        const response = await fetch(list.dataset.mdUrl ?? '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? {'X-Glpi-Csrf-Token': token} : {}),
            },
            body: body.toString(),
        });
        success = response.ok && (await response.json()).success === true;
    } catch {
        success = false;
    }

    // Toasts of the core (js/glpi_dialog.js), global to the page
    if (success && typeof glpi_toast_info === 'function') {
        glpi_toast_info(list.dataset.mdSaved ?? '');
    } else if (!success && typeof glpi_toast_error === 'function') {
        glpi_toast_error(list.dataset.mdFailed ?? '');
    }
};

/**
 * @param {HTMLElement} list
 */
const bind = (list) => {
    if (typeof window.sortable !== 'function' || list.dataset.mdBasketOrderReady === '1') {
        return;
    }
    list.dataset.mdBasketOrderReady = '1';

    window.sortable(list, {
        items: 'tr',
        handle: '.md-basket-order-handle',
        forcePlaceholderSize: true,
        placeholderClass: 'tab_bg_2',
    });
    list.addEventListener('sortupdate', () => {
        renumber(list);
        save(list);
    });
};

/**
 * @param {ParentNode & Node} root
 */
const scan = (root) => {
    if (root instanceof Element && root.matches(SELECTOR)) {
        bind(root);
    }
    root.querySelectorAll(SELECTOR).forEach(bind);
};

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
