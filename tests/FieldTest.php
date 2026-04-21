<?php

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
