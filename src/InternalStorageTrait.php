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

/**
 * Rows the plugin writes and reads itself: the answers stored for the tickets, the values of
 * the drafts, of the saved forms and of the step forms, the basket lines...
 *
 * The core offers every CommonDBTM to its generic routes (REST API, generic form and search
 * routes), which check the right of the class and, for an item, its entity: these tables carry
 * no entity and no owner the core would look at, so the right of the class gave every row of
 * every user and every entity. No generic access to them: the plugin reads and writes them
 * directly (find(), add(), update(), delete()), once the parent object (ticket, draft, saved
 * form, step form) is checked.
 */
trait InternalStorageTrait
{
    public static function canView(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canUpdate(): bool
    {
        return false;
    }

    public static function canDelete(): bool
    {
        return false;
    }

    public static function canPurge(): bool
    {
        return false;
    }
}
