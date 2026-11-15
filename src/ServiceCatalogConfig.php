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

namespace GlpiPlugin\Metademands;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\ServiceCatalog\ServiceCatalog;

/**
 * "Service catalog" tab of a metademand: the one of the core (ServiceCatalog), whose
 * illustration can be removed, to go back to the one of its category or the default one.
 *
 * The core tab shows that inherited illustration as the value of its picker, which offers no
 * empty choice: saving the tab made it the illustration of the metademand, for good.
 */
class ServiceCatalogConfig extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return ServiceCatalog::getTypeName($nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof Metademand || $item->isNewItem()) {
            return '';
        }

        return self::createTabEntry(self::getTypeName(), 0, null, ServiceCatalog::getIcon());
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof Metademand) {
            return false;
        }

        $own       = (string) ($item->fields['illustration'] ?? '');
        $inherited = $item->getInheritedServiceCatalogIllustration();

        TemplateRenderer::getInstance()->display('@metademands/service_catalog_config.html.twig', [
            'item'                   => $item,
            'icon'                   => ServiceCatalog::getIcon(),
            'illustration'           => $own !== '' ? $own : $inherited,
            'has_own_illustration'   => $own !== '',
            'inherited_illustration' => $inherited,
        ]);

        return true;
    }
}
