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
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Wizard;

/**
 * Step of the wizard (wizard_metademands.html.twig): the hidden inputs and the
 * "Previous" button are written by the template.
 */
class WizardMetademandsTest extends DbTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    private function render(int $metademands_id, array $options = []): string
    {
        ob_start();
        Wizard::showMetademands($metademands_id, 2, 0, false, false, $options);

        return (string) ob_get_clean();
    }

    public function testStepCarriesItsHiddenInputs(): void
    {
        $this->login();

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Wizard step',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $id = $metademand->getID();

        $html = $this->render($id, ['ancestor_tickets_id' => '12"><b>x</b>']);

        $this->assertStringContainsString('<input type="hidden" name="metademands_id" value="' . $id . '">', $html);
        $this->assertStringContainsString('<input type="hidden" name="create_metademands" value="1">', $html);
        $this->assertStringContainsString(
            '<input type="hidden" name="ancestor_tickets_id" value="12&quot;&gt;&lt;b&gt;x&lt;/b&gt;">',
            $html,
        );
        $this->assertStringNotContainsString('name="previous"', $html);
    }

    public function testNoAncestorInputWithoutParentTicket(): void
    {
        $this->login();

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Wizard step',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);

        $this->assertStringNotContainsString('ancestor_tickets_id', $this->render($metademand->getID()));
    }

    public function testEmptyMetademandOffersToGoBack(): void
    {
        $this->login();

        // No step is built without a metademand
        $html = $this->render(0);

        $this->assertStringContainsString(__('No results found'), $html);
        $this->assertMatchesRegularExpression(
            '/<button type="submit" name="previous" value="' . __('Previous') . '" class="btn btn-primary">\s*<span>'
            . __('Previous') . '<\/span>\s*<\/button>/',
            $html,
        );
        $this->assertStringContainsString('<input type="hidden" name="previous_metademands_id" value="0">', $html);
        $this->assertStringNotContainsString('create_metademands', $html);
    }
}
