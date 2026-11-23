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

/**
 * PHPStan-only stub for the optional `resources` plugin dependency.
 *
 * The links of a meta-demand to the contract types of the Resources plugin
 * (Metademand_Resource) are exported and imported by name (Export) when that
 * plugin is active. This stub lets static analysis resolve the contract type
 * class without relying on the resources plugin being present on disk, as the
 * datainjection stub does. It is never loaded at runtime and is stripped from
 * the release archive (tools/).
 */

namespace GlpiPlugin\Resources;

class ContractType extends \CommonDropdown {}
