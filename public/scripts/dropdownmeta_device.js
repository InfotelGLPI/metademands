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
 * Behaviours of the "my devices" tiles of a metademand field, previously emitted
 * as inline <script> blocks by Fields\Dropdownmeta::getItemsForUser().
 *
 * The picker is (re)injected through Ajax by ajax/umydevicesUpdate.php, so the
 * click handler is delegated on the document and the one-shot warning toggle is
 * replayed by a MutationObserver.
 *
 * The legacy code kept every tile of the page in a global hardwareType array, so
 * picking a device in one field cleared the highlight of the other fields as
 * well; the tiles are now reset within their own picker only.
 */

(function () {
    'use strict';

    const TILE_CLASS = 'btn md_buttonelt col-md-2 center';

    // Highlight the tile the requester just picked.
    document.addEventListener('click', function (event) {
        const tile = event.target.closest('[data-metademands-devices] label[data-metademands-device]');

        if (!tile) {
            return;
        }

        tile.closest('[data-metademands-devices]')
            .querySelectorAll('label[data-metademands-device]')
            .forEach(function (other) {
                other.className = TILE_CLASS;
            });

        tile.className = TILE_CLASS + ' md_buttonelt_color';
    });

    /**
     * Light the "this field is mandatory" warning up, or switch it off when the
     * requester owns no device at all.
     *
     * @param {HTMLElement} picker the container emitted by field_dropdownmeta_devices.html.twig
     */
    function initAlert(picker) {
        if (picker.dataset.metademandsDevicesInit) {
            return;
        }
        picker.dataset.metademandsDevicesInit = '1';

        const state = picker.dataset.metademandsAlert;

        if (state !== 'on' && state !== 'off') {
            return;
        }

        // The warning is emitted by the caller, next to the picker, not inside it.
        const alert = document.querySelector('.alertelt');

        if (alert) {
            alert.classList.toggle('active', state === 'on');
        }
    }

    function initAllPickers(root) {
        if (root.matches && root.matches('[data-metademands-devices]')) {
            initAlert(root);
        }

        root.querySelectorAll('[data-metademands-devices]').forEach(initAlert);
    }

    $(function () {
        initAllPickers(document);

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        initAllPickers(node);
                    }
                });
            });
        }).observe(document.body, {childList: true, subtree: true});
    });
})();
