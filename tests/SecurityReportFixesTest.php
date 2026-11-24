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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Config;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\TicketField;
use Ticket;
use TicketTemplate;

/**
 * Security review of 3.7.3: the synchronisation of the ticket fields bound to the metademand
 * and to the templates the tab offers (1, 6), the configuration written with the right of its
 * form (3), the colours of the fields (5)
 */
class SecurityReportFixesTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
    }

    public function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    private function createMetademand(string $entity): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Security ' . mt_rand(),
            'entities_id'      => getItemByTypeName(Entity::class, $entity, true),
            'is_recursive'     => 0,
            'object_to_create' => 'Ticket',
            'type'             => Ticket::DEMAND_TYPE,
            'itilcategories_id' => '[]',
        ], ['itilcategories_id']);
    }

    private function postTemplateSync(array $post): void
    {
        $_POST = $post + ['template_sync' => 1];
        include dirname(__DIR__) . '/front/ticketfield.form.php';
    }

    public function testTheSynchronisationOfAMetademandOutOfReachIsDenied(): void
    {
        $other = $this->createMetademand('_test_child_2');
        $this->setEntity('_test_child_1', false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->postTemplateSync($other->fields);
    }

    public function testTheSynchronisationWithoutMetademandIsDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->postTemplateSync(['id' => 0, 'entities_id' => 0, 'itilcategories_id' => '[]']);
    }

    public function testOnlyTheTemplatesTheTabOffersAreSynchronised(): void
    {
        $metademand = $this->createMetademand('_test_child_1');
        $hidden = $this->createItem(TicketTemplate::class, [
            'name'         => 'Hidden ' . mt_rand(),
            'entities_id'  => getItemByTypeName(Entity::class, '_test_child_2', true),
            'is_recursive' => 0,
        ]);
        $visible = $this->createItem(TicketTemplate::class, [
            'name'         => 'Visible ' . mt_rand(),
            'entities_id'  => getItemByTypeName(Entity::class, '_test_child_1', true),
            'is_recursive' => 0,
        ]);
        $this->setEntity('_test_child_1', false);

        $this->assertFalse(TicketField::isOfferedTemplate($metademand, $hidden->getID()));
        $this->assertNull(TicketField::getMetademandTemplate($metademand, $hidden->getID()));
        $this->assertTrue(TicketField::isOfferedTemplate($metademand, $visible->getID()));
        $this->assertSame($visible->getID(), TicketField::getMetademandTemplate($metademand, $visible->getID())?->getID());

        // The template of the metademand itself, whoever looks
        $own = TicketField::getMetademandTemplateId($metademand);
        if ($own > 0) {
            $this->assertTrue(TicketField::isOfferedTemplate($metademand, $own));
        }
    }

    public function testThePluginRightDoesNotWriteTheConfiguration(): void
    {
        $_SESSION['glpiactiveprofile']['config'] = READ;
        $this->assertFalse(Config::canUpdate());
        $this->assertFalse((new Config())->can(1, UPDATE));

        $_SESSION['glpiactiveprofile']['config'] = READ | UPDATE;
        $this->assertTrue(Config::canUpdate());
    }

    public function testTheColourOfAFieldIsHexadecimal(): void
    {
        $metademand = $this->createMetademand('_test_child_1');
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'entities_id'                       => 0,
            'type'                              => 'title-block',
            'item'                              => '',
            'name'                              => 'Title',
            'rank'                              => 1,
        ], ['item', 'rank']);

        $parameter = new FieldParameter();
        $id = $parameter->add([
            'plugin_metademands_fields_id' => $field->getID(),
            'color'                        => 'red;background:url(https://example.test/beacon)',
        ]);
        $this->assertGreaterThan(0, $id);
        $this->assertSame('#000000', $parameter->fields['color']);

        $this->assertTrue($parameter->update(['id' => $id, 'color' => '#AbC123']));
        $parameter->getFromDB($id);
        $this->assertSame('#AbC123', $parameter->fields['color']);

        // Anything else keeps the stored colour
        $this->assertTrue($parameter->update(['id' => $id, 'color' => '#fff;position:fixed']));
        $parameter->getFromDB($id);
        $this->assertSame('#AbC123', $parameter->fields['color']);

        // And a colour stored before gives no CSS
        $this->assertSame('', Metademand::toThemedForeground('red;background:url(x)'));
        $this->assertStringStartsWith('color-mix(in srgb, #AbC123,', Metademand::toThemedForeground('#AbC123'));
    }
}
