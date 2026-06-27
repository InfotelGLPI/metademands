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

namespace GlpiPlugin\Metademands\Tests;

use GlpiPlugin\Metademands\Wizard;
use PHPUnit\Framework\TestCase;

class WizardTest extends TestCase
{
    public function testExtractRichtextFieldUploadsReturnsEmptyWhenNoFieldKey(): void
    {
        $result = Wizard::extractRichtextFieldUploads([]);

        $this->assertSame([], $result['_filename']);
        $this->assertSame([], $result['_prefix_filename']);
        $this->assertSame([], $result['_tag_filename']);
    }

    public function testExtractRichtextFieldUploadsReturnsEmptyWhenFieldIsNotArray(): void
    {
        $result = Wizard::extractRichtextFieldUploads(['_field' => 'not-an-array']);

        $this->assertSame([], $result['_filename']);
        $this->assertSame([], $result['_prefix_filename']);
        $this->assertSame([], $result['_tag_filename']);
    }

    public function testExtractRichtextFieldUploadsSingleFieldSingleFile(): void
    {
        $post = [
            '_field'        => [42 => [0 => 'image.png']],
            '_prefix_field' => [42 => [0 => 'abc123']],
            '_tag_field'    => [42 => [0 => '#tag-uuid#']],
        ];

        $result = Wizard::extractRichtextFieldUploads($post);

        $this->assertSame(['image.png'], $result['_filename']);
        $this->assertSame(['abc123'], $result['_prefix_filename']);
        $this->assertSame(['#tag-uuid#'], $result['_tag_filename']);
    }

    public function testExtractRichtextFieldUploadsMultipleFieldsMultipleFiles(): void
    {
        $post = [
            '_field'        => [
                10 => [0 => 'photo.jpg', 1 => 'doc.pdf'],
                20 => [0 => 'chart.png'],
            ],
            '_prefix_field' => [
                10 => [0 => 'pre1', 1 => 'pre2'],
                20 => [0 => 'pre3'],
            ],
            '_tag_field'    => [
                10 => [0 => '#tag1#', 1 => '#tag2#'],
                20 => [0 => '#tag3#'],
            ],
        ];

        $result = Wizard::extractRichtextFieldUploads($post);

        $this->assertSame(['photo.jpg', 'doc.pdf', 'chart.png'], $result['_filename']);
        $this->assertSame(['pre1', 'pre2', 'pre3'], $result['_prefix_filename']);
        $this->assertSame(['#tag1#', '#tag2#', '#tag3#'], $result['_tag_filename']);
    }

    public function testExtractRichtextFieldUploadsSkipsEmptyFilenames(): void
    {
        $post = [
            '_field'        => [5 => [0 => '', 1 => 'valid.png', 2 => '']],
            '_prefix_field' => [5 => [0 => 'p0', 1 => 'p1', 2 => 'p2']],
            '_tag_field'    => [5 => [0 => '#t0#', 1 => '#t1#', 2 => '#t2#']],
        ];

        $result = Wizard::extractRichtextFieldUploads($post);

        $this->assertSame(['valid.png'], $result['_filename']);
        $this->assertSame(['p1'], $result['_prefix_filename']);
        $this->assertSame(['#t1#'], $result['_tag_filename']);
    }

    public function testExtractRichtextFieldUploadsUsesEmptyStringForMissingPrefixAndTag(): void
    {
        $post = [
            '_field' => [7 => [0 => 'file.txt']],
        ];

        $result = Wizard::extractRichtextFieldUploads($post);

        $this->assertSame(['file.txt'], $result['_filename']);
        $this->assertSame([''], $result['_prefix_filename']);
        $this->assertSame([''], $result['_tag_filename']);
    }

    public function testExtractRichtextFieldUploadsSkipsNonArrayFieldEntries(): void
    {
        $post = [
            '_field'        => [3 => 'not-an-array', 4 => [0 => 'ok.png']],
            '_prefix_field' => [4 => [0 => 'px']],
            '_tag_field'    => [4 => [0 => '#tx#']],
        ];

        $result = Wizard::extractRichtextFieldUploads($post);

        $this->assertSame(['ok.png'], $result['_filename']);
        $this->assertSame(['px'], $result['_prefix_filename']);
        $this->assertSame(['#tx#'], $result['_tag_filename']);
    }
}
