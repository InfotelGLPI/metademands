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
 the Free Software Foundation; either version 2 of the License, or
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

use GlpiPlugin\Metademands\Condition;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{
    public function testGetEnumShowConditionForDropdownReturnsOnlyEqAndNe(): void
    {
        $result = Condition::getEnumShowCondition('dropdown');

        $this->assertArrayHasKey(Condition::SHOW_CONDITION_EQ, $result);
        $this->assertArrayHasKey(Condition::SHOW_CONDITION_NE, $result);
        $this->assertArrayNotHasKey(Condition::SHOW_CONDITION_LT, $result);
        $this->assertArrayNotHasKey(Condition::SHOW_CONDITION_REGEX, $result);
    }

    public function testGetEnumShowConditionForNumberReturnsComparisonOperators(): void
    {
        $result = Condition::getEnumShowCondition('number');

        $this->assertArrayHasKey(Condition::SHOW_CONDITION_LT, $result);
        $this->assertArrayHasKey(Condition::SHOW_CONDITION_GT, $result);
        $this->assertArrayHasKey(Condition::SHOW_CONDITION_LE, $result);
        $this->assertArrayHasKey(Condition::SHOW_CONDITION_GE, $result);
    }

    public function testGetEnumShowConditionForTextReturnsRegex(): void
    {
        $result = Condition::getEnumShowCondition('text');

        $this->assertArrayHasKey(Condition::SHOW_CONDITION_REGEX, $result);
    }

    public function testGetEnumShowConditionForYesnoReturnsEmptyOperators(): void
    {
        $result = Condition::getEnumShowCondition('yesno');

        $this->assertArrayHasKey(Condition::SHOW_CONDITION_EMPTY, $result);
        $this->assertArrayHasKey(Condition::SHOW_CONDITION_NOTEMPTY, $result);
        $this->assertArrayNotHasKey(Condition::SHOW_CONDITION_LT, $result);
    }

    public function testShowConditionReturnsCorrectSymbol(): void
    {
        $this->assertSame('=', Condition::showCondition(Condition::SHOW_CONDITION_EQ));
        $this->assertSame('≠', Condition::showCondition(Condition::SHOW_CONDITION_NE));
        $this->assertSame('<', Condition::showCondition(Condition::SHOW_CONDITION_LT));
        $this->assertSame('>', Condition::showCondition(Condition::SHOW_CONDITION_GT));
        $this->assertSame('≤', Condition::showCondition(Condition::SHOW_CONDITION_LE));
        $this->assertSame('≥', Condition::showCondition(Condition::SHOW_CONDITION_GE));
    }

    public function testShowConditionForUnknownValueReturnsEmptyString(): void
    {
        $this->assertSame('', Condition::showCondition(999));
    }

    public function testShowLogicReturnsAndOrOr(): void
    {
        $this->assertSame('AND', Condition::showLogic(Condition::SHOW_LOGIC_AND));
        $this->assertSame('OR', Condition::showLogic(Condition::SHOW_LOGIC_OR));
    }

    public function testShowLogicForUnknownValueReturnsEmptyString(): void
    {
        $this->assertSame('', Condition::showLogic(999));
    }

    public function testGetEnumShowLogicReturnsBothKeys(): void
    {
        $result = Condition::getEnumShowLogic();

        $this->assertArrayHasKey(Condition::SHOW_LOGIC_AND, $result);
        $this->assertArrayHasKey(Condition::SHOW_LOGIC_OR, $result);
        $this->assertCount(2, $result);
    }

    public function testGetEnumShowRuleReturnsThreeRules(): void
    {
        $result = Condition::getEnumShowRule();

        $this->assertArrayHasKey(Condition::SHOW_RULE_ALWAYS, $result);
        $this->assertArrayHasKey(Condition::SHOW_RULE_HIDDEN, $result);
        $this->assertArrayHasKey(Condition::SHOW_RULE_SHOWN, $result);
        $this->assertCount(3, $result);
    }
}
