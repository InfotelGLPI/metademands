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
 * New field form: one title per block (Field::prepareInputForAdd(), field_type_picker.js),
 * the blocks named by their title, and what the additional label and the comments become
 */
class FieldBlockTitleTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        $_SESSION['glpiactiveprofile'][Field::$rightname]      = ALLSTANDARDRIGHT;

        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Block titles',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'title-block',
            'name'                              => 'Accident',
            'rank'                              => 2,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);

        return $metademand;
    }

    private function blockTitleInput(Metademand $metademand, int $rank): array
    {
        return [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'title-block',
            'name'                              => 'Second title',
            'rank'                              => $rank,
            'entities_id'                       => $this->getTestRootEntity(true),
        ];
    }

    public function testTheFormRefusesASecondTitleInABlock(): void
    {
        $metademand = $this->createMetademand();

        $this->assertFalse((new Field())->add($this->blockTitleInput($metademand, 2) + ['_unique_block_title' => 1]));
        $this->hasSessionMessages(ERROR, ['Block 2 already has a block title: Accident']);
        // The input is given back to the form displayed after the redirect
        $this->assertSame('Second title', $_SESSION['glpi_plugin_metademands_fields'][0]['name'] ?? null);
        unset($_SESSION['glpi_plugin_metademands_fields']);

        $this->assertGreaterThan(0, (new Field())->add($this->blockTitleInput($metademand, 3) + ['_unique_block_title' => 1]));
    }

    public function testCopiesKeepTheirTitlesAsTheyAre(): void
    {
        $metademand = $this->createMetademand();

        // Duplication and import: no _unique_block_title
        $this->assertGreaterThan(0, (new Field())->add($this->blockTitleInput($metademand, 2)));
        $this->assertSame(2, countElementsInTable(Field::getTable(), [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'title-block',
            'rank'                              => 2,
        ]));
    }

    public function testTheNewFieldFormKnowsTheTitledBlocks(): void
    {
        $metademand = $this->createMetademand();

        ob_start();
        (new Field())->showForm(-1, ['parent' => $metademand]);
        $html = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/<input[^>]*name="_unique_block_title"[^>]*value="1"/', $html);
        $this->assertStringContainsString('data-md-titled-blocks="[{&quot;rank&quot;:2,&quot;title&quot;:&quot;Accident&quot;}]"', $html);
        $this->assertStringContainsString('data-md-titled-message="Block %1$s already has a block title: %2$s"', $html);
        // The "Block" dropdown names its blocks by their title, every block in the page
        $this->assertMatchesRegularExpression('/<option value=.2.[^>]*>Block 2 · Accident<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option value=.40.[^>]*>Block 40<\/option>/', $html);
        $this->assertStringContainsString('data-md-type-taken hidden', $html);
        $this->assertStringContainsString('data-md-titled-warning hidden', $html);
        // What the additional label and the comments become, for every type
        $this->assertStringContainsString('data-md-text-hints="', $html);
        $this->assertStringContainsString('Placeholder: the text shown in the field while it is empty', $html);
        $this->assertMatchesRegularExpression(
            '/data-md-text-hint="label2">Shown under the field, as a help text<\/div>/',
            $html,
        );
    }

    public function testAnExistingFieldShowsTheHintsOfItsType(): void
    {
        $metademand = $this->createMetademand();
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'date_interval',
            'name'                              => 'Period',
            'label2'                            => 'End',
            'rank'                              => 2,
            'order'                             => 2,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);

        ob_start();
        (new Field())->showForm($field->getID());
        $html = (string) ob_get_clean();

        $this->assertMatchesRegularExpression('/data-md-text-hint="label2">Label of the end date<\/div>/', $html);
        $this->assertStringNotContainsString('_unique_block_title', $html);
        $this->assertStringNotContainsString('data-md-titled-blocks', $html);
    }
}
