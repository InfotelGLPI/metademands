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
 * Wrapper of a wizard field (fields/field_wrapper.html.twig): the configuration link,
 * the comment help, the second label and the mandatory mark of an interval end are
 * written by the template from data.
 */
class FieldWrapperTest extends DbTestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function render(array $data, bool $preview): string
    {
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Wrapper',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);

        $data += [
            'id'                                => 987654,
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'name'                              => 'Wrapped',
            'item'                              => '',
            'comment'                           => '',
            'label2'                            => '',
            'hide_title'                        => 0,
            'hidden'                            => 0,
            'is_mandatory'                      => 0,
            'row_display'                       => 0,
            'use_richtext'                      => 1,
            'icon'                              => '',
            'use_future_date'                   => 0,
            'use_date_now'                      => 0,
            'additional_number_day'             => 0,
        ];

        ob_start();
        Field::displayFieldByType($metademand, $metademand->fields, $data, $preview);

        return (string) ob_get_clean();
    }

    public function testCommentHelpIsSanitizedIntoTheTooltipAttribute(): void
    {
        $this->login();

        $html = $this->render([
            'type'    => 'date',
            'comment' => '<b>Help</b><script>alert(1)</script>',
        ], true);

        // Bootstrap tooltip of the core, no qtip script anymore
        $this->assertStringContainsString('class="form-help" data-bs-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-bs-title="&lt;b&gt;Help&lt;/b&gt;"', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('qtip', $html);

        // Configuration link of the field in preview from the central interface
        $this->assertStringContainsString('<a href="' . Field::getFormURL() . '?id=987654"><i class="ti ti-settings"></i></a>', $html);
    }

    public function testNoConfigurationLinkOutsideThePreview(): void
    {
        $this->login();

        $html = $this->render(['type' => 'date'], false);

        $this->assertStringNotContainsString('ti-settings', $html);
    }

    public function testMandatoryIntervalEndHasItsMarkAndSanitizedLabel(): void
    {
        $this->login();

        $html = $this->render([
            'type'         => 'date_interval',
            'label2'       => 'End <script>alert(1)</script>',
            'is_mandatory' => 1,
        ], false);

        $this->assertStringContainsString("id-field='field987654-2'", $html);
        $this->assertStringContainsString("<span class='metademands_wizard_red'> * </span>", $html);
        $this->assertStringContainsString('name="field[987654-2]"', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
    }

    public function testSecondLabelIsShownSanitizedInItsAlert(): void
    {
        $this->login();

        $html = $this->render([
            'type'   => 'date',
            'label2' => '<p>Second <b>label</b></p><script>alert(1)</script>',
        ], false);

        $this->assertMatchesRegularExpression(
            '/<div class=\'remove-last-tinymce-margin\'><p>Second <b>label<\/b><\/p>\s*<\/div>/',
            $html,
        );
        $this->assertStringNotContainsString('alert(1)', $html);
    }
}
