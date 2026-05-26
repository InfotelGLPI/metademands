<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Metademand;
use GlpiPlugin\Metademands\TicketTask;
use Ticket_Ticket;
use TicketTask as GlpiTicketTask;

class AddObjectsTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Reset Config singleton so each test reloads config from DB
        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        // Ensure plugin config row exists (required by formatFields())
        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), [
                'id'             => 1,
                'create_pdf'     => 0,
                'show_form_changes' => 0,
                'childs_parent_content' => 0,
            ]);
        }
    }

    private function createMetademand(array $extra = []): Metademand
    {
        return $this->createItem(Metademand::class, array_merge([
            'name'                 => 'Test Metademand',
            'entities_id'          => 0,
            'object_to_create'     => 'Ticket',
            'type'                 => \Ticket::DEMAND_TYPE,
            'is_order'             => 0,
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ], $extra));
    }

    private function createTextField(int $metademands_id): Field
    {
        return $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => 'text',
            'name'        => 'Champ texte',
            'rank'        => 1,
            'order'       => 1,
            'entities_id' => 0,
        ]);
    }

    private function createTaskWithTemplate(int $metademands_id, int $type, string $name): array
    {
        $task = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'name'          => $name,
            'type'          => $type,
            'level'         => 1,
            'entities_id'   => 0,
            'formatastable' => 0,
            'useBlock'      => 0,
            'block_use'     => '[]',
        ]);

        $tickettask = $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $task->getID(),
            'content'     => 'Contenu de la tâche de test',
            'entities_id' => 0,
        ]);

        return [$task, $tickettask];
    }

    private function buildValues(int $field_id, string $value = 'Valeur de test'): array
    {
        return ['fields' => [$field_id => $value]];
    }

    // -------------------------------------------------------------------------
    // Ticket principal
    // -------------------------------------------------------------------------

    public function testAddObjectsCreatesParentTicket(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $field      = $this->createTextField($metademand->getID());

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertIsArray($result, 'addObjects() doit retourner un tableau');
        $this->assertArrayHasKey('id', $result);
        $this->assertGreaterThan(0, $result['id'], 'Un ticket parent doit avoir été créé');

        $ticket = new \Ticket();
        $this->assertTrue(
            $ticket->getFromDB($result['id']),
            'Le ticket créé doit être retrouvable en base'
        );
    }

    public function testAddObjectsCreatesTicketMetademandLink(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();
        $field      = $this->createTextField($metademand->getID());

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertGreaterThan(0, $result['id']);

        $link_count = countElementsInTable(
            Ticket_Metademand::getTable(),
            [
                'tickets_id'                        => $result['id'],
                'plugin_metademands_metademands_id' => $metademand->getID(),
            ]
        );
        $this->assertSame(
            1,
            $link_count,
            'Un lien Ticket_Metademand doit relier le ticket à la métademande'
        );
    }

    public function testAddObjectsReturnsFalseForNonExistentMetademand(): void
    {
        $this->login('glpi', 'glpi');

        $result = Metademand::addObjects(99999, ['fields' => []]);

        // PHP 8 génère un warning car fields['object_to_create'] n'existe pas sur un objet vide
        $this->hasPhpLogRecordThatContains('Undefined array key "object_to_create"', 'WARNING');

        $this->assertFalse(
            $result,
            'addObjects() doit retourner false si la métademande n\'existe pas'
        );
    }

    // -------------------------------------------------------------------------
    // Sous-tickets (force_create_tasks = 0)
    // -------------------------------------------------------------------------

    public function testAddObjectsCreatesSubTicketWithParentOfLink(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand([
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ]);
        $field = $this->createTextField($metademand->getID());
        $this->createTaskWithTemplate($metademand->getID(), Task::TICKET_TYPE, 'Sous-demande');

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertGreaterThan(0, $result['id']);

        // GLPI normalise PARENT_OF → SON_OF en inversant les IDs (parent devient tickets_id_2)
        $link_count = countElementsInTable(
            Ticket_Ticket::getTable(),
            [
                'tickets_id_2' => $result['id'],
                'link'         => Ticket_Ticket::SON_OF,
            ]
        );
        $this->assertGreaterThan(
            0,
            $link_count,
            'Un lien SON_OF doit exister avec le ticket parent en tickets_id_2'
        );
    }

    public function testSubTicketIsLinkedToMetademand(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand([
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ]);
        $field = $this->createTextField($metademand->getID());
        $this->createTaskWithTemplate($metademand->getID(), Task::TICKET_TYPE, 'Sous-demande');

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertGreaterThan(0, $result['id']);

        // Le sous-ticket doit aussi avoir un enregistrement Ticket_Metademand
        $all_ticket_meta = countElementsInTable(
            Ticket_Metademand::getTable(),
            ['plugin_metademands_metademands_id' => $metademand->getID()]
        );
        $this->assertGreaterThanOrEqual(
            2,
            $all_ticket_meta,
            'Le ticket parent et le sous-ticket doivent tous deux être liés à la métademande'
        );
    }

    public function testNoSubTicketCreatedWhenNoTaskDefined(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand([
            'force_create_tasks'   => 0,
            'validation_subticket' => 0,
        ]);
        $field = $this->createTextField($metademand->getID());
        // Aucune tâche créée

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertGreaterThan(0, $result['id']);

        $link_count = countElementsInTable(
            Ticket_Ticket::getTable(),
            [
                'tickets_id_1' => $result['id'],
                'link'         => Ticket_Ticket::PARENT_OF,
            ]
        );
        $this->assertSame(
            0,
            $link_count,
            'Sans tâche définie, aucun sous-ticket ne doit être créé'
        );
    }

    // -------------------------------------------------------------------------
    // Tâches directes sur le ticket (force_create_tasks = 1)
    // -------------------------------------------------------------------------

    public function testAddObjectsWithForceTasksCreatesTaskRecord(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand(['force_create_tasks' => 1]);
        $field      = $this->createTextField($metademand->getID());
        $this->createTaskWithTemplate($metademand->getID(), Task::TASK_TYPE, 'Tâche automatique');

        $count_before = countElementsInTable(GlpiTicketTask::getTable());

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertGreaterThan(0, $result['id']);
        $this->assertGreaterThan(
            $count_before,
            countElementsInTable(GlpiTicketTask::getTable()),
            'Un enregistrement TicketTask GLPI doit avoir été ajouté lors de force_create_tasks=1'
        );
    }

    public function testAddObjectsWithForceTasksDoesNotCreateSubTicket(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand(['force_create_tasks' => 1]);
        $field      = $this->createTextField($metademand->getID());
        $this->createTaskWithTemplate($metademand->getID(), Task::TASK_TYPE, 'Tâche automatique');

        $result = Metademand::addObjects($metademand->getID(), $this->buildValues($field->getID()));

        $this->assertGreaterThan(0, $result['id']);

        $link_count = countElementsInTable(
            Ticket_Ticket::getTable(),
            [
                'tickets_id_1' => $result['id'],
                'link'         => Ticket_Ticket::PARENT_OF,
            ]
        );
        $this->assertSame(
            0,
            $link_count,
            'Avec force_create_tasks=1, aucun sous-ticket ne doit être créé'
        );
    }
}
