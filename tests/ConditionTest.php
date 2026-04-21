<?php

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
