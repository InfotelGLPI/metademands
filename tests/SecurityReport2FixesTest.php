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

use Entity;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Basketline;
use GlpiPlugin\Metademands\CheckSchema;
use GlpiPlugin\Metademands\Draft_Value;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Form;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandValidation;
use GlpiPlugin\Metademands\Task;
use GlpiPlugin\Metademands\Ticket_Field;
use GlpiPlugin\Metademands\Tools;
use GlpiPlugin\Metademands\Wizard;
use Group;
use ReflectionMethod;
use Ticket;

/**
 * Second security review: the identifiers checked are the identifiers written, the criteria of
 * the group fields, the placeholder engine, the classes the core offers to its generic routes,
 * the targets of the tasks, the values of the list fields
 */
class SecurityReport2FixesTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
    }

    private function createMetademand(string $entity = '_test_root_entity'): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'              => 'Security ' . mt_rand(),
            'entities_id'       => getItemByTypeName(Entity::class, $entity, true),
            'is_recursive'      => 1,
            'object_to_create'  => 'Ticket',
            'type'              => Ticket::DEMAND_TYPE,
            'itilcategories_id' => '[]',
        ], ['itilcategories_id']);
    }

    public function testOnlyAPlainPositiveIdIsAccepted(): void
    {
        $this->assertSame(12, Metademand::getPostedId('12'));
        $this->assertSame(12, Metademand::getPostedId(12));

        // "5x12" was checked as 5 and loaded as 512, "/512" taken for a new item
        foreach (['5x12', '/512', '0', '-3', '1.5', '', null, ['12']] as $posted) {
            try {
                Metademand::getPostedId($posted);
                $this->fail('Accepted: ' . var_export($posted, true));
            } catch (BadRequestHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testTheCriteriaOfAGroupFieldAreItsFlagsOnly(): void
    {
        // A numeric key or a logical operator was read by GLPI 11 as a raw SQL expression
        $criteria = FieldParameter::getItemCriteria(Group::class, [
            'is_requester' => '1',
            'is_assign'    => '0',
            'user_group'   => '1',
            0              => '1=1) OR SLEEP(1',
            'OR'           => '1=1',
            'is_watcher'   => ['1'],
        ]);
        $this->assertSame(['is_requester' => 1, 'is_assign' => 0], $criteria);

        // As stored
        $this->assertSame(
            ['is_watcher' => 1],
            FieldParameter::getItemCriteria(Group::class, FieldParameter::_serialize(['is_watcher' => 1, '5' => 'x'])),
        );
    }

    public function testAnAlternationWithoutItsEndIsNoMatch(): void
    {
        // The rest of the text was returned: the callers looped forever on "[]" or "[x"
        $this->assertSame('', Metademand::getBetween('a [] b', '[', ']'));
        $this->assertSame('', Metademand::getBetween('a [x b', '[', ']'));
        $this->assertSame('x|y', Metademand::getBetween('a [x|y] b', '[', ']'));
    }

    public function testTheAnswersAreNeverReadAsPlaceholders(): void
    {
        $meta  = $this->createMetademand();
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $meta->getID(),
            'name'                              => 'Text',
            'type'                              => 'text',
            'rank'                              => 1,
            'order'                             => 1,
        ]);
        $fid = $field->getID();

        // An answer holding the alternation and the token of its own field: replaced once
        $answer = "[#$fid#]";
        $result = (new ReflectionMethod(Metademand::class, 'resolvePlaceholders'))->invoke(
            null,
            "Title [#$fid#|none] #$fid#",
            ['fields' => [$fid => $answer]],
            0,
            0,
        );

        $this->assertSame("Title $answer $answer", $result);
        $this->assertSame('a#b[c]', Metademand::restorePlaceholderValues(Metademand::protectPlaceholderValue('a#b[c]')));
    }

    public function testTheStoredValuesHaveNoGenericAccess(): void
    {
        foreach ([Ticket_Field::class, Draft_Value::class, Basketline::class] as $itemtype) {
            $this->assertFalse($itemtype::canView(), $itemtype);
            $this->assertFalse($itemtype::canCreate(), $itemtype);
            $this->assertFalse($itemtype::canUpdate(), $itemtype);
            $this->assertFalse($itemtype::canPurge(), $itemtype);
        }
        // A validation is created by a submission only, whatever the right of the validator
        $this->assertFalse(MetademandValidation::canCreate());
        // The classes reading the table of another one write nothing through it
        foreach ([Wizard::class, Tools::class, CheckSchema::class] as $itemtype) {
            $item = new $itemtype();
            $this->assertFalse($item->canCreateItem(), $itemtype);
            $this->assertFalse($item->canUpdateItem(), $itemtype);
            $this->assertFalse($item->canPurgeItem(), $itemtype);
        }
    }

    public function testASavedFormIsReadByItsAuthor(): void
    {
        $meta   = $this->createMetademand();
        $entity = getItemByTypeName(Entity::class, '_test_root_entity', true);
        $other  = getItemByTypeName(\User::class, 'tech', true);
        $base   = ['plugin_metademands_metademands_id' => $meta->getID(), 'entities_id' => $entity, 'name' => 'Form', 'date' => $_SESSION['glpi_currenttime']];

        $mine    = $this->createItem(Form::class, $base + ['users_id' => \Session::getLoginUserID(), 'is_model' => 0, 'is_private' => 1]);
        $private = $this->createItem(Form::class, $base + ['users_id' => $other, 'is_model' => 1, 'is_private' => 1]);
        $public  = $this->createItem(Form::class, $base + ['users_id' => $other, 'is_model' => 1, 'is_private' => 0]);

        $this->assertTrue($mine->canViewItem());
        $this->assertFalse($private->canViewItem());
        $this->assertTrue($public->canViewItem());
        $this->assertFalse($public->canPurgeItem());
    }

    public function testTheTargetsOfATaskOutsideTheEntitiesAreDropped(): void
    {
        $this->setEntity('_test_child_1', false);
        $child_1 = getItemByTypeName(Entity::class, '_test_child_1', true);
        $child_2 = getItemByTypeName(Entity::class, '_test_child_2', true);
        $group   = $this->createItem(Group::class, ['name' => 'Other entity', 'entities_id' => $child_2, 'is_recursive' => 0]);

        $added = Task::filterTaskTargets(
            ['entities_id' => $child_2, 'groups_id_assign' => $group->getID()],
            null,
            [],
            ['groups_id_assign'],
        );
        $this->assertSame($child_1, $added['entities_id']);
        $this->assertSame(0, $added['groups_id_assign']);

        // On update, the stored entity is kept; a stored value is never dropped
        $updated = Task::filterTaskTargets(['entities_id' => $child_2], ['entities_id' => 0]);
        $this->assertArrayNotHasKey('entities_id', $updated);
        $this->assertSame(['entities_id' => $child_2], Task::filterTaskTargets(['entities_id' => $child_2], ['entities_id' => $child_2]));
        // The entity of a ticket task is not read
        $this->assertSame(['entities_id' => $child_2], Task::filterTaskTargets(['entities_id' => $child_2], null, [], [], false));
        $this->hasSessionMessages(WARNING, [
            'An entity, a user or a group outside your entities was not kept',
            'An entity, a user or a group outside your entities was not kept',
        ]);
    }

    public function testAListValueTheDropdownDoesNotOfferIsEmptied(): void
    {
        $this->setEntity('_test_child_1', false);
        $here  = $this->createItem(Group::class, ['name' => 'Here', 'entities_id' => getItemByTypeName(Entity::class, '_test_child_1', true)]);
        $there = $this->createItem(Group::class, ['name' => 'There', 'entities_id' => getItemByTypeName(Entity::class, '_test_child_2', true), 'is_recursive' => 0]);
        $field = ['id' => 0, 'type' => 'dropdown_object', 'item' => Group::class];

        $this->assertSame($here->getID(), Metademand::filterOfferedValue($field, (string) $here->getID()));
        $this->assertSame('', Metademand::filterOfferedValue($field, (string) $there->getID()));
        $this->assertSame([$here->getID()], Metademand::filterOfferedValue(
            ['type' => 'dropdown_multiple'] + $field,
            [$here->getID(), $there->getID()],
        ));
        // The submitter himself, as the user fields offer him
        $this->assertSame(
            \Session::getLoginUserID(),
            Metademand::filterOfferedValue(['id' => 0, 'type' => 'dropdown_object', 'item' => \User::class], \Session::getLoginUserID()),
        );
        // Values of other types are left as posted
        $this->assertSame('free text', Metademand::filterOfferedValue(['type' => 'text', 'item' => ''], 'free text'));
    }

    public function testTheParentOfATaskIsATaskOfItsMetademand(): void
    {
        $meta  = $this->createMetademand();
        $other = $this->createMetademand();
        $task  = $this->createItem(Task::class, [
            'plugin_metademands_metademands_id' => $meta->getID(),
            'name'                              => 'Parent',
            'type'                              => Task::TICKET_TYPE,
            'level'                             => 1,
            'entities_id'                       => $meta->fields['entities_id'],
        ]);

        $this->assertTrue(Task::isTaskOfMetademand($task->getID(), $meta->getID()));
        $this->assertFalse(Task::isTaskOfMetademand($task->getID(), $other->getID()));
    }

    public function testTheListsOfTheGenericRoutesAreRestricted(): void
    {
        require_once dirname(__DIR__) . '/hook.php';

        foreach ([Form::class, \GlpiPlugin\Metademands\Stepform::class, MetademandValidation::class, \GlpiPlugin\Metademands\FieldOption::class] as $itemtype) {
            $this->assertNotSame([], plugin_metademands_addDefaultWhere($itemtype), $itemtype);
        }
    }
}
