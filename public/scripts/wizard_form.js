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
 * Behaviours of the meta-demand wizard form, previously emitted as inline
 * <script> blocks by Wizard::constructForm().
 *
 * The service catalogue replaces the whole .md-wizard subtree through Ajax, so
 * the handlers are delegated on the document and the one-shot tab bootstrap is
 * replayed by a MutationObserver instead of running once at load time. Every
 * value they need travels as a data attribute emitted by templates/wizard/.
 *
 * jQuery is used where the surrounding wizard requires it (serializeArray() on
 * widgets bound by metademands.js, the Bootstrap shown.bs.tab event); the rest
 * is plain DOM.
 */

/* global updateActiveTab, tinyMCE, glpi_html_dialog */

(function () {
    'use strict';

    /**
     * Save the tinyMCE editors and force every "to" multiselect to be serialized,
     * the way the legacy inline handlers did before posting the wizard.
     */
    function prepareWizardPost() {
        if (typeof tinyMCE !== 'undefined') {
            tinyMCE.triggerSave();
        }

        $('.resume_builder_input').trigger('change');
        $('select[id$="_to"] option').each(function () {
            $(this).prop('selected', true);
        });
    }

    // Basket reminder: jump straight to the basket summary.
    $(document).on('click', '#submitjob', function () {
        const alert_box = this.closest('[data-metademands-basket-url]');

        if (!alert_box) {
            return;
        }

        prepareWizardPost();
        $('#ajax_loader').show();

        $.ajax({
            url: alert_box.dataset.metademandsBasketUrl,
            type: 'POST',
            datatype: 'html',
            data: $('#wizard_form').serializeArray(),
            success: function (response) {
                $('#ajax_loader').hide();
                $('.md-wizard').replaceWith(response);
            },
            error: function (xhr, status, error) {
                console.log(xhr, status, error);
            },
        });
    });

    // Enter submits the wizard instead of hijacking the whole page, except in a textarea.
    $(document).on('keypress', '#meta-form', function (e) {
        if (e.which !== 13) {
            return;
        }

        if (!$(e.target).is('textarea')) {
            e.preventDefault();
            $('#submitjob').click();
            $('#nextBtn').click();
        }
    });

    // Horizontal scroll of the tab bar.
    $(document).on('click', '.tabs-container .scroll-btn', function () {
        const container = this.closest('.tabs-container').querySelector('.scrollable-tabs');

        if (container) {
            container.scrollBy({
                left: this.classList.contains('scroll-left') ? -150 : 150,
                behavior: 'smooth',
            });
        }
    });

    // Keep the current tab in the hash and in the session storage.
    $(document).on('shown.bs.tab', 'ul.nav-tabs > li > a', function (e) {
        const id = $(e.target).attr('href').substr(1);

        sessionStorage.setItem('loadedblock', id);
        window.location.hash = id;
    });

    /**
     * Restore the tab the requester was on: the session storage wins over the
     * location hash, and both win over the block posted by the step flow.
     *
     * @param {HTMLElement} container the .tabs-container just inserted
     */
    function initTabs(container) {
        if (container.dataset.metademandsTabsInit) {
            return;
        }
        container.dataset.metademandsTabsInit = '1';

        const hash = window.location.hash;
        const fieldid = sessionStorage.getItem('loadedblock');
        let block_id = parseInt(container.dataset.metademandsBlockId, 10) || 0;

        if (fieldid && document.getElementById(fieldid)) {
            updateActiveTab(fieldid.replace('block', ''));
        } else if (hash.startsWith('#block') && document.getElementById(hash.substring(1))) {
            updateActiveTab(hash.replace('#block', ''));
        } else {
            if (block_id <= 0) {
                block_id = 1;
            }
            updateActiveTab(block_id);
            sessionStorage.setItem('loadedblock', 'block' + block_id);
            window.location.hash = '#block' + block_id;
        }
    }

    /**
     * Replay, once, the data the previous holder of the form typed.
     *
     * @param {HTMLElement} container the hidden payload emitted by form_previous_data.html.twig
     */
    function initPreviousDialog(container) {
        if (container.dataset.metademandsDialogInit) {
            return;
        }
        container.dataset.metademandsDialogInit = '1';

        glpi_html_dialog({
            title: container.dataset.metademandsPreviousDialog,
            body: container.innerHTML,
            dialogclass: 'modal-lg',
        });
    }

    /**
     * Hide the blocks the step flow has already answered.
     *
     * @param {HTMLElement} container the marker emitted by form_hidden_blocks.html.twig
     */
    function initHiddenBlocks(container) {
        if (container.dataset.metademandsHiddenInit) {
            return;
        }
        container.dataset.metademandsHiddenInit = '1';

        container.dataset.metademandsHiddenBlocks.split(',').forEach(function (rank) {
            if (rank === '') {
                return;
            }

            document.querySelectorAll('div[bloc-id="bloc' + rank + '"]').forEach(function (block) {
                block.style.display = 'none';
            });
        });
    }

    /**
     * The basket summary replaces the step flow: its own button posts the order.
     *
     * @param {HTMLElement} container the holder emitted by fields/basket_summary.html.twig
     */
    function initBasketOrder(container) {
        if (container.dataset.metademandsOrderInit) {
            return;
        }
        container.dataset.metademandsOrderInit = '1';

        $('#prevBtn').hide();
        $('.step_wizard').hide();
    }

    const WIDGETS = [
        {selector: '[data-metademands-basket-order]', init: initBasketOrder},
        {selector: '.tabs-container[data-metademands-block-id]', init: initTabs},
        {selector: '[data-metademands-previous-dialog]', init: initPreviousDialog},
        {selector: '[data-metademands-hidden-blocks]', init: initHiddenBlocks},
    ];

    /**
     * Bootstrap every one-shot widget the wizard carries, whether it was there at
     * load time or came back from an Ajax replacement.
     *
     * @param {Element|Document} root
     */
    function initWidgets(root) {
        WIDGETS.forEach(function (widget) {
            if (root.matches && root.matches(widget.selector)) {
                widget.init(root);
            }

            root.querySelectorAll(widget.selector).forEach(widget.init);
        });
    }

    $(function () {
        initWidgets(document);

        // The wizard comes back from Ajax as a single subtree replacement, so the
        // tab bar and its blocks land in the same mutation.
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        initWidgets(node);
                    }
                });
            });
        }).observe(document.body, {childList: true, subtree: true});
    });

    /**
     * Refuse to save a draft while a free table line is typed but not confirmed:
     * that line would be silently dropped.
     *
     * @param {string} message
     *
     * @return {boolean}
     */
    function draftLinesAreConfirmed(message) {
        const inputs = document.querySelectorAll('#freetable_table #tr_input input');

        if (inputs.length === 0) {
            return true;
        }

        const pending = Array.prototype.some.call(inputs, function (input) {
            return input.value !== '' && input.value !== '0';
        });

        return !pending || window.confirm(message);
    }

    $(document).on('click', '.boutons_draft #button_save_mydraft', function () {
        const config = this.closest('.boutons_draft').dataset;

        if (!draftLinesAreConfirmed(config.metademandsUnconfirmedMsg)) {
            return;
        }

        prepareWizardPost();
        $('#ajax_loader').show();

        const posted = $('#wizard_form.formCustomDraft').serializeArray();

        posted.push({name: 'save_draft', value: true});
        posted.push({name: 'plugin_metademands_drafts_id', value: config.metademandsDraftId});
        posted.push({name: 'draft_name', value: config.metademandsDraftName});
        posted.push({name: 'step', value: 2});
        posted.push({name: 'fied', value: ''});
        posted.push({name: '_users_id_requester', value: config.metademandsUsersId});
        posted.push({name: 'metademands_id', value: config.metademandsId});

        $.ajax({
            url: config.metademandsAddUrl,
            type: 'POST',
            data: posted,
            success: function () {
                window.location.href = config.metademandsDraftFormUrl + '?id=' + config.metademandsDraftId;
            },
            error: function (xhr, status, error) {
                console.log(xhr, status, error);
            },
        });
    });

    $(document).on('click', '.boutons_draft .delete_draft', function () {
        const config = this.closest('.boutons_draft').dataset;

        $('#ajax_loader').show();

        $.ajax({
            url: config.metademandsDeleteUrl,
            type: 'POST',
            data: {
                users_id: config.metademandsUsersId,
                plugin_metademands_metademands_id: config.metademandsId,
                drafts_id: config.metademandsDraftId,
                self_delete: true,
            },
            success: function (response) {
                $('#bodyDraft').html(response);
                $('#ajax_loader').hide();
                window.location.href = config.metademandsDraftListUrl;
            },
            error: function (xhr, status, error) {
                console.log(xhr, status, error);
            },
        });
    });

    /**
     * Tell whether an element takes room on the page, the way jQuery ':visible' does.
     *
     * @param {HTMLElement} element
     *
     * @return {boolean}
     */
    function isVisible(element) {
        return element.offsetWidth > 0
            || element.offsetHeight > 0
            || element.getClientRects().length > 0;
    }

    // Collapse toggle of a block title. The same handler used to be emitted twice as an
    // inline scriptBlock, by Fields\Titleblock::showWizardField() and by the block break
    // of Wizard::displayBlockFields(), each time with its own random variable names.
    document.addEventListener('click', function (e) {
        const chevron = e.target.closest('[data-metademands-collapse]');

        if (!chevron) {
            return;
        }

        const bodies = document.querySelectorAll(
            '[bloc-hideid="bloc' + chevron.dataset.metademandsCollapse + '"]'
        );
        const shown = Array.prototype.some.call(bodies, isVisible);

        bodies.forEach(function (body) {
            body.style.display = shown ? 'none' : '';
        });
        chevron.classList.toggle('ti-chevron-up', !shown);
        chevron.classList.toggle('ti-chevron-down', shown);
    });

    // Toggle of the models / forms / drafts panel. The trigger used to carry an inline
    // onclick attribute calling jQuery's toggle().
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-metademands-toggle]');

        if (!trigger) {
            return;
        }

        const panel = document.getElementById(trigger.dataset.metademandsToggle);

        if (panel) {
            panel.style.display = isVisible(panel) ? 'none' : '';
        }
    });

    /**
     * Post the basket order, then create the meta-demand it belongs to.
     *
     * The whole handler used to be an inline <script> emitted by
     * Fields\Basket::displayBasketSummary(), with the meta-demand name interpolated
     * into a JS string literal.
     *
     * @param {Object} config
     */
    function sendBasketOrder(config) {
        const posted = $('#wizard_form').serializeArray();

        posted.push({name: 'save_form', value: true});
        posted.push({name: 'step', value: 2});
        posted.push({name: 'form_name', value: config.form_name});

        $.ajax({
            url: config.add_url,
            type: 'POST',
            dataType: 'html',
            data: posted,
            success: function (response) {
                if (response == 1) {
                    location.reload();
                    return;
                }

                $.ajax({
                    url: config.create_url,
                    type: 'POST',
                    data: posted,
                    success: function (created) {
                        if (created == 1) {
                            location.reload();
                        } else {
                            window.location.href = config.wizard_url;
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr, status, error);
                    },
                });
            },
            error: function (xhr, status, error) {
                console.log(xhr, status, error);
            },
        });
    }

    $(document).on('click', '#submitOrder', function () {
        const holder = this.closest('[data-metademands-basket-order]');

        if (holder) {
            sendBasketOrder(JSON.parse(holder.dataset.metademandsBasketOrder));
        }
    });

    // A free table drives its own save button, the draft one would bypass it.
    $(function () {
        if (document.querySelector('#freetable_table')) {
            const save = document.querySelector('.boutons_draft #button_save_mydraft');

            if (save) {
                save.style.display = 'none';
            }
        }
    });
})();
