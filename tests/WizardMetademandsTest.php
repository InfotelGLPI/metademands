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
use GlpiPlugin\Metademands\Config;
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

    public function testLayoutIsOnlyForTheRequestersForm(): void
    {
        $this->login();

        $form = $this->createItem(Metademand::class, [
            'name'             => 'Wizard layout',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $basket = $this->createItem(Metademand::class, [
            'name'             => 'Wizard basket',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'is_basket'        => 1,
        ]);

        // The form a requester fills: laid out by public/scripts/wizard_layout.js, which
        // reads its translated labels from the wrapper
        $html = $this->render($form->getID());
        $this->assertMatchesRegularExpression('/<div class="md-wizard"\s+data-md-wizard-layout\s/', $html);
        $this->assertStringContainsString('data-md-label-step="Step %1$s of %2$s"', $html);
        $this->assertStringContainsString('data-md-label-left-one="%s left"', $html);
        // No condition on this metademand: nothing explains a hidden send button
        $this->assertStringNotContainsString('data-md-conditions', $html);

        // Neither in the preview of the "Wizard creation" tab nor for a basket
        ob_start();
        Wizard::showMetademands($form->getID(), 2, 0, false, true);
        $preview = (string) ob_get_clean();
        $this->assertStringContainsString('<div class="md-wizard">', $preview);
        $this->assertStringNotContainsString('data-md-wizard-layout', $preview);
        $this->assertStringNotContainsString('data-md-wizard-layout', $this->render($basket->getID()));
    }

    /**
     * Update the plugin configuration, and forget the one Config::getInstance() keeps.
     *
     * @param array<string, mixed> $values
     */
    private function updateConfig(array $values): void
    {
        global $DB;
        $DB->update(Config::getTable(), $values, ['id' => 1]);
        (new \ReflectionProperty(Config::class, 'instance'))->setValue(null, null);
    }

    public function testFontAwesomeIconGivesWayToTheConfiguredOne(): void
    {
        $this->updateConfig(['icon_request' => 'ti-clipboard-text']);

        // Font Awesome icon of a former version: the one configured for the type
        $this->assertSame('ti-clipboard-text', Metademand::getDisplayIcon(['icon' => 'fas fa-file-code', 'type' => \Ticket::DEMAND_TYPE]));
        // Its own Tabler icon wins
        $this->assertSame('ti-device-mobile', Metademand::getDisplayIcon(['icon' => 'ti-device-mobile', 'type' => \Ticket::DEMAND_TYPE]));
        // No icon: the configured one, then the default
        $this->assertSame('ti-clipboard-text', Metademand::getDisplayIcon(['icon' => '', 'type' => \Ticket::DEMAND_TYPE]));
        // Nothing configured for the type: the Font Awesome icon is kept rather than lost
        $this->updateConfig(['icon_problem' => '']);
        $this->assertSame('fas fa-file-code', Metademand::getDisplayIcon(['icon' => 'fas fa-file-code', 'type' => 'Problem']));

        // The next tests read the configuration from the database again
        (new \ReflectionProperty(Config::class, 'instance'))->setValue(null, null);
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
