<?php

namespace GlpiPlugin\Metademands\Tests;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Step;

class StepTest extends DbTestCase
{
    private function createMetademand(): Metademand
    {
        return $this->createItem(Metademand::class, [
            'name'             => 'Step Test Metademand',
            'entities_id'      => 0,
            'object_to_create' => 'Ticket',
            'type'             => 0,
            'step_by_step_mode' => 1,
        ]);
    }

    public function testStepCanBeCreatedForMetademand(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $step = $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 1,
        ]);

        $this->assertGreaterThan(0, $step->getID());
        $this->assertSame(
            $metademand->getID(),
            (int) $step->getField('plugin_metademands_metademands_id')
        );
    }

    public function testMultipleStepsCanBeCreatedForSameMetademand(): void
    {
        $this->login('glpi', 'glpi');

        $metademand = $this->createMetademand();

        $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 1,
        ]);
        $this->createItem(Step::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'block_id'                          => 2,
        ]);

        $count = countElementsInTable(
            Step::getTable(),
            ['plugin_metademands_metademands_id' => $metademand->getID()]
        );
        $this->assertSame(2, $count);
    }

    public function testStepGetTypeNameIsNotEmpty(): void
    {
        $this->assertNotEmpty(Step::getTypeName());
    }

    public function testGetForbiddenStandardMassiveActionIncludesUpdate(): void
    {
        $this->login('glpi', 'glpi');

        $step = new Step();
        $forbidden = $step->getForbiddenStandardMassiveAction();

        $this->assertContains('update', $forbidden);
    }
}
