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

namespace GlpiPlugin\Metademands\Fields;

/**
 * Information of the form: the presentation of the metademand, shown above its first block,
 * and as the first tab when the blocks are shown as tabs (the header of a GLPI form).
 *
 * One per metademand, in the block 0 that no other field uses (Forminformation::BLOCK): the
 * wizard shows the blocks by number, so that it comes first without a case of its own. It
 * shows and is set as an information (content, kind, colour, icon) and is never part of the
 * ticket.
 */
class Forminformation extends Information
{
    /** Block of the information of the form, before the blocks numbered from 1 */
    public const BLOCK = 0;

    public static function getTypeName($nb = 0)
    {
        return __('Form information', 'metademands');
    }
}
