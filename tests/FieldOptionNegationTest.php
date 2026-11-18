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
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldCustomvalue;
use GlpiPlugin\Metademands\FieldOption;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\TicketTask;
use ITILValidationTemplate;
use Ticket;
use Ticket_Ticket;
use TicketValidation;
use User;
use ValidationStep;

/**
 * Options on any value but their value to check (FieldOption.is_negated), unfolded into the
 * other values of the field, and the validation template of the validation an option launches
 */
class FieldOptionNegationTest extends DbTestCase
{
    /** @var array{metademand: int, radio: int, values: array<string, int>, text: int} */
    private array $form;

    public function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionProperty(Config::class, 'instance');
        $ref->setValue(null, null);
        global $DB;
        if (!countElementsInTable(Config::getTable())) {
            $DB->insert(Config::getTable(), ['id' => 1, 'create_pdf' => 0, 'show_form_changes' => 0]);
        }

        $this->login('glpi', 'glpi');
        $this->form = $this->createForm();
    }

    /**
     * CHOICEFIELD (radio: ACCIDENT, INCIDENT, SUGGESTION) in block 1, BLOCKFIELD in block 2
     */
    private function createForm(): array
    {
        $metademands_id = $this->createItem(Metademand::class, [
            'name'               => 'N’est pas',
            'entities_id'        => 0,
            'object_to_create'   => 'Ticket',
            'type'               => Ticket::DEMAND_TYPE,
            'is_order'           => 0,
            'force_create_tasks' => 0,
        ])->getID();

        $field = fn(string $name, string $type, int $rank, int $order) => $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademands_id,
            'type'        => $type,
            'item'        => $type === 'radio' ? 'radio' : '',
            'name'        => $name,
            'rank'        => $rank,
            'order'       => $order,
            'entities_id' => 0,
        ], ['item'])->getID();

        $radio = $field('CHOICEFIELD', 'radio', 1, 1);
        $values = [];
        foreach (['ACCIDENT', 'INCIDENT', 'SUGGESTION'] as $rank => $name) {
            $values[$name] = $this->createItem(FieldCustomvalue::class, [
                'plugin_metademands_fields_id' => $radio,
                'name'                         => $name,
                'rank'                         => $rank,
                'is_default'                   => 0,
            ])->getID();
        }
        $field('BLOCKTITLE', 'title-block', 2, 1);
        $text = $field('BLOCKFIELD', 'text', 2, 2);

        return ['metademand' => $metademands_id, 'radio' => $radio, 'values' => $values, 'text' => $text];
    }

    private function addOption(array $input, array $skip = []): int
    {
        return $this->createItem(FieldOption::class, $input + [
            'plugin_metademands_fields_id' => $this->form['radio'],
        ], array_merge(['check_value'], $skip))->getID();
    }

    private function submit(string $choice): Ticket
    {
        $result = Metademand::addObjects($this->form['metademand'], ['fields' => [
            $this->form['radio'] => (string) $this->form['values'][$choice],
            $this->form['text']  => 'typed in the block',
        ]]);
        $this->assertIsArray($result);
        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB((int) $result['id']));

        return $ticket;
    }

    public function testNegatedOptionIsUnfoldedIntoTheOtherValues(): void
    {
        $this->addOption(['check_value' => $this->form['values']['ACCIDENT'], 'is_negated' => 1, 'hidden_block' => 2]);

        $applied = FieldOption::findApplied(['plugin_metademands_fields_id' => $this->form['radio']]);
        $this->assertEqualsCanonicalizing(
            [$this->form['values']['INCIDENT'], $this->form['values']['SUGGESTION']],
            array_map('intval', array_column($applied, 'check_value')),
        );
        $this->assertSame([0, 0], array_map('intval', array_column($applied, 'is_negated')));
        $this->assertSame([2, 2], array_map('intval', array_column($applied, 'hidden_block')));
    }

    public function testNegatedOptionShowsItsBlockForTheOtherValues(): void
    {
        $this->addOption(['check_value' => $this->form['values']['ACCIDENT'], 'is_negated' => 1, 'hidden_block' => 2]);

        $this->assertStringContainsString('typed in the block', $this->submit('INCIDENT')->fields['content']);
        $this->assertStringNotContainsString('typed in the block', $this->submit('ACCIDENT')->fields['content']);
    }

    public function testNegatedOptionCreatesItsTaskForTheOtherValues(): void
    {
        $tasks_id = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $this->form['metademand'],
            'name'        => 'NOTACCIDENTTASK',
            'type'        => Task::TICKET_TYPE,
            'level'       => 1,
            'entities_id' => 0,
        ])->getID();
        $this->createItem(TicketTask::class, [
            'plugin_metademands_tasks_id' => $tasks_id,
            'content'     => 'Contenu',
            'entities_id' => 0,
        ]);
        $this->addOption([
            'check_value'                 => $this->form['values']['ACCIDENT'],
            'is_negated'                  => 1,
            'plugin_metademands_tasks_id' => $tasks_id,
        ]);

        $children = fn(Ticket $parent) => countElementsInTable(Ticket_Ticket::getTable(), [
            'tickets_id_2' => $parent->getID(),
            'link'         => Ticket_Ticket::SON_OF,
        ]);
        $this->assertSame(1, $children($this->submit('SUGGESTION')));
        $this->assertSame(0, $children($this->submit('ACCIDENT')));
    }

    public function testNegationNeedsARealValueOfAKnownField(): void
    {
        $special = $this->addOption(['check_value' => -1, 'is_negated' => 1, 'hidden_block' => 2], ['is_negated']);
        $this->assertSame(0, (int) (new FieldOption())->getById($special)->fields['is_negated']);

        $text_option = $this->createItem(FieldOption::class, [
            'plugin_metademands_fields_id' => $this->form['text'],
            'check_value'                  => 1,
            'is_negated'                   => 1,
        ], ['is_negated']);
        $this->assertSame(0, (int) $text_option->fields['is_negated']);

        $this->assertSame([], FieldOption::getFieldValues($this->form['text']));
    }

    private function createTemplate(int $validator, int $validationsteps_id): ITILValidationTemplate
    {
        return $this->createItem(ITILValidationTemplate::class, [
            'name'               => 'Validation template',
            'content'            => 'COMMENT OF THE TEMPLATE',
            'entities_id'        => 0,
            'is_recursive'       => 1,
            'validationsteps_id' => $validationsteps_id,
            'itemtype_target'    => User::class,
            'items_id_target'    => [$validator],
        ], ['itemtype_target', 'items_id_target']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function validations(Ticket $ticket): array
    {
        return array_values(getAllDataFromTable(TicketValidation::getTable(), [
            'tickets_id' => $ticket->getID(),
        ]));
    }

    public function testOptionLaunchesTheValidationOfItsTemplate(): void
    {
        $validator = (int) getItemByTypeName(User::class, 'tech', true);
        $template = $this->createTemplate($validator, ValidationStep::getDefault()->getID());
        $this->addOption([
            'check_value'                => $this->form['values']['ACCIDENT'],
            'is_negated'                 => 1,
            'itilvalidationtemplates_id' => $template->getID(),
        ]);

        $this->assertSame([], $this->validations($this->submit('ACCIDENT')));

        $rows = $this->validations($this->submit('INCIDENT'));
        $this->assertCount(1, $rows);
        $this->assertSame(User::class, $rows[0]['itemtype_target']);
        $this->assertSame($validator, (int) $rows[0]['items_id_target']);
        $this->assertStringContainsString('COMMENT OF THE TEMPLATE', (string) $rows[0]['comment_submission']);
    }

    public function testTemplateWithoutStepStillAsksItsValidators(): void
    {
        $validator = (int) getItemByTypeName(User::class, 'tech', true);
        $template = $this->createTemplate($validator, 0);
        $this->addOption([
            'check_value'                => $this->form['values']['INCIDENT'],
            'itilvalidationtemplates_id' => $template->getID(),
        ]);

        $rows = $this->validations($this->submit('INCIDENT'));
        $this->assertCount(1, $rows);
        $this->assertSame($validator, (int) $rows[0]['items_id_target']);
    }
}
