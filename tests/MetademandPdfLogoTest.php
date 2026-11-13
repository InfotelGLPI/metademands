<?php

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

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\MetademandPdf;

/**
 * Logo of the header of the PDF (MetademandPdf::Header()): TCPDF 7 only reads files under an
 * allowlist of directories, which the one of the plugin is not part of on every server. The
 * logo is handed over as its content, whatever the current directory.
 */
class MetademandPdfLogoTest extends DbTestCase
{
    public function testThePdfIsBuiltWithItsLogoOutsideTheAllowedDirectories(): void
    {
        $this->login();

        $cwd = getcwd();
        // A current directory none of the allowed ones contains the plugin from
        chdir(sys_get_temp_dir());
        try {
            $pdf = new MetademandPdf('Title', 'Subtitle', 1);
            $pdf->AddPage();
            $pdf->Close();
            $content = $pdf->Output('', 'S');
        } finally {
            chdir($cwd);
        }

        $this->assertStringStartsWith('%PDF', $content);
        // The logo is embedded as an image
        $this->assertStringContainsString('/Subtype /Image', $content);
    }
}
