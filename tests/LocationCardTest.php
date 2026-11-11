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
use GlpiPlugin\Metademands\FieldParameter;
use GlpiPlugin\Metademands\Fields\Dropdown;
use GlpiPlugin\Metademands\Metademand;
use Location;

/**
 * Identity card of the location chosen in a dropdown / Location field: parameter, card
 * under the field of the wizard, address in the ticket content, and the boundary of
 * ajax/locationCard.php.
 */
class LocationCardTest extends DbTestCase
{
    private const XSS = '<img src=x onerror=alert(1)>';

    public function setUp(): void
    {
        parent::setUp();
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
    }

    public function tearDown(): void
    {
        unset($_SESSION['plugin_metademands'], $_GET['fields_id'], $_GET['locations_id']);
        parent::tearDown();
    }

    private function createMetademand(): Metademand
    {
        /** @var Metademand */
        return $this->createItem(Metademand::class, [
            'name'             => 'Location card',
            'entities_id'      => $this->getTestRootEntity(true),
            'is_recursive'     => 1,
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createLocationField(Metademand $metademand, array $parameters = [], string $item = Location::class): Field
    {
        /** @var Field $field */
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'dropdown',
            'item'                              => $item,
            'name'                              => 'Site',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $this->createItem(FieldParameter::class, $parameters + [
            'plugin_metademands_fields_id' => $field->getID(),
            'show_location_card'           => 1,
        ]);

        return $field;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function createLocation(array $input = []): Location
    {
        /** @var Location */
        return $this->createItem(Location::class, $input + [
            'name'         => 'Site ' . $this->getUniqueString(),
            'entities_id'  => $this->getTestRootEntity(true),
            'is_recursive' => 1,
            'address'      => '1 rue ' . self::XSS,
            'postcode'     => '75001',
            'town'         => 'Paris',
            'state'        => '',
            'country'      => 'France',
        ]);
    }

    private function callEndpoint(int $fields_id, int $locations_id): string
    {
        $_GET['fields_id']    = (string) $fields_id;
        $_GET['locations_id'] = (string) $locations_id;

        ob_start();
        include dirname(__DIR__) . '/ajax/locationCard.php';

        return trim((string) ob_get_clean());
    }

    private function renderWizardField(Metademand $metademand, Field $field, int $locations_id): string
    {
        $_SESSION['plugin_metademands'][$metademand->getID()]['fields'][$field->getID()] = $locations_id;
        $data = Field::getAllParamsFromField($field) + ['is_mandatory' => 0];

        ob_start();
        Dropdown::showWizardField($data, 'field', $locations_id, false, 0);

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function renderContent(Location $location, bool $format_as_table, array $extra = []): string
    {
        $result = [1 => ['content' => '', 'display' => false]];
        Dropdown::displayFieldItems(
            $result,
            $format_as_table,
            '',
            'Site',
            $extra + [
                'id'                 => 1,
                'rank'               => 1,
                'type'               => 'dropdown',
                'item'               => Location::class,
                'value'              => (string) $location->getID(),
                'show_location_card' => 1,
            ],
            false,
            'en_GB',
        );

        return $result[1]['content'];
    }

    public function testTheParameterIsOfferedForLocationsOnlyAndSaved(): void
    {
        $params = [
            'id'                                => 12,
            'plugin_metademands_metademands_id' => 5,
            'object_to_create'                  => 'Ticket',
            'used_by_child'                     => 0,
            'link_to_user'                      => 0,
            'display_type'                      => 0,
            'show_location_card'                => 1,
        ];

        $html = Dropdown::showFieldParameters($params + ['item' => Location::class]);
        $this->assertMatchesRegularExpression(
            '/name=[\'"]show_location_card[\'"][^>]*>.*?<option value=[\'"]1[\'"][^>]*selected/s',
            $html,
        );
        $this->assertStringContainsString('Show an identity card of the location', $html);

        $html = Dropdown::showFieldParameters($params + ['item' => \RequestType::class]);
        $this->assertStringNotContainsString('show_location_card', $html);

        $field     = $this->createLocationField($this->createMetademand(), ['show_location_card' => 0]);
        $parameter = new FieldParameter();
        $this->assertTrue($parameter->getFromDBByCrit(['plugin_metademands_fields_id' => $field->getID()]));
        $this->assertSame(0, (int) $parameter->fields['show_location_card']);

        $this->updateItem(FieldParameter::class, $parameter->getID(), ['show_location_card' => 1]);
        $this->assertTrue($parameter->getFromDB($parameter->getID()));
        $this->assertSame(1, (int) $parameter->fields['show_location_card']);
        $this->assertSame(1, Field::getAllParamsFromField($field)['show_location_card']);
    }

    public function testTheWizardRendersAnEscapedCardOfTheChosenLocation(): void
    {
        $metademand = $this->createMetademand();
        $field      = $this->createLocationField($metademand);
        $location   = $this->createLocation();

        $html = $this->renderWizardField($metademand, $field, $location->getID());

        $this->assertStringContainsString('data-md-location-card=', $html);
        $this->assertStringContainsString('data-md-location-card-current="' . $location->getID() . '"', $html);
        $this->assertStringContainsString('user-info-card md-location-card', $html);
        $this->assertStringContainsString('1 rue &lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('75001', $html);
        $this->assertStringContainsString('Paris', $html);
        $this->assertStringContainsString('France', $html);
        $this->assertStringNotContainsString('ti ti-map"', $html);
    }

    public function testTheWizardRendersAnEmptyAnchorWithoutLocationAndNothingWithoutTheOption(): void
    {
        $metademand = $this->createMetademand();
        $field      = $this->createLocationField($metademand);

        $html = $this->renderWizardField($metademand, $field, 0);
        $this->assertStringContainsString('data-md-location-card-current="0"', $html);
        $this->assertStringNotContainsString('user-info-card', $html);

        $without = $this->createLocationField($metademand, ['show_location_card' => 0]);
        $html    = $this->renderWizardField($metademand, $without, $this->createLocation()->getID());
        $this->assertStringNotContainsString('data-md-location-card', $html);
    }

    public function testTheTicketContentCarriesTheAddressOfTheLocation(): void
    {
        $location = $this->createLocation(['name' => 'HQ']);

        $content = $this->renderContent($location, true);
        $this->assertStringContainsString('<table style="border:0;"><tr><td>HQ</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Address: 1 rue &lt;img src=x onerror=alert(1)&gt;</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Postal code: 75001</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Town: Paris</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Country: France</td></tr>', $content);
        $this->assertStringNotContainsString('State', $content);
        $this->assertStringNotContainsString('<img', $content);

        $content = $this->renderContent($location, false);
        $this->assertStringContainsString('HQ (Address: 1 rue &lt;img', $content);
        $this->assertStringContainsString('Country: France)', $content);

        $content = $this->renderContent($location, true, ['show_location_card' => 0]);
        $this->assertStringEndsWith('<td colspan="1">HQ</td>', $content);
    }

    public function testTheEndpointRendersTheCardOfAnOfferedLocationWithoutTheLocationRight(): void
    {
        $field    = $this->createLocationField($this->createMetademand());
        $location = $this->createLocation();
        $_SESSION['glpiactiveprofile'][Location::$rightname] = 0;

        $html = $this->callEndpoint($field->getID(), $location->getID());

        $this->assertStringContainsString('md-location-card', $html);
        $this->assertStringContainsString('1 rue &lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    public function testTheEndpointRefusesALocationOutOfTheEntitiesOfTheField(): void
    {
        $field   = $this->createLocationField($this->createMetademand());
        $outside = $this->createLocation([
            'entities_id'  => getItemByTypeName(\Entity::class, '_test_child_2', true),
            'is_recursive' => 0,
        ]);
        $inside  = $this->createLocation();
        $this->setEntity('_test_child_1', false);

        $this->assertSame('', $this->callEndpoint($field->getID(), $outside->getID()));
        $this->assertStringContainsString('md-location-card', $this->callEndpoint($field->getID(), $inside->getID()));
    }

    public function testTheEndpointRefusesALocationOutsideTheRootOfTheField(): void
    {
        $root  = $this->createLocation(['name' => 'Root ' . $this->getUniqueString()]);
        $child = $this->createLocation(['locations_id' => $root->getID()]);
        $other = $this->createLocation();
        $field = $this->createLocationField($this->createMetademand(), ['root_items_id' => $root->getID()]);

        $this->assertStringContainsString('md-location-card', $this->callEndpoint($field->getID(), $child->getID()));
        $this->assertSame('', $this->callEndpoint($field->getID(), $other->getID()));
        $this->assertSame('', $this->callEndpoint($field->getID(), $root->getID()));
    }

    public function testTheEndpointRefusesAFieldWithoutTheOptionOrOfAnotherKind(): void
    {
        $metademand = $this->createMetademand();
        $location   = $this->createLocation();
        $without    = $this->createLocationField($metademand, ['show_location_card' => 0]);
        $other_item = $this->createLocationField($metademand, [], \RequestType::class);

        $this->assertSame('', $this->callEndpoint($without->getID(), $location->getID()));
        $this->assertSame('', $this->callEndpoint($other_item->getID(), $location->getID()));
        $this->assertSame('', $this->callEndpoint(0, $location->getID()));
    }

    public function testTheEndpointRefusesTheFieldOfAFormOutOfReach(): void
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Other entity',
            'entities_id'      => getItemByTypeName(\Entity::class, '_test_child_2', true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field    = $this->createLocationField($metademand);
        $location = $this->createLocation();
        $this->setEntity('_test_child_1', false);

        $this->assertSame('', $this->callEndpoint($field->getID(), $location->getID()));
    }
}
