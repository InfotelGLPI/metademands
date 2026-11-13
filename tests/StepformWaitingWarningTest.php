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

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\Stepform;

/**
 * Banner of the step forms awaiting the user (Stepform::showWaitingWarning()): the step form
 * being filled on the page is not announced as waiting.
 */
class StepformWaitingWarningTest extends DbTestCase
{
    private function render(int $current = 0): string
    {
        ob_start();
        Stepform::showWaitingWarning($current);

        return (string) ob_get_clean();
    }

    public function testTheStepFormBeingFilledIsNotAnnounced(): void
    {
        $this->login();
        $metademand = $this->createItem(Metademand::class, [
            'name'             => 'Steps',
            'entities_id'      => $this->getTestRootEntity(true),
            'object_to_create' => 'Ticket',
            'type'             => 0,
        ]);
        $stepform = $this->createItem(Stepform::class, [
            'plugin_metademands_metademands_id' => $metademand->getID(),
            'users_id'                          => 0,
            'users_id_dest'                     => \Session::getLoginUserID(),
            'groups_id_dest'                    => 0,
            'block_id'                          => 1,
            'date'                              => $_SESSION['glpi_currenttime'],
        ], ['date']);

        // Elsewhere, the waiting form is announced
        $this->assertStringContainsString('front/stepform.php', $this->render());
        // While it is being filled, nothing left to announce
        $this->assertSame('', trim($this->render($stepform->getID())));

        // A form to complete in which the user also took part is not listed again with
        // the ones in progress
        $this->createItem(\GlpiPlugin\Metademands\Stepform_Actor::class, [
            'plugin_metademands_stepforms_id' => $stepform->getID(),
            'users_id'                        => \Session::getLoginUserID(),
        ]);
        $this->assertArrayHasKey($stepform->getID(), Stepform::getWaitingFormsByMaker());
        $this->assertSame([], Stepform::getWaitingFormsByMaker(Stepform::getWaitingForms()));
    }
}
