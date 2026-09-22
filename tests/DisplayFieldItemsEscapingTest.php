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

use GlpiPlugin\Metademands\Fields\Date;
use GlpiPlugin\Metademands\Fields\Datetime;
use GlpiPlugin\Metademands\Fields\Link;
use GlpiPlugin\Metademands\Fields\Number;
use GlpiPlugin\Metademands\Fields\Range;
use GlpiPlugin\Metademands\Fields\Time;
use GlpiPlugin\Metademands\Fields\Title;
use GlpiPlugin\Metademands\Fields\Titleblock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Field labels and field values are stored as plain text and are concatenated into the markup
 * built by every Fields\*::displayFieldItems() implementation. They are escaped at each of
 * those sinks rather than once at their source, because a few implementations legitimately
 * return markup and Metademand::getFieldValue() hands some labels back to Twig callers.
 *
 * These tests lock that contract down: labels must come out as entities, the surrounding
 * table markup must stay markup, and the guard below refuses any new sink that forgets it.
 */
class DisplayFieldItemsEscapingTest extends TestCase
{
    /**
     * A label that would run script if it reached the browser unescaped.
     */
    private const XSS_LABEL = '<img src=x onerror=alert(1)>"\'';

    /**
     * Field types whose displayFieldItems() needs neither the database nor the GLPI runtime
     * configuration, together with a value they accept.
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function provideDatabaseFreeFieldTypes(): array
    {
        return [
            'title'      => [Title::class, ''],
            'titleblock' => [Titleblock::class, ''],
            'number'     => [Number::class, '42'],
            'range'      => [Range::class, '7'],
            'time'       => [Time::class, '12:30'],
            'link'       => [Link::class, 'https://example.org/'],
            'date'       => [Date::class, '2026-09-22'],
            'datetime'   => [Datetime::class, '2026-09-22 08:15:00'],
        ];
    }

    /**
     * @param class-string $field_class
     */
    #[DataProvider('provideDatabaseFreeFieldTypes')]
    public function testLabelIsRenderedAsEntities(string $field_class, string $value): void
    {
        $content = $this->renderContent($field_class, self::XSS_LABEL, $value);

        $this->assertStringNotContainsString('<img', $content);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $content);
        $this->assertStringContainsString('&quot;&#039;', $content);
    }

    /**
     * Escaping the label must not escape the table markup the implementations build around it,
     * otherwise the summary would display its own tags.
     *
     * @param class-string $field_class
     */
    #[DataProvider('provideDatabaseFreeFieldTypes')]
    public function testSurroundingMarkupIsNotEscaped(string $field_class, string $value): void
    {
        $content = $this->renderContent($field_class, 'Plain label', $value);

        $this->assertStringContainsString('Plain label', $content);
        $this->assertMatchesRegularExpression('#^<t[dh] #', $content);
        $this->assertStringNotContainsString('&lt;t', $content);
    }

    /**
     * Without the table wrapper, the label is all there is: no markup may survive.
     */
    public function testLabelIsEscapedOutsideTableLayoutToo(): void
    {
        $content = $this->renderContent(Title::class, self::XSS_LABEL, '', false);

        $this->assertSame(htmlspecialchars(self::XSS_LABEL, ENT_QUOTES, 'UTF-8'), $content);
    }

    /**
     * Link builds an anchor out of the submitted value: the anchor is markup, the value inside
     * it is not.
     */
    public function testLinkValueCannotBreakOutOfTheAnchor(): void
    {
        $content = $this->renderContent(Link::class, 'Label', 'https://example.org/"><script>alert(1)</script>');

        $this->assertStringContainsString('<a href="', $content);
        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringContainsString('&lt;script&gt;', $content);
    }

    /**
     * Number, Range and Time drop anything that is not the kind of value they advertise, so a
     * forged submission never reaches the markup at all.
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function provideTypedFieldTypes(): array
    {
        return [
            'number' => [Number::class, '<b>1</b>'],
            'range'  => [Range::class, '1 <b>'],
            'time'   => [Time::class, '12:30<b>'],
        ];
    }

    /**
     * @param class-string $field_class
     */
    #[DataProvider('provideTypedFieldTypes')]
    public function testForgedValueIsDropped(string $field_class, string $forged_value): void
    {
        $this->assertSame('', $field_class::getFieldValue(['value' => $forged_value]));
    }

    /**
     * Static guard: a field type added later must escape its label like every existing one.
     * displayFieldItems() implementations all build their markup by appending to
     * $result[...]['content'], so a bare $label appended there is the defect this catches.
     */
    public function testNoFieldTypeAppendsAnUnescapedLabel(): void
    {
        $offenders = [];

        foreach (glob(dirname(__DIR__) . '/src/Fields/*.php') ?: [] as $path) {
            $source = (string) file_get_contents($path);
            foreach (explode("\n", $source) as $number => $line) {
                if (preg_match('/\[\'content\'\]\s*\.=\s*\$label\d*\s*;/', $line) === 1) {
                    $offenders[] = basename($path) . ':' . ($number + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These sinks append a label without escaping it: ' . implode(', ', $offenders),
        );
    }

    /**
     * Render one field and return the markup the summary would display.
     *
     * @param class-string $field_class
     */
    private function renderContent(string $field_class, string $label, string $value, bool $format_as_table = true): string
    {
        $rank   = 1;
        $result = [$rank => ['content' => '', 'display' => false]];
        $field  = [
            'id'         => 1,
            'rank'       => $rank,
            'name'       => $label,
            'value'      => $value,
            'item'       => '',
            'hide_title' => 0,
        ];

        $field_class::displayFieldItems($result, $format_as_table, "class='title'", $label, $field, false, 'en_GB');

        return $result[$rank]['content'];
    }
}
