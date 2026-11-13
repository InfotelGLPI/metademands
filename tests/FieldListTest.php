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

/**
 * Field list of a metademand (field_list.html.twig): the values are escaped by Twig,
 * and the add modals, block tabs and purge are driven by data-md-field-* attributes
 * instead of generated inline scripts.
 */
class FieldListTest extends DbTestCase
{
    private const NAME = '<b>Field</b>';

    /**
     * A metademand whose single block holds two fields, ordered 1 and 3 so that the
     * list offers to fix the orders.
     *
     * @return array{Metademand, Field}
     */
    private function createMetademandWithFields(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Field list',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = null;
        foreach ([1, 3] as $order) {
            $field = $this->createItem(Field::class, [
                'plugin_metademands_metademands_id' => $metademand->getID(),
                'type'                              => 'text',
                'name'                              => 'Field',
                'rank'                              => 1,
                'order'                             => $order,
                'entities_id'                       => $this->getTestRootEntity(true),
            ], ['order']);
        }
        // Store markup as legacy data could hold it, whatever the input sanitization
        global $DB;
        $DB->update(Field::getTable(), ['name' => self::NAME, 'order' => 3], ['id' => $field->getID()]);

        return [$metademand, $field];
    }

    /**
     * @param array<string, int|string> $search filter of the search form, as stored in the session
     */
    private function renderFields(Metademand $metademand, array $search = []): string
    {
        unset($_SESSION['plugin_metademands_searchresults']);
        if ($search !== []) {
            $_SESSION['plugin_metademands_searchresults'][$metademand->getID()] = $search;
        }
        ob_start();
        Field::displayTabContentForItem($metademand);

        return (string) ob_get_clean();
    }

    public function testListEscapesTheNameAndDrivesTheFormsByAttributes(): void
    {
        $this->login();
        [$metademand, $field] = $this->createMetademandWithFields();

        // A fresh test install does not grant the plugin rights to the profile
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $html = $this->renderFields($metademand);

        $this->assertStringContainsString('&lt;b&gt;Field&lt;/b&gt;', $html);
        $this->assertStringNotContainsString(self::NAME, $html);
        // The only scripts left are the ones of the core massive actions
        foreach (['addFieldmeta', 'addExistingFieldmeta', 'loadPreview', 'plugin_metademands_reloaditem', 'submitGetLink'] as $generated) {
            $this->assertStringNotContainsString($generated, $html);
        }
        $this->assertStringContainsString('data-md-field-list="' . $metademand->getID() . '"', $html);
        // Toolbar: a new field and an existing one; then one "add here" button per block,
        // which prefills the rank of its block
        $this->assertSame(3, substr_count($html, 'data-md-field-add="'));
        $this->assertSame(1, substr_count($html, 'data-md-field-add-rank="1"'));
        $this->assertStringContainsString('data-md-field-add-new', $html);
        $this->assertMatchesRegularExpression('/data-md-field-add-params="[^"]*&quot;id&quot;:-1[,}]/', $html);
        // Purge: one button per row, owned by the purge form of the block
        $this->assertMatchesRegularExpression(
            '/<button type="submit" form="(purgeMetaField\d+)" name="id" value="' . $field->getID() . '"/',
            $html,
        );
        preg_match('/form="(purgeMetaField\d+)"/', $html, $matches);
        $this->assertStringContainsString('<form id="' . $matches[1] . '" method="post"', $html);
        // Fix the orders: a plain form with its token
        $this->assertStringContainsString('name="fixorders"', $html);
        // Filters: run in the browser (public/scripts/field_list.js), no search form posted
        $this->assertStringContainsString('data-md-field-search', $html);
        $this->assertMatchesRegularExpression('/data-md-field-type-filter[^>]*>\s*<option value="">[^<]*<\/option>\s*<option value="text">/', $html);
        $this->assertStringNotContainsString('name="search"', $html);
    }

    /**
     * The list is filtered in the browser: a search criteria left in the session by the
     * former search form neither hides fields nor the offer to fix their orders.
     */
    public function testSessionSearchNoLongerFiltersTheList(): void
    {
        $this->login();
        [$metademand] = $this->createMetademandWithFields();

        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $filters = [
            ['block' => 2, 'type' => 0, 'item' => 0],
            ['block' => 0, 'type' => 'textarea', 'item' => 0],
        ];
        foreach ($filters as $search) {
            $html = $this->renderFields($metademand, $search);

            $this->assertStringContainsString('&lt;b&gt;Field&lt;/b&gt;', $html);
            $this->assertSame(2, substr_count($html, 'data-md-field-id="'));
            $this->assertStringContainsString('name="fixorders"', $html);
        }
        unset($_SESSION['plugin_metademands_searchresults']);
    }

    public function testReadOnlyListHasNoFormNorMassiveActions(): void
    {
        $this->login();
        [$metademand] = $this->createMetademandWithFields();

        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = READ;
        $html = $this->renderFields($metademand);

        $this->assertStringContainsString('&lt;b&gt;Field&lt;/b&gt;', $html);
        foreach (['data-md-field-add', 'data-md-field-purge', 'massiveaction'] as $absent) {
            $this->assertStringNotContainsString($absent, $html);
        }
    }
}
