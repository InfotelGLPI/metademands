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
use GlpiPlugin\Metademands\Condition;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\Metademand;

/**
 * Partial evaluation of the conditions (ajax/condition.php with known_fields), used by the
 * step by step form to replace "Next" as soon as the steps filled break the conditions.
 */
class ConditionPartialTest extends DbTestCase
{
    /**
     * A metademand sent only when its field A is "yes" and its field B "ok".
     *
     * @return array{Metademand, Field, Field}
     */
    private function createMetademand(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Conditions',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $fields = [];
        foreach (['A' => 1, 'B' => 2] as $name => $rank) {
            $fields[] = $field = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'type'                              => 'text',
                'name'                              => $name,
                'rank'                              => $rank,
                'order'                             => 1,
                'entities_id'                       => $this->getTestRootEntity(true),
            ], ['order']);
            $this->createItem(Condition::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'plugin_metademands_fields_id'      => $field->getID(),
                'type'                              => 'text',
                'check_value'                       => $name === 'A' ? 'yes' : 'ok',
                'show_logic'                        => Condition::SHOW_LOGIC_AND,
                'show_condition'                    => Condition::SHOW_CONDITION_EQ,
                'order'                             => 1,
            ]);
        }

        return [$metademand, $fields[0], $fields[1]];
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return mixed the decoded answer
     */
    private function check(array $post)
    {
        $_POST = $post;
        ob_start();
        require dirname(__DIR__) . '/ajax/condition.php';
        $output = (string) ob_get_clean();
        $_POST = [];

        return json_decode($output, true);
    }

    public function testStepsFilledTellWhetherTheConditionsCanStillBeMet(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['plugin_metademands'] = ALLSTANDARDRIGHT;
        [$metademand, $a, $b] = $this->createMetademand();
        $id = $metademand->getID();

        // Step 1 filled with "no": the conditions can no longer be met, whatever B holds
        $this->assertSame(
            ['optimistic' => false, 'pessimistic' => false],
            $this->check(['metademands_id' => $id, 'field' => [$a->getID() => 'no'], 'known_fields' => [$a->getID()]]),
        );
        // Step 1 filled with "yes": still possible, not met yet (B unknown)
        $this->assertSame(
            ['optimistic' => true, 'pessimistic' => false],
            $this->check(['metademands_id' => $id, 'field' => [$a->getID() => 'yes'], 'known_fields' => [$a->getID()]]),
        );
        // Both steps filled as expected: met for good
        $this->assertSame(
            ['optimistic' => true, 'pessimistic' => true],
            $this->check([
                'metademands_id' => $id,
                'field'          => [$a->getID() => 'yes', $b->getID() => 'ok'],
                'known_fields'   => [$a->getID(), $b->getID()],
            ]),
        );
        // Without known_fields: the former answer of the whole form, unchanged
        $this->assertFalse($this->check(['metademands_id' => $id, 'field' => [$a->getID() => 'yes']]));
        $this->assertTrue($this->check(['metademands_id' => $id, 'field' => [$a->getID() => 'yes', $b->getID() => 'ok']]));
    }
}
