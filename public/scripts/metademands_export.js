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
 * Download button of the XML / JSON export massive action, previously emitted
 * as an inline <script> by Metademand::showMassiveActionsSubForm().
 *
 * The export is posted through Ajax to avoid the length limit of a GET request,
 * and the returned archive is saved through a temporary link. The sub form is
 * injected by the massive action modal, hence the delegated handler; the core
 * CSRF check validates the same-origin POST from the browser headers.
 */

(function () {
    'use strict';

    $(document).on('click', '#export_metademand[data-md-export-url]', function (e) {
        e.preventDefault();

        const button = this;
        let items;

        try {
            items = JSON.parse(button.dataset.mdExportItems);
        } catch (err) {
            return;
        }

        const spinner = document.createElement('i');
        spinner.className = 'fas fa-3x fa-spinner fa-pulse m-1';
        button.style.display = 'none';
        button.parentElement.prepend(spinner);

        const restore = function () {
            spinner.remove();
            button.removeAttribute('style');
        };

        $.ajax({
            url: button.dataset.mdExportUrl,
            type: 'POST',
            data: {metademands: items, action: button.dataset.mdExportAction},
            xhrFields: {
                responseType: 'blob',
            },
            success: function (blob, status, xhr) {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;

                const content_disposition = xhr.getResponseHeader('Content-Disposition');
                let filename = 'export_' + new Date().toISOString().slice(0, 10) + '.zip';

                if (content_disposition) {
                    const matches = content_disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                    if (matches !== null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }

                link.download = filename;
                document.body.appendChild(link);
                link.click();
                restore();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },
            error: restore,
        });
    });
})();
