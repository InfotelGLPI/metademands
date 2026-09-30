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

/* global updateActiveTab, tinyMCE, glpi_html_dialog, basketSearchInit, SignaturePad,
   addLine, editLine, removeLine, confirmUpdateLine */

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

    // Keep the current tab in the hash and in the session storage. The selector has to stay
    // anchored on the block tab bar: this file is loaded on every page of GLPI, and
    // `ul.nav-tabs > li > a` also matches the tab bar of the core forms, whose href is a
    // whole URL — writing that into the hash made jQuery parse it as a selector on the next
    // page load ("unrecognized expression: #front/ticket.form.php?id=…").
    $(document).on('shown.bs.tab', 'ul#fieldslist > li > a', function (e) {
        const href = $(e.target).attr('href') || '';

        if (href.indexOf('#block') !== 0) {
            return;
        }

        const id = href.substr(1);

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
     * Prefill an Email / Tel / Text / Url field from the user currently selected
     * in the linked "User" field, once, when the field is rendered. Later changes
     * of that user are handled by dropdownobject_linked_text_fields.js.
     *
     * @param {HTMLInputElement} input the input carrying the data-md-user-* attributes
     */
    function initUserPrefill(input) {
        if (input.dataset.mdUserInit) {
            return;
        }
        input.dataset.mdUserInit = '1';

        const source = document.querySelector('[name="' + CSS.escape(input.dataset.mdUserSource) + '"]');
        if (source === null) {
            return;
        }

        $.ajax({
            url: input.dataset.mdUserUrl,
            data: {id: source.value},
            success: function (response) {
                const values = typeof response === 'string' ? JSON.parse(response) : response;

                $(input).val(values[input.dataset.mdUserKey] ?? '').trigger('input');
            },
        });
    }

    /**
     * Drop the "required" flag of every input of a block (and of its sub-block),
     * as FieldOption::resetMandatoryBlockFields() does in the generated scripts.
     *
     * @param {number} block
     */
    function resetMandatoryBlock(block) {
        $('div[bloc-id="bloc' + block + '"], div[bloc-id="subbloc' + block + '"]')
            .find(':input')
            .each(function () {
                if (this.type === 'checkbox' || this.type === 'radio') {
                    $('[name^="' + CSS.escape(this.name) + '"]').removeAttr('required');
                }
                $(this).removeAttr('required');
            });
    }

    // A checkbox option hides its child blocks on each click (Fields/Checkbox.php).
    $(document).on('click', 'input[type="checkbox"][data-md-hide-blocks]', function () {
        JSON.parse(this.dataset.mdHideBlocks).forEach(function (block) {
            sessionStorage.setItem('hiddenbloc' + block, block);
            resetMandatoryBlock(block);
            $('div[bloc-id="bloc' + block + '"]').hide();
        });
    });

    /**
     * Expose the parameters of a free table (Fields/Freetable.php) under the
     * window.metademandfreelinesparams<rand> name that metademands_freelines.js and
     * the lines it generates rely on, and open an empty line on a new table.
     *
     * @param {HTMLTableElement} table
     */
    function initFreetable(table) {
        if (table.dataset.mdFreetableInit) {
            return;
        }
        table.dataset.mdFreetableInit = '1';

        const params = JSON.parse(table.dataset.mdFreetableParams);
        window['metademandfreelinesparams' + table.dataset.mdFreetable] = params;

        if (table.dataset.mdFreetableAutoadd) {
            addLine(params);
        }
    }

    /**
     * @param {string} rand
     *
     * @return {Object}
     */
    function freetableParams(rand) {
        return window['metademandfreelinesparams' + rand];
    }

    $(document).on('click', '[data-md-freetable-add]', function () {
        addLine(freetableParams(this.dataset.mdFreetableAdd));
    });

    $(document).on('click', 'button[data-md-freetable-edit]', function () {
        const rand = this.dataset.mdFreetableEdit;
        editLine(parseInt(this.dataset.mdFreetableLine, 10), rand, freetableParams(rand));
    });

    // Confirm button of a line being added (generated by metademands_freelines.js).
    $(document).on('click', 'button[data-md-freetable-confirm]', function () {
        const rand = this.dataset.mdFreetableConfirm;
        confirmUpdateLine(this, parseInt(this.dataset.mdFreetableLine, 10), 1, rand, freetableParams(rand));
    });

    $(document).on('click', 'button[data-md-freetable-remove]', function () {
        const rand = this.dataset.mdFreetableRemove;
        removeLine(parseInt(this.dataset.mdFreetableLine, 10), rand, freetableParams(rand));
    });

    /**
     * @param {string} id
     * @param {string} label
     * @param {string} amount_id
     * @param {number} amount
     *
     * @return {HTMLTableRowElement}
     */
    function grandTotalRow(id, label, amount_id, amount) {
        const background = 'var(--tblr-bg-surface, #ffffff)';

        const row = document.createElement('tr');
        row.id = id;

        const label_cell = document.createElement('th');
        label_cell.colSpan = 6;
        label_cell.style.backgroundColor = background;
        label_cell.textContent = label;

        const amount_cell = document.createElement('th');
        amount_cell.id = amount_id;
        amount_cell.style.whiteSpace = 'nowrap';
        amount_cell.style.textAlign = 'right';
        amount_cell.style.backgroundColor = background;
        amount_cell.textContent = amount.toFixed(2) + ' €';

        row.append(label_cell, amount_cell);

        return row;
    }

    // Validating a free table of the orderfollowup basket shows its grand total.
    $(document).on('click', 'button[data-md-freetable-validate]', function () {
        const rand = this.dataset.mdFreetableValidate;
        const tva = parseFloat(this.dataset.mdFreetableTva);
        const lines = $('#freetable_table' + CSS.escape(rand) + ' tr[id^="line_' + rand + '_"]');

        let grandtotal = 0;
        lines.each(function () {
            grandtotal += $(this).find('[id^=unit_price_]').val() * $(this).find('[id^=quantity_]').val();
        });
        const grandtotalht = grandtotal / (1 + tva);

        lines.css('background-color', 'var(--tblr-bg-surface, #f7f7f7)');
        if (document.getElementById('grandtotal_' + rand) === null) {
            lines.last().after(
                grandTotalRow('grandtotal_' + rand, this.dataset.mdFreetableLabel, 'amount_grandtotal_' + rand, grandtotal),
                grandTotalRow('grandtotalht_' + rand, this.dataset.mdFreetableLabelHt, 'amount_grandtotalht_' + rand, grandtotalht),
            );
        } else {
            $('#amount_grandtotal_' + CSS.escape(rand)).text(grandtotal.toFixed(2) + ' €');
            $('#amount_grandtotalht_' + CSS.escape(rand)).text(grandtotalht.toFixed(2) + ' €');
        }
        $('#nextBtn').show();
    });

    /**
     * Bind a signature pad (Fields/Signature.php): upload on save, removal on clear.
     *
     * @param {HTMLCanvasElement} canvas
     */
    function initSignature(canvas) {
        if (canvas.dataset.mdSignatureInit) {
            return;
        }
        if (typeof SignaturePad === 'undefined') {
            // The library tag follows the canvas (Fields/Signature.php): retry once it ran.
            const lib = document.querySelector('script[src*="signature_pad"]');
            if (lib) {
                lib.addEventListener('load', function () {
                    initSignature(canvas);
                }, {once: true});
            }
            return;
        }
        canvas.dataset.mdSignatureInit = '1';

        const data = canvas.dataset;
        const field_id = data.mdSignature;
        const is_mandatory = data.mdSignatureMandatory === '1';
        const messages = JSON.parse(data.mdSignatureMessages);
        const result = document.getElementById(data.mdSignatureResult);
        const hidden = document.getElementById(data.mdSignatureHidden);
        const pad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });
        let has_drawn = false;

        const showResult = function (ok, message) {
            const icon = document.createElement('i');
            icon.className = ok ? 'ti ti-circle-check fa-1x' : 'ti ti-circle-x fa-1x';
            icon.style.color = ok ? 'forestgreen' : 'darkred';
            result.replaceChildren(icon, ' ' + message);
        };

        if (is_mandatory) {
            sessionStorage.setItem('mandatory_sign_' + field_id, field_id);
        }

        document.getElementById(data.mdSignatureSave).addEventListener('click', function () {
            let datasign = '';
            if (!pad.isEmpty()) {
                datasign = pad.toDataURL('image/png');
                has_drawn = true;
            }
            if (!has_drawn && is_mandatory) {
                showResult(false, messages.mandatory);
                return;
            }
            if (has_drawn) {
                $.ajax({
                    url: data.mdSignatureAddUrl,
                    type: 'POST',
                    dataType: 'html',
                    data: {datasign: datasign, metademands_id: data.mdSignatureMetademand},
                    success: function (response) {
                        showResult(true, messages.add);
                        hidden.value = response;
                        sessionStorage.removeItem('mandatory_sign_' + field_id);
                    },
                    error: function () {
                        showResult(false, messages.failadd);
                    }
                });
            }
        });

        document.getElementById(data.mdSignatureClear).addEventListener('click', function () {
            pad.clear();
            has_drawn = false;
            $.ajax({
                url: data.mdSignatureRemoveUrl,
                type: 'POST',
                dataType: 'html',
                data: {metademands_id: data.mdSignatureMetademand, datasign: hidden.value},
                success: function () {
                    showResult(true, messages.remove);
                    hidden.value = '';
                    if (is_mandatory) {
                        sessionStorage.setItem('mandatory_sign_' + field_id, field_id);
                    }
                },
                error: function () {
                    showResult(false, messages.failremove);
                }
            });
        });
    }

    // "Add" button of the free table columns (templates/fields/freetable_fields.html.twig):
    // append the form of a new column.
    $(document).on('click', 'button[data-md-freetablefield-add]', function () {
        $(document)
            .metademandWizard({root_doc: this.dataset.mdFreetablefieldRoot})
            .metademands_add_custom_values('show_custom_fields', parseInt(this.dataset.mdFreetablefieldAdd, 10));
    });

    // "Add" button of the custom values of a field (templates/fields/field_customvalue_list.html.twig):
    // append the form of a new value.
    $(document).on('click', 'button[data-md-customvalue-add]', function () {
        $(document)
            .metademandWizard({root_doc: this.dataset.mdCustomvalueRoot})
            .metademands_add_custom_values('show_custom_fields', parseInt(this.dataset.mdCustomvalueAdd, 10));
    });

    // Submit buttons carrying a confirmation (templates/fields/field_customvalue_import.html.twig).
    $(document).on('click', 'button[data-md-confirm]', function (event) {
        if (!window.confirm(this.dataset.mdConfirm)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    });

    // Type of a new free table column (Freetablefield::addNewValue()): show the
    // dropdown values or the comment cell of that rank.
    $(document).on('change', 'select[data-md-freetablefield-type]', function () {
        const rank = this.dataset.mdFreetablefieldType;
        const type = String($(this).val());
        const show = function (class_name, visible) {
            document.querySelectorAll('.' + CSS.escape(class_name + rank)).forEach(function (span) {
                span.style.display = visible ? 'initial' : 'none';
            });
        };

        if (type === '1') {
            show('newdropdownvalue', false);
            show('newcomment', true);
        } else if (type === '2') {
            show('newdropdownvalue', true);
            show('newcomment', false);
        } else if (type === '3') {
            show('newdropdownvalue', false);
        }
    });

    // A pending step form (Stepform::showWaitingForm()) restores its values, then
    // opens the wizard on its block.
    $(document).on('click', 'a[data-md-stepform]', function (event) {
        event.preventDefault();

        const list = this.closest('[data-md-stepform-load-url]');
        const meta_id = this.dataset.mdStepformMeta;
        const block_id = this.dataset.mdStepformBlock;

        $('#ajax_loader').show();
        $.ajax({
            url: list.dataset.mdStepformLoadUrl,
            type: 'POST',
            data: {
                plugin_metademands_stepforms_id: this.dataset.mdStepform,
                metademands_id: meta_id,
                block_id: block_id,
                _users_id_requester: list.dataset.mdStepformUsersId,
            },
            success: function (response) {
                if (response == 0) {
                    $('#ajax_loader').hide();
                    const url = new URL(list.dataset.mdStepformWizardUrl, window.location.origin);
                    url.searchParams.set('metademands_id', meta_id);
                    url.searchParams.set('step', '2');
                    url.searchParams.set('block_id', block_id);
                    window.location.href = url.toString();
                }
            },
            error: function (xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
            }
        });
    });

    // Edit link or row of a sub item list (TicketField, Task): load its form into the
    // pane. Cells marked data-md-subitem-noopen (massive action checkbox, links) keep
    // their own behaviour. jQuery load() runs the scripts of the returned form.
    $(document).on('click', '[data-md-subitem-target]', function (event) {
        if ($(event.target).closest('[data-md-subitem-noopen]', this).length > 0) {
            return;
        }
        event.preventDefault();

        $('#' + CSS.escape(this.dataset.mdSubitemTarget))
            .load(this.dataset.mdSubitemUrl, JSON.parse(this.dataset.mdSubitemParams));
    });

    // A basket quantity reloads the total of its line (Fields/Basket.php).
    $(document).on('change', 'select[data-md-totalrow-target]', function () {
        const params = JSON.parse(this.dataset.mdTotalrowParams);
        params.quantity = $(this).val();

        $('#' + CSS.escape(this.dataset.mdTotalrowTarget)).load(this.dataset.mdTotalrowUrl, params);
    });

    /**
     * Bind the search inputs of a basket (basketSearchInit() in metademands.js).
     *
     * @param {HTMLTableElement} table
     */
    function initBasketSearch(table) {
        if (table.dataset.mdBasketSearchInit) {
            return;
        }
        table.dataset.mdBasketSearchInit = '1';

        basketSearchInit(table.dataset.mdBasketSearch);
    }

    /**
     * The basket summary replaces the step flow: its own button posts the order.
     *
     * @param {HTMLElement} container the holder emitted by fields/basket_summary.html.twig
     *                                or forms/basketline_summary.html.twig
     */
    function initBasketOrder(container) {
        if (container.dataset.metademandsOrderInit) {
            return;
        }
        container.dataset.metademandsOrderInit = '1';

        $('#prevBtn').hide();
        $('.step_wizard').hide();
    }

    /**
     * Publish the wizard configuration as the window.metademandparams and
     * window.metademandconditionsparams globals public/scripts/metademands.js
     * reads, show the first tab and wire the navigation buttons.
     *
     * @param {HTMLElement} container the marker emitted by form_params.html.twig
     */
    function initWizardParams(container) {
        if (container.dataset.metademandsParamsInit) {
            return;
        }
        container.dataset.metademandsParamsInit = '1';

        let params;
        let conditions;

        try {
            params = JSON.parse(container.dataset.metademandsWizardParams);
            conditions = JSON.parse(container.dataset.metademandsWizardConditions);
        } catch (e) {
            return;
        }

        window.metademandparams = params;
        window.metademandconditionsparams = conditions;

        // plugin_metademands_wizard_findFirstTab() returns nothing: firstnumTab ends up
        // undefined, exactly as with the legacy inline assignment the functions of
        // metademands.js were written against.
        window.firstnumTab = window.plugin_metademands_wizard_findFirstTab(params.block_id, params);
        window.plugin_metademands_wizard_showTab(window.firstnumTab, params, conditions);

        const prev_btn = document.getElementById('prevBtn');
        if (prev_btn) {
            prev_btn.addEventListener('click', function () {
                window.plugin_metademands_wizard_prevBtn(-1, window.firstnumTab, params, conditions);
            });
        }

        [['nextBtn', false], ['nextBtn2', true]].forEach(function ([id, change_step]) {
            const button = document.getElementById(id);

            if (!button) {
                return;
            }

            button.addEventListener('click', async function () {
                const result = await window.plugin_metademands_wizard_nextBtn(
                    1,
                    window.firstnumTab,
                    params,
                    conditions,
                    change_step,
                );

                if (result !== false) {
                    window.plugin_metademands_wizard_showTab(window.firstnumTab, params, conditions);
                }
            });
        });

        document.querySelectorAll('a.tablinks').forEach(function (tab_link) {
            tab_link.addEventListener('click', async function (e) {
                e.preventDefault();
                await window.plugin_metademands_wizard_goToTab(
                    parseInt(this.id.replace('ablock', ''), 10),
                    window.firstnumTab,
                    params,
                    conditions,
                );
            });
        });
    }

    /**
     * Once the next recipient of a step is chosen, closing its modal leaves the
     * form for the ticket list.
     *
     * @param {HTMLElement} container the marker emitted by forms/step_modal_redirect.html.twig
     */
    function initRedirectOnClose(container) {
        if (container.dataset.metademandsRedirectInit) {
            return;
        }
        container.dataset.metademandsRedirectInit = '1';

        const modal = document.getElementById(container.dataset.metademandsRedirectOnClose);

        if (modal) {
            modal.addEventListener('hide.bs.modal', function () {
                window.location.href = container.dataset.metademandsRedirectUrl;
            });
        }
    }

    const WIDGETS = [
        {selector: '[data-metademands-wizard-params]', init: initWizardParams},
        {selector: '[data-metademands-redirect-on-close]', init: initRedirectOnClose},
        {selector: '[data-metademands-basket-order]', init: initBasketOrder},
        {selector: '[data-md-basketline-order]', init: initBasketOrder},
        {selector: '.tabs-container[data-metademands-block-id]', init: initTabs},
        {selector: '[data-metademands-previous-dialog]', init: initPreviousDialog},
        {selector: '[data-metademands-hidden-blocks]', init: initHiddenBlocks},
        {selector: 'input[data-md-user-source]', init: initUserPrefill},
        {selector: 'table[data-md-basket-search]', init: initBasketSearch},
        {selector: 'table[data-md-freetable-params]', init: initFreetable},
        {selector: 'canvas[data-md-signature]', init: initSignature},
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

    /**
     * Post the order of a basket (forms/basketline_summary.html.twig): the payload the
     * wizard posted is saved, then the meta-demand is created.
     *
     * The handler used to be an inline <script> interpolating that payload.
     */
    $(document).on('click', 'button[data-md-basketline-submit]', function () {
        const order = JSON.parse(this.closest('[data-md-basketline-order]').dataset.mdBasketlineOrder);

        $.ajax({
            url: order.add_url,
            type: 'POST',
            data: order.post,
            success: function () {
                $.ajax({
                    url: order.create_url,
                    type: 'POST',
                    data: order.post,
                    success: function () {
                        window.location.href = order.wizard_url;
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
    });

    // The basket summary sits inside #wizard_form, where it cannot open a form of its
    // own: its clear, previous and line delete buttons post their fields through the
    // core helper.
    $(document).on('click', 'button[data-md-basketline-post]', function () {
        const order = JSON.parse(this.closest('[data-md-basketline-order]').dataset.mdBasketlineOrder);

        submitGetLink(order.wizard_form_url, JSON.parse(this.dataset.mdBasketlinePost));
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

    // The wizard opens with the spinner visible and used to hide it through an inline
    // `$(window).load()` — an alias jQuery removed in 3.0, so the spinner stayed up for
    // good. Plain DOM, and it also covers a load event that already fired.
    function hideWizardLoader() {
        document.querySelectorAll('#ajax_loader, .ajax_loader').forEach(function (loader) {
            loader.style.display = 'none';
        });
    }

    if (document.readyState === 'complete') {
        hideWizardLoader();
    } else {
        window.addEventListener('load', hideWizardLoader);
    }
})();
