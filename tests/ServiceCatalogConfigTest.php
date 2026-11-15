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

use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ServiceCatalog;
use Glpi\Tests\DbTestCase;
use Glpi\UI\IllustrationManager;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\ServiceCatalogConfig;

/**
 * Service catalog tab of a metademand: its illustration can be removed, to go back to the
 * one of its category or the default one, which stays inherited when the tab is saved.
 */
class ServiceCatalogConfigTest extends DbTestCase
{
    private function createMetademand(array $input = []): Metademand
    {
        return $this->createItem(Metademand::class, $input + [
            'name'             => 'Service catalog ' . mt_rand(),
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
    }

    private function renderTab(Metademand $metademand): string
    {
        $metademand->getFromDB($metademand->getID());
        ob_start();
        ServiceCatalogConfig::displayTabContentForItem($metademand);

        return (string) ob_get_clean();
    }

    public function testTheTabOfThePluginReplacesTheOneOfTheCore(): void
    {
        $this->login();

        $tabs = $this->createMetademand()->defineTabs();

        $this->assertArrayHasKey(ServiceCatalogConfig::class . '$1', $tabs);
        $this->assertArrayNotHasKey(ServiceCatalog::class . '$1', $tabs);
    }

    public function testAnInheritedIllustrationStaysInherited(): void
    {
        $this->login();

        $metademand = $this->createMetademand();
        $default = IllustrationManager::DEFAULT_ILLUSTRATION;

        $html = $this->renderTab($metademand);
        // The picker shows the illustration used; nothing to remove
        $this->assertStringContainsString('name="illustration" type="hidden" value="' . $default . '"', preg_replace('/\s+/', ' ', $html));
        $this->assertStringContainsString('name="_inherited_illustration" value="' . $default . '"', $html);
        $this->assertStringNotContainsString('_blank_illustration', $html);
        $this->assertStringNotContainsString('onchange', $html);

        // Saved unchanged: still inherited, so it follows the category given afterwards
        $this->assertTrue($metademand->update([
            'id'                      => $metademand->getID(),
            'illustration'            => $default,
            '_inherited_illustration' => $default,
        ]));
        $this->assertSame('', (string) Metademand::getById($metademand->getID())->fields['illustration']);

        $category = $this->createItem(Category::class, ['name' => 'Catalog category', 'illustration' => 'laptop']);
        $this->assertTrue($metademand->update(['id' => $metademand->getID(), 'forms_categories_id' => $category->getID()]));
        $this->assertSame('laptop', Metademand::getById($metademand->getID())->getServiceCatalogItemIllustration());

        // Another one picked: the illustration of the metademand
        $this->assertTrue($metademand->update([
            'id'                      => $metademand->getID(),
            'illustration'            => 'printer',
            '_inherited_illustration' => 'laptop',
        ]));
        $this->assertSame('printer', Metademand::getById($metademand->getID())->fields['illustration']);
    }

    public function testTheIllustrationOfTheMetademandCanBeRemoved(): void
    {
        $this->login();

        $category = $this->createItem(Category::class, ['name' => 'Catalog category', 'illustration' => 'laptop']);
        $metademand = $this->createMetademand(['forms_categories_id' => $category->getID()]);
        $this->assertTrue($metademand->update(['id' => $metademand->getID(), 'illustration' => 'printer']));

        $html = $this->renderTab($metademand);
        $this->assertStringContainsString('name="illustration" type="hidden" value="printer"', preg_replace('/\s+/', ' ', $html));
        $this->assertStringContainsString('name="_blank_illustration"', $html);
        $this->assertStringNotContainsString('_inherited_illustration', $html);

        // Removed, whatever the picker posts: back to the one of its category
        $this->assertTrue($metademand->update([
            'id'                  => $metademand->getID(),
            'illustration'        => 'printer',
            '_blank_illustration' => 1,
        ]));
        $metademand = Metademand::getById($metademand->getID());
        $this->assertSame('', (string) $metademand->fields['illustration']);
        $this->assertSame('laptop', $metademand->getServiceCatalogItemIllustration());

        // Without a category: the default one
        $this->assertTrue($metademand->update(['id' => $metademand->getID(), 'forms_categories_id' => 0]));
        $this->assertSame(
            IllustrationManager::DEFAULT_ILLUSTRATION,
            Metademand::getById($metademand->getID())->getServiceCatalogItemIllustration(),
        );
    }
}
