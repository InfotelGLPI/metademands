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
 * Row reordering for the metademands configuration screens.
 *
 * Replaces the former RedIPS.drag integration with GLPI core's html5sortable
 * (window.sortable, already loaded on every page). The markup stays a plain
 * table: each sortable <tbody> carries data-md-url (the reorder endpoint) and an
 * optional data-md-params JSON of container-level parameters; each draggable <tr>
 * carries data-md-order with its real stored order/rank value.
 *
 * On drop we POST old_order/new_order derived from those real order values (not
 * from the 0-based DOM index), which keeps the payload correct whatever the base
 * of the stored sequence (the field-ordering table is 1-based and has a header
 * row, the custom-value tables are contiguous from their own first rank). The PHP
 * reorder() handlers are therefore left unchanged.
 */

"use strict";

const PLUGIN_METADEMANDS_SORTABLE_SELECTOR = "[data-md-sortable]";

function plugin_metademands_parseParams(element, attribute) {
    const raw = element ? element.getAttribute(attribute) : null;
    if (!raw) {
        return {};
    }
    try {
        return JSON.parse(raw);
    } catch (error) {
        return {};
    }
}

function plugin_metademands_csrfToken() {
    // GLPI exposes the current CSRF token through this meta tag; core's jQuery
    // ajaxSend hook reads it the same way (see js/common.js getAjaxCsrfToken).
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    return meta !== null ? meta.getAttribute("content") : null;
}

function plugin_metademands_sendOrder(container, detail) {
    const url = container.getAttribute("data-md-url");
    if (!url || !detail || !detail.origin || !detail.destination) {
        return;
    }

    const item = detail.item;
    // old_order is the dragged row's own stored value, still on the element.
    const old_order = item.getAttribute("data-md-order");
    if (old_order === null) {
        return;
    }

    // new_order is the stored value of the slot the row landed on. html5sortable
    // has already moved the DOM, and the data-md-order attributes of the shifted
    // rows are still their pre-move values, so the neighbour the row jumped onto
    // carries exactly the target value RedIPS used to send (its getPosition row).
    let neighbour = null;
    if (detail.destination.elementIndex > detail.origin.elementIndex) {
        // Moved down: it now sits right after the last row it passed.
        neighbour = item.previousElementSibling;
    } else {
        // Moved up: it now sits right before the row that occupied the slot.
        neighbour = item.nextElementSibling;
    }
    if (!neighbour || neighbour.getAttribute("data-md-order") === null) {
        return;
    }
    const new_order = neighbour.getAttribute("data-md-order");

    const body = new URLSearchParams();
    body.set("old_order", old_order);
    body.set("new_order", new_order);

    // Container-level parameters (field id, type, rank, metademand id) forwarded
    // as-is; they replace the hidden inputs / hash lookups RedIPS relied on.
    const params = plugin_metademands_parseParams(container, "data-md-params");
    Object.keys(params).forEach((key) => body.set(key, params[key]));

    // GLPI 11's CheckCsrfListener requires a CSRF token on every POST.
    // X-Requested-With routes the request through the AJAX branch, which reads the
    // token from the X-Glpi-Csrf-Token header with preserve_token:true (so
    // successive reorders stay valid); credentials carry the session cookie. This
    // mirrors the former jQuery.ajax transport (core's ajaxSend hook set the same
    // header) — without it the endpoint answers 403.
    const headers = {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
    };
    const csrf_token = plugin_metademands_csrfToken();
    if (csrf_token) {
        headers["X-Glpi-Csrf-Token"] = csrf_token;
    }
    fetch(url, {
        method: "POST",
        credentials: "same-origin",
        headers: headers,
        body: body.toString(),
    }).catch(() => {
        // Mirror the previous RedIPS handler, which swallowed AJAX failures silently.
    });
}

function plugin_metademands_bindSortable(container) {
    if (typeof window.sortable !== "function") {
        return;
    }
    if (container.dataset.mdSortableReady === "1") {
        return;
    }
    container.dataset.mdSortableReady = "1";

    window.sortable(container, {
        items: "tr",
        handle: ".md-sort-handle",
        forcePlaceholderSize: true,
        placeholderClass: "tab_bg_2",
    });
    container.addEventListener("sortupdate", (event) => {
        plugin_metademands_sendOrder(container, event.detail);
    });
}

function plugin_metademands_scan(root) {
    const scope = root && root.querySelectorAll ? root : document;
    scope
        .querySelectorAll(PLUGIN_METADEMANDS_SORTABLE_SELECTOR)
        .forEach(plugin_metademands_bindSortable);

    // A container may itself be the single node injected by an AJAX response.
    if (root && root.matches && root.matches(PLUGIN_METADEMANDS_SORTABLE_SELECTOR)) {
        plugin_metademands_bindSortable(root);
    }
}

function plugin_metademands_observe() {
    // The field blocks (tabs) and the custom-value panels are injected via AJAX
    // after the initial load, so a one-shot scan is not enough: watch the DOM and
    // bind any sortable container as soon as it appears.
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (node.nodeType === 1) {
                    plugin_metademands_scan(node);
                }
            }
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
}

function plugin_metademands_startSortable() {
    plugin_metademands_scan(document);
    plugin_metademands_observe();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", plugin_metademands_startSortable);
} else {
    plugin_metademands_startSortable();
}
