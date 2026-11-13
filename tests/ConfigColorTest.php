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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Config;

/**
 * Colours of the configuration forced over the ones of each meta-demand: a colour input
 * always posts a colour, the "colour of each meta-demand" checkbox empties the setting.
 */
class ConfigColorTest extends DbTestCase
{
    public function testCheckboxEmptiesTheForcedColour(): void
    {
        $input = (new Config())->prepareInputForUpdate([
            'id'                        => 1,
            'title_color'               => '#ff0000',
            '_shown_title_color'        => '#FF0000',
            '_default_title_color'      => '1',
            'titleblock_color'          => '#00ff00',
        ]);

        $this->assertSame('', $input['title_color']);
        $this->assertSame('#00ff00', $input['titleblock_color']);
        $this->assertArrayNotHasKey('_default_title_color', $input);
    }

    public function testInvalidColourIsDropped(): void
    {
        $input = (new Config())->prepareInputForUpdate([
            'id'          => 1,
            'title_color' => 'red;background:url(x)',
        ]);

        $this->assertArrayNotHasKey('title_color', $input);
    }

    public function testTypeCommentsAreEditable(): void
    {
        $input = (new Config())->prepareInputForUpdate([
            'id'               => 1,
            'comment_incident' => 'Something is broken',
        ]);

        $this->assertSame('Something is broken', $input['comment_incident']);
    }

    public function testColourPickedWhileTheCheckboxIsCheckedIsKept(): void
    {
        $input = (new Config())->prepareInputForUpdate([
            'id'                   => 1,
            'icon_color'           => '#123456',
            '_shown_icon_color'    => '#000000',
            '_default_icon_color'  => '1',
        ]);

        $this->assertSame('#123456', $input['icon_color']);
    }

    public function testConfigFormIsRendered(): void
    {
        $this->login();
        $html = TemplateRenderer::getInstance()->render('@metademands/config.html.twig', [
            'config' => Config::getInstance(),
            'action' => '/config.form.php',
        ]);

        $this->assertStringContainsString('name="_default_icon_color"', $html);
        $this->assertStringContainsString('name="comment_change"', $html);
    }

    public function testTypeCommentFallsBackOnTheConfiguration(): void
    {
        $this->assertSame('', Config::getTypeComment('Unknown'));
        $this->assertCount(4, (new Config())->listTranslationsHandlers()[Config::class . '_1'] ?? []);
    }
}
