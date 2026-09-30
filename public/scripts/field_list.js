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
 * Field list of a metademand (templates/field_list.html.twig), previously driven by
 * the inline <script> blocks Field::listFields() generated.
 *
 * The list wrapper carries data-md-field-list (metademand id),
 * data-md-field-list-block (block filtered by the search form, 0 for none) and
 * data-md-field-list-preview-url. The horizontal scroll of the tab bar is handled by
 * wizard_form.js, which is loaded on every page.
 */

/* global updateActiveTab, glpi_confirm */

(function () {
    'use strict';

    /**
     * Load the preview of a block below the list.
     *
     * @param {HTMLElement} list
     * @param {number} rank
     */
    function loadPreview(list, rank) {
        $.ajax({
            url: list.dataset.mdFieldListPreviewUrl,
            type: 'POST',
            datatype: 'HTML',
            data: {block: rank, metademands_id: list.dataset.mdFieldList},
            success: function (response) {
                $('#see_block_preview').html(response);
            },
            error: function (xhr, status, error) {
                console.log(xhr, status, error);
            },
        });
    }

    /**
     * Show a block tab, remember it and preview it.
     *
     * @param {HTMLElement} list
     * @param {number} rank
     */
    function showBlock(list, rank) {
        updateActiveTab(rank);
        sessionStorage.setItem('loadedblock', 'block' + rank);
        window.location.hash = 'block' + rank;
        loadPreview(list, rank);
    }

    /**
     * Load a field form into its modal body, then show the modal.
     * jQuery load() posts the parameters and runs the scripts of the returned form.
     *
     * @param {HTMLElement} button a [data-md-field-add] button
     */
    function openAddModal(button) {
        const modal = document.getElementById(button.dataset.mdFieldAdd);

        if (modal === null) {
            return;
        }

        $(modal.querySelector('.modal-body')).load(
            button.dataset.mdFieldAddUrl,
            JSON.parse(button.dataset.mdFieldAddParams),
            function () {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            },
        );
    }

    /**
     * Restore the active block: the filtered block first, then the session storage,
     * then the location hash, block 1 otherwise. Reopen the "new field" modal when
     * front/field.form.php redirected here after "add another".
     *
     * @param {HTMLElement} list
     */
    function initList(list) {
        if (list.dataset.mdFieldListInit) {
            return;
        }
        list.dataset.mdFieldListInit = '1';

        const searched = parseInt(list.dataset.mdFieldListBlock, 10) || 0;
        const stored = sessionStorage.getItem('loadedblock');
        const hash = window.location.hash;
        let rank = 1;

        if (searched > 0) {
            rank = searched;
        } else if (stored && stored.startsWith('block') && document.getElementById(stored)) {
            rank = parseInt(stored.substring(5), 10);
        } else if (hash.startsWith('#block') && document.getElementById(hash.substring(1))) {
            rank = parseInt(hash.substring(6), 10);
        }
        showBlock(list, rank);

        const params = new URLSearchParams(window.location.search);
        if (params.get('open_add_field') === list.dataset.mdFieldList) {
            params.delete('open_add_field');
            const query = params.toString();
            history.replaceState(
                null,
                '',
                window.location.pathname + (query ? '?' + query : '') + window.location.hash,
            );

            const button = list.querySelector('[data-md-field-add-new]');
            if (button !== null) {
                openAddModal(button);
            }
        }
    }

    // Add buttons: new field, existing field.
    $(document).on('click', '[data-md-field-add]', function (event) {
        event.preventDefault();
        openAddModal(this);
    });

    // Block tabs of the list itself: the preview loaded below it carries its own tabs.
    $(document).on('click', '[data-md-field-list] > .tabs-container a.tablinks', function (event) {
        event.preventDefault();

        const rank = parseInt(this.id.replace('ablock', ''), 10);
        if (rank > 0) {
            showBlock(this.closest('[data-md-field-list]'), rank);
        }
    });

    // Purge of a field, confirmed first. The button submits the purge form of its block.
    $(document).on('click', 'button[data-md-field-purge]', function (event) {
        event.preventDefault();

        const button = this;
        glpi_confirm({
            message: $('<div>').text(button.dataset.mdFieldPurgeConfirm).html(),
            confirm_callback: function () {
                button.form.requestSubmit(button);
            },
        });
    });

    $(function () {
        document.querySelectorAll('[data-md-field-list]').forEach(initList);

        // The list is a tab of the metademand form, loaded through Ajax.
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== Node.ELEMENT_NODE) {
                        return;
                    }
                    if (node.matches('[data-md-field-list]')) {
                        initList(node);
                    }
                    node.querySelectorAll('[data-md-field-list]').forEach(initList);
                });
            });
        }).observe(document.body, {childList: true, subtree: true});
    });
})();
