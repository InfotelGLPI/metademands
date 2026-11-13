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
use GlpiPlugin\Metademands\Metademand;

/**
 * "Wizard creation" tab: a field renamed in place (ajax/fieldname.php) and its parameters
 * edited in a panel under its line (ajax/fieldparameters.php).
 */
class FieldInlineEditTest extends DbTestCase
{
    /**
     * @return array{Metademand, Field}
     */
    private function createField(): array
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Inline',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $field = $this->createItem(Field::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'type'                              => 'text',
            'name'                              => 'Before',
            'rank'                              => 1,
            'order'                             => 1,
            'entities_id'                       => $this->getTestRootEntity(true),
        ], ['order']);
        $this->createItem(FieldParameter::class, [
            'plugin_metademands_fields_id' => $field->getID(),
            'is_mandatory'                 => 0,
        ]);

        return [$metademand, $field];
    }

    /**
     * @param array<string, mixed> $post
     */
    private function call(string $script, array $post): string
    {
        $_POST = $post;
        ob_start();
        require dirname(__DIR__) . '/ajax/' . $script;
        $output = (string) ob_get_clean();
        $_POST = [];

        return $output;
    }

    public function testLabelIsRenamedInPlace(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        [, $field] = $this->createField();

        $result = json_decode($this->call('fieldname.php', ['id' => $field->getID(), 'name' => '  After <b>x</b> ']), true);
        $this->assertSame(['success' => true, 'name' => 'After <b>x</b>'], $result);
        $field->getFromDB($field->getID());
        $this->assertSame('After <b>x</b>', $field->fields['name']);

        // An empty label is refused, the field keeps its own
        $result = json_decode($this->call('fieldname.php', ['id' => $field->getID(), 'name' => '   ']), true);
        $this->assertFalse($result['success']);
        $field->getFromDB($field->getID());
        $this->assertSame('After <b>x</b>', $field->fields['name']);
    }

    public function testPanelLoadsTheParameterFormOfTheField(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile'][Metademand::$rightname] = ALLSTANDARDRIGHT;
        [$metademand, $field] = $this->createField();

        $html = $this->call('fieldparameters.php', ['id' => $field->getID()]);
        $this->assertStringContainsString('/front/fieldparameter.form.php', $html);
        $this->assertMatchesRegularExpression(
            '/name="plugin_metademands_fields_id"[^>]*value="' . $field->getID() . '"/',
            $html,
        );
        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"[^>]*name="update"/', $html);

        // The list offers both, on the line of the field
        ob_start();
        Field::displayTabContentForItem($metademand);
        $list = (string) ob_get_clean();
        $this->assertStringContainsString('data-md-field-parameters="' . $field->getID() . '"', $list);
        $this->assertStringContainsString('data-md-field-rename="' . $field->getID() . '"', $list);
        $this->assertStringContainsString('data-md-field-list-parameters-url="', $list);
        // Preview: the form as the requesters fill it, in a new tab
        $this->assertMatchesRegularExpression(
            '/href="[^"]*' . preg_quote('/front/wizard.form.php?metademands_id=' . $metademand->getID() . '&amp;step=2&amp;render=1', '/')
            . '" class="btn btn-outline-secondary" target="_blank"/',
            $list,
        );
    }
}
