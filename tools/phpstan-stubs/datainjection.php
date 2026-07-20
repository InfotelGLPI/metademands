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

/**
 * PHPStan-only stub for the optional `datainjection` plugin dependency.
 *
 * BasketobjectInjection integrates with datainjection only when that plugin is
 * installed. This stub lets static analysis resolve the referenced symbols
 * without relying on the datainjection plugin being present on disk, so the
 * analysis stays independent of the deployment layout (marketplace/, plugins/,
 * or absent). It is never loaded at runtime and is stripped from the release
 * archive (tools/).
 */

interface PluginDatainjectionInjectionInterface
{
}

class PluginDatainjectionCommonInjectionLib
{
    public function __construct($injectionClass, $values, $options = []) {}

    public static function getBlacklistedOptions($itemtype): array
    {
        return [];
    }

    public static function addToSearchOptions($tab, $options, $injectionClass): array
    {
        return [];
    }

    public function processAddOrUpdate(): void {}

    public function getInjectionResults(): array
    {
        return [];
    }
}
