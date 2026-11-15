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
use GlpiPlugin\Metademands\TicketField;
use Group;
use ITILCategory;
use Location;
use RequestType;
use Ticket;
use TicketTemplate;
use TicketTemplateMandatoryField;
use TicketTemplatePredefinedField;
use User;

/**
 * Fields of the parent ticket of a metademand: defined by hand, as the destinations of a core
 * form (title, urgency, request source, location, assigned group or technician...), and
 * synchronised with a template that no longer depends on the profile of the user (#299).
 */
class TicketFieldManualTest extends DbTestCase
{
    private function loginWithPluginRights(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
    }

    /**
     * Search option of a field of the ticket templates.
     */
    private function num(string $field): int
    {
        $num = array_search($field, TicketTemplate::getAllowedFields(true, true), true);
        $this->assertNotFalse($num, $field);

        return (int) $num;
    }

    /**
     * @param int[] $categories
     */
    private function createMetademand(array $categories = []): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'              => 'Parent ticket fields ' . mt_rand(),
            'entities_id'       => $this->getTestRootEntity(true),
            'is_recursive'      => 1,
            'object_to_create'  => Ticket::class,
            'type'              => Ticket::DEMAND_TYPE,
            'itilcategories_id' => array_map('strval', $categories),
        ], ['itilcategories_id']);
    }

    /**
     * @param array<string, mixed> $predefined field => value
     * @param string[]             $mandatory  fields
     */
    private function createTemplate(array $predefined = [], array $mandatory = []): TicketTemplate
    {
        $template = $this->createItem(TicketTemplate::class, ['name' => 'Template ' . mt_rand(), 'entities_id' => 0, 'is_recursive' => 1]);
        foreach ($mandatory as $field) {
            $this->createItem(TicketTemplateMandatoryField::class, ['tickettemplates_id' => $template->getID(), 'num' => $this->num($field)]);
        }
        foreach ($predefined as $field => $value) {
            $this->createItem(TicketTemplatePredefinedField::class, [
                'tickettemplates_id' => $template->getID(),
                'num'                => $this->num($field),
                'value'              => $value,
            ]);
        }

        return $template;
    }

    /**
     * @return array<string, string> field => value
     */
    private function getValues(Metademand $meta): array
    {
        $names  = TicketTemplate::getAllowedFields(true, true);
        $values = [];
        foreach ((new TicketField())->find(['plugin_metademands_metademands_id' => $meta->getID()]) as $row) {
            $values[$names[$row['num']]] = (string) $row['value'];
        }

        return $values;
    }

    public function testTheTemplateIsTheSameWhateverTheProfile(): void
    {
        $this->loginWithPluginRights();

        $of_category = $this->createTemplate();
        $of_profile  = $this->createTemplate();
        $without = $this->createItem(ITILCategory::class, ['name' => 'Without template', 'entities_id' => 0, 'is_recursive' => 1]);
        $with = $this->createItem(ITILCategory::class, [
            'name'                       => 'With template',
            'entities_id'                => 0,
            'is_recursive'               => 1,
            'tickettemplates_id_demand'  => $of_category->getID(),
        ]);
        // The template of the profile, which getITILTemplateToUse() took
        $_SESSION['glpiactiveprofile']['tickettemplates_id'] = $of_profile->getID();

        // The first of its categories having one
        $meta = $this->createMetademand([$without->getID(), $with->getID()]);
        $this->assertSame($of_category->getID(), TicketField::getMetademandTemplateId($meta));
        $this->assertSame($of_category->getID(), TicketField::getMetademandTemplate($meta)->getID());

        // Otherwise the one of its entity, never the one of the profile (a category belongs to
        // one metademand of a type only)
        $without_too = $this->createItem(ITILCategory::class, ['name' => 'Without template too', 'entities_id' => 0, 'is_recursive' => 1]);
        $meta = $this->createMetademand([$without_too->getID()]);
        $entity_template = (int) \Entity::getUsedConfig('tickettemplates_strategy', 0, 'tickettemplates_id', 0);
        $this->assertSame($entity_template, TicketField::getMetademandTemplateId($meta));
        $this->assertNotSame($of_profile->getID(), TicketField::getMetademandTemplateId($meta));
    }

    public function testTheSynchronisationKeepsTheFieldsAddedByHand(): void
    {
        $this->loginWithPluginRights();

        $template = $this->createTemplate(['urgency' => 4, 'impact' => 2], ['urgency']);
        $meta = $this->createMetademand();
        // Added by hand before
        $this->createItem(TicketField::class, [
            'plugin_metademands_metademands_id' => $meta->getID(),
            'num'                               => $this->num('impact'),
            'value'                             => 5,
            'entities_id'                       => 0,
        ]);

        // With the template chosen in the tab
        TicketField::updateMandatoryTicketFields($meta->fields, $template->getID());
        $values = $this->getValues($meta);
        $this->assertSame('4', $values['urgency']);
        $this->assertSame('5', $values['impact']);

        // A field brought by the template follows it, not the one added by hand
        $urgency = new TicketField();
        $this->assertTrue($urgency->getFromDBByCrit(['plugin_metademands_metademands_id' => $meta->getID(), 'num' => $this->num('urgency')]));
        $this->assertSame(0, (int) $urgency->fields['is_deletable']);

        // The template no longer predefines the impact: the field added by hand stays
        $predefined = new TicketTemplatePredefinedField();
        $this->assertTrue($predefined->getFromDBByCrit(['tickettemplates_id' => $template->getID(), 'num' => $this->num('impact')]));
        $category = $this->createItem(ITILCategory::class, [
            'name'                      => 'Synchronised',
            'entities_id'               => 0,
            'is_recursive'              => 1,
            'tickettemplates_id_demand' => $template->getID(),
        ]);
        $this->assertTrue($meta->update(['id' => $meta->getID(), 'itilcategories_id' => [(string) $category->getID()]]));
        // As the hook on its purge does (registered for a logged in session only)
        TicketField::deleteFieldsFromTemplate($predefined, 'predefined');
        $this->assertSame('5', $this->getValues($meta)['impact']);

        // While a field the template brought goes with it
        $urgency_predefined = new TicketTemplatePredefinedField();
        $this->assertTrue($urgency_predefined->getFromDBByCrit(['tickettemplates_id' => $template->getID(), 'num' => $this->num('urgency')]));
        $mandatory = new TicketTemplateMandatoryField();
        $this->assertTrue($mandatory->getFromDBByCrit(['tickettemplates_id' => $template->getID(), 'num' => $this->num('urgency')]));
        $this->assertTrue($mandatory->delete(['id' => $mandatory->getID()], true));
        $meta->getFromDB($meta->getID());
        TicketField::deleteFieldsFromTemplate($urgency_predefined, 'predefined');
        $this->assertArrayNotHasKey('urgency', $this->getValues($meta));
    }

    public function testFieldsAreAddedByHandOnce(): void
    {
        $this->loginWithPluginRights();

        $meta = $this->createMetademand();
        $available = TicketField::getAvailableFields($meta);
        foreach (['name', 'urgency', 'impact', 'priority', 'requesttypes_id', 'locations_id', '_groups_id_assign', '_users_id_assign'] as $field) {
            $this->assertArrayHasKey($this->num($field), $available, $field);
        }
        // Filled by the plugin itself
        foreach (['content', 'itilcategories_id', '_users_id_requester'] as $field) {
            $this->assertArrayNotHasKey($this->num($field), $available, $field);
        }

        $location = $this->createItem(Location::class, ['name' => 'Manual location', 'entities_id' => 0]);
        $group = $this->createItem(Group::class, ['name' => 'Manual group', 'entities_id' => 0, 'is_recursive' => 1]);
        $tech = getItemByTypeName(User::class, 'tech', true);
        $source = getItemByTypeName(RequestType::class, 'Phone', true);
        $base = ['plugin_metademands_metademands_id' => $meta->getID(), 'entities_id' => 0];
        foreach ([
            'name'              => 'Order of #requester.login#',
            'urgency'           => 4,
            'impact'            => 2,
            'priority'          => 5,
            'requesttypes_id'   => $source,
            'locations_id'      => $location->getID(),
            '_groups_id_assign' => $group->getID(),
            '_users_id_assign'  => $tech,
        ] as $field => $value) {
            $this->createItem(TicketField::class, $base + ['num' => $this->num($field), 'value' => $value]);
        }
        $this->assertArrayNotHasKey($this->num('name'), TicketField::getAvailableFields($meta));

        // Once only, and not a field the plugin fills
        $ticketfield = new TicketField();
        $this->assertFalse($ticketfield->add($base + ['num' => $this->num('urgency'), 'value' => 2]));
        $this->assertFalse($ticketfield->add($base + ['num' => $this->num('_users_id_requester'), 'value' => $tech]));
        $this->hasSessionMessages(ERROR, [
            __('This field cannot be predefined, or it already is', 'metademands'),
            __('This field cannot be predefined, or it already is', 'metademands'),
        ]);

        // Applied to the parent ticket
        $inputs = Metademand::formatTicketFields($meta->getID(), 0, ['fields' => []], (int) \Session::getLoginUserID(), 0);
        $this->assertStringEndsWith('Order of ' . $_SESSION['glpiname'], $inputs['name']);
        $this->assertSame(4, $inputs['urgency']);
        $this->assertSame(2, $inputs['impact']);
        $this->assertSame(5, $inputs['priority']);
        $this->assertSame($source, $inputs['requesttypes_id']);
        $this->assertSame($location->getID(), $inputs['locations_id']);
        $this->assertSame($group->getID(), $inputs['_groups_id_assign']);
        $this->assertSame($tech, $inputs['_users_id_assign']);
    }

    public function testTheTabOffersTheTemplateAndTheNewField(): void
    {
        $this->loginWithPluginRights();

        $template = $this->createTemplate();
        $category = $this->createItem(ITILCategory::class, [
            'name'                      => 'Tab',
            'entities_id'               => 0,
            'is_recursive'              => 1,
            'tickettemplates_id_demand' => $template->getID(),
        ]);
        $meta = $this->createMetademand([$category->getID()]);

        ob_start();
        (new TicketField())->showFromMetademand($meta);
        $html = (string) ob_get_clean();
        // The template of the metademand, which another one may replace
        $this->assertMatchesRegularExpression('/name=[\'"]_templates_id[\'"]/', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]' . $template->getID() . '[\'"][^>]*selected/', $html);
        $this->assertStringContainsString('data-md-subitem-params="' . htmlescape(json_encode([
            'type'                              => TicketField::class,
            'parenttype'                        => Metademand::class,
            'plugin_metademands_metademands_id' => $meta->getID(),
            'id'                                => -1,
        ])) . '"', $html);

        // The new field: the fields that may be added, its value widget following
        ob_start();
        (new TicketField())->showForm(-1, ['parent' => $meta]);
        $html = (string) ob_get_clean();
        $this->assertMatchesRegularExpression('/name=[\'"]num[\'"]/', $html);
        $this->assertMatchesRegularExpression('/<option value=[\'"]' . $this->num('urgency') . '[\'"]/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value=[\'"]' . $this->num('_users_id_requester') . '[\'"]/', $html);
        $this->assertStringContainsString('dropdownMassiveActionField.php', $html);
        $this->assertMatchesRegularExpression('/name=[\'"]add[\'"]/', $html);
    }

    public function testTheTemplateOfACategoryReachesItsMetademands(): void
    {
        $this->loginWithPluginRights();

        $template = $this->createTemplate(['urgency' => 4], ['urgency']);
        $category = $this->createItem(ITILCategory::class, ['name' => 'Later', 'entities_id' => 0, 'is_recursive' => 1]);
        $meta = $this->createMetademand([$category->getID()]);
        $this->assertArrayNotHasKey('urgency', $this->getValues($meta));

        // The template given to the category afterwards: its metademands, whose categories are
        // a JSON list, follow it
        $this->assertTrue($category->update(['id' => $category->getID(), 'tickettemplates_id_demand' => $template->getID()]));
        // As the hook on its update does (registered for a logged in session only)
        TicketField::update_category_fields($category);
        $this->assertSame('4', $this->getValues($meta)['urgency'] ?? null);
    }
}
