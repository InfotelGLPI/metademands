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
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use Ticket;
use TicketTemplateMandatoryField;

/**
 * Rendering of the ticket task form section (tickettask_form_section.html.twig):
 * every widget comes from the core macros, the values are the configured ones and
 * the mandatory marks follow the ticket template.
 */
class TicketTaskFormTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'                             => 'Ticket task form',
            'entities_id'                      => 0,
            'object_to_create'                 => 'Ticket',
            'type'                             => Ticket::DEMAND_TYPE,
            'initial_requester_childs_tickets' => 1,
        ]);
    }

    private function renderSection(int $metademands_id, int $tasktype, array $input = []): string
    {
        ob_start();
        TicketTask::showTicketTaskForm($metademands_id, true, $tasktype, $input);

        return (string) ob_get_clean();
    }

    public function testTicketTypeRendersTheConfiguredValues(): void
    {
        $this->login();
        $metademand = $this->createMetademand();
        foreach ([1, 3] as $rank) {
            $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'type'                              => 'text',
                'name'                              => 'Block field ' . $rank,
                'rank'                              => $rank,
                'order'                             => 1,
                'entities_id'                       => 0,
            ]);
        }

        $html = $this->renderSection($metademand->getID(), Task::TICKET_TYPE, [
            'useBlock'      => 0,
            'block_use'     => '[3]',
            'formatastable' => 0,
            'name'          => '"><b>Title</b>',
        ]);

        $this->assertMatchesRegularExpression('/name=["\x27]useBlock["\x27]/', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*name=["\x27]block_use\[\]["\x27][^>]*multiple/', $html);
        $this->assertMatchesRegularExpression('/<option value=["\x27]3["\x27] selected[^>]*>Block 3<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option value=["\x27]1["\x27]\s*>Block 1<\/option>/', $html);
        foreach (['formatastable', 'block_parent_ticket_resolution', 'parent_tasks_id', 'entities_id', 'itilcategories_id', 'users_id_assign', 'groups_id_assign'] as $name) {
            $this->assertMatchesRegularExpression('/name=["\x27]' . preg_quote($name, '/') . '["\x27]/', $html, $name);
        }
        // The initial requester is kept on the sons: no requester nor observer selector
        $this->assertDoesNotMatchRegularExpression('/name=["\x27]users_id_requester["\x27]/', $html);
        $this->assertDoesNotMatchRegularExpression('/name=["\x27]users_id_observer["\x27]/', $html);
        // The macros render the widget alone, without label nor column wrapper
        $this->assertStringNotContainsString('form-field row', $html);
        $this->assertStringContainsString('value="&quot;&gt;&lt;b&gt;Title&lt;/b&gt;"', $html);
        $this->assertStringNotContainsString('<span class="required">*</span>', $html);
    }

    public function testMandatoryMarksFollowTheTicketTemplate(): void
    {
        $this->login();
        $metademand = $this->createMetademand();

        // Title (1), status (12) and request source (9) made mandatory on the default ticket template
        $template_id = 1;
        foreach ([1, 12, 9] as $num) {
            $this->createItem(TicketTemplateMandatoryField::class, [
                'tickettemplates_id' => $template_id,
                'num'                => $num,
            ]);
        }

        $html = $this->renderSection($metademand->getID(), Task::TICKET_TYPE);

        $this->assertStringContainsString('name="_tickettemplates_id" value="' . $template_id . '"', $html);
        $this->assertMatchesRegularExpression('/name=["\x27]status["\x27]/', $html);
        $this->assertStringContainsString('name="requesttypes_id"', $html);
        $this->assertSame(3, substr_count($html, '<span class="required">*</span>'));
    }

    public function testNonTicketTypeOnlyRendersTheAssignmentAndTexts(): void
    {
        $this->login();
        $metademand = $this->createMetademand();

        $html = $this->renderSection($metademand->getID(), Task::TASK_TYPE);

        $this->assertDoesNotMatchRegularExpression('/name=["\x27]useBlock["\x27]/', $html);
        $this->assertStringNotContainsString('name="entities_id"', $html);
        $this->assertStringContainsString('name="users_id_assign"', $html);
        $this->assertStringContainsString('name="groups_id_assign"', $html);
        $this->assertStringContainsString('name="name"', $html);
    }
}
