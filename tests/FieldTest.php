<?php

/*
 -------------------------------------------------------------------------
 metademands plugin for GLPI
 Copyright (C) 2018-2026 by the metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of metademands.

 metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Tests;

use GlpiPlugin\Metademands\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function testFieldTypesListIsNotEmpty(): void
    {
        $this->assertNotEmpty(Field::$field_types);
    }

    public function testTitleTypesAreSubsetOfAllFieldTypes(): void
    {
        foreach (Field::$field_title_types as $type) {
            $this->assertContains($type, Field::$field_types);
        }
    }

    public function testCustomvaluesTypesAreSubsetOfAllFieldTypes(): void
    {
        foreach (Field::$field_customvalues_types as $type) {
            $this->assertContains($type, Field::$field_types);
        }
    }

    public function testTextTypesAreSubsetOfAllFieldTypes(): void
    {
        foreach (Field::$field_text_types as $type) {
            $this->assertContains($type, Field::$field_types);
        }
    }

    public function testDateTypesAreSubsetOfAllFieldTypes(): void
    {
        foreach (Field::$field_date_types as $type) {
            $this->assertContains($type, Field::$field_types);
        }
    }

    public function testMaxFieldsConstantIsPositive(): void
    {
        $this->assertGreaterThan(0, Field::MAX_FIELDS);
    }

    public function testNotNullConstantValue(): void
    {
        $this->assertSame('NOT_NULL', Field::$not_null);
    }

    public function testDropdownTypesIncludeDropdownObjectAndDropdown(): void
    {
        $this->assertContains('dropdown', Field::$field_dropdown_types);
        $this->assertContains('dropdown_object', Field::$field_dropdown_types);
    }

    public function testWithObjectsTypesIncludeBasket(): void
    {
        $this->assertContains('basket', Field::$field_withobjects);
    }
}
