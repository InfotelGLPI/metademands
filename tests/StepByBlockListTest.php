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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;

/**
 * Visibilities of the blocks in step-by-step mode (forms/step_by_block.html.twig): one numbered
 * step per block, and closed markup with or without any visibility.
 */
class StepByBlockListTest extends DbTestCase
{
    private function render(Metademand $metademand): string
    {
        ob_start();
        Step::showStepByBlock($metademand);

        return (string) ob_get_clean();
    }

    public function testListOfTheVisibilities(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;

        $metademand = $this->createItem(Metademand::class, [
            'name'              => 'Steps',
            'entities_id'       => $this->getTestRootEntity(true),
            'object_to_create'  => 'Ticket',
            'type'              => 0,
            'step_by_step_mode' => 1,
        ]);
        $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'Text',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);

        // No visibility yet: an empty state, the markup closed all the same
        $html = $this->render($metademand);
        $this->assertStringContainsString('No visibility defined', $html);
        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));

        $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 1,
            'groups_id'                         => 0,
            'only_by_supervisor'                => 0,
            'message'                           => '<p>Hello you</p>',
        ]);
        $html = $this->render($metademand);
        $this->assertStringContainsString('md-visibility-step', $html);
        $this->assertStringContainsString('Visible by all', $html);
        $this->assertStringContainsString('Hello you', $html);
        // The delete button keeps its own action inside the clickable row
        $this->assertStringContainsString('data-md-reload-noopen', $html);
        $this->assertSame(substr_count($html, '<div'), substr_count($html, '</div>'));
    }
}
