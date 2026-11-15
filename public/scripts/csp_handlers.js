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
 * Click behaviours that the markup of the plugin used to carry in inline event handlers,
 * which the Content Security Policy of GLPI blocks. The markup now only holds data
 * attributes; the listeners are delegated on the document, so they also cover the content
 * loaded later (tabs, modals, lists refreshed by AJAX), and are bound once, with the module.
 *
 *  - data-metademands-confirm="<message>": the click is cancelled unless the user confirms
 *    the message (was onclick="return confirm('<message>')");
 *  - data-metademands-action="<function>" + data-metademands-args='<JSON array>': the click
 *    calls the global function, defined by the template, with these arguments (was
 *    onclick="<function>(<arguments>)"); only the functions of ACTIONS can be called;
 *  - data-metademands-submit-get-link="<url>" + data-metademands-submit-get-link-fields=
 *    '<JSON object>': the click posts the fields to the URL with submitGetLink() of the core
 *    (was onclick="submitGetLink(<url>, <fields>)");
 *  - data-metademands-print: the click prints the page instead of following the link (was
 *    onclick="window.print();return false;").
 *
 * The core data-glpi-* attributes are not used: they only exist from the GLPI releases
 * shipping the CSP, and a confirmation would be silently skipped on the older ones.
 */

/**
 * Global functions that data-metademands-action may call, defined by these templates.
 */
const ACTIONS = new Set([
    // templates/forms/drafts_list.html.twig, drafts_rows.html.twig
    'deleteDraft',
    'loadDraft',
    // templates/forms/draft_modal.html.twig
    'saveMyDraft',
    // templates/forms/draft_save_button.html.twig
    'load_draft_modal',
    // templates/forms/private_models_list.html.twig, private_models_rows.html.twig
    'changeVisibility',
    'deleteForm',
    // the same templates, public_models_list.html.twig, public_models_tab.html.twig,
    // user_forms_list.html.twig, user_forms_tab.html.twig and itilobject_forms_tab.html.twig
    'loadForm',
]);

// Capture phase: the confirmation is asked before any other listener of the element runs,
// as the inline handler, set first, was.
document.addEventListener('click', (event) => {
    const element = event.target.closest('[data-metademands-confirm]');
    if (element !== null && !window.confirm(element.dataset.metademandsConfirm)) {
        event.preventDefault();
    }
}, true);

document.addEventListener('click', (event) => {
    const action = event.target.closest('[data-metademands-action]');
    if (action !== null) {
        const name = action.dataset.metademandsAction;
        if (ACTIONS.has(name) && typeof window[name] === 'function') {
            window[name](...JSON.parse(action.dataset.metademandsArgs ?? '[]'));
        }
        return;
    }

    const link = event.target.closest('[data-metademands-submit-get-link]');
    if (link !== null) {
        window.submitGetLink(
            link.dataset.metademandsSubmitGetLink,
            JSON.parse(link.dataset.metademandsSubmitGetLinkFields ?? '{}'),
        );
        return;
    }

    if (event.target.closest('[data-metademands-print]') !== null) {
        window.print();
        event.preventDefault();
    }
});
