<?php
/*
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
  Copyright (C) 2018-2026 by the Metademands Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\ForbiddenHttpException;
use GlpiPlugin\Metademands\Basketobject;
use GlpiPlugin\Metademands\BasketobjectTranslation;
use GlpiPlugin\Metademands\Basketobjecttype;
use GlpiPlugin\Metademands\BasketobjecttypeTranslation;
use GlpiPlugin\Metademands\Field;
use GlpiPlugin\Metademands\FieldTranslation;
use GlpiPlugin\Metademands\Metademand;
use GlpiPlugin\Metademands\MetademandTranslation;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$action            = $_POST['action']            ?? '';
$translation_class = $_POST['translation_class'] ?? '';
$language          = $_POST['language']          ?? '';

$allowed_classes = [
    MetademandTranslation::class,
    FieldTranslation::class,
    BasketobjectTranslation::class,
    BasketobjecttypeTranslation::class,
];

if (!in_array($translation_class, $allowed_classes, true)) {
    throw new BadRequestHttpException();
}

$itemtype_to_tr_class = [
    Metademand::class    => MetademandTranslation::class,
    Field::class         => FieldTranslation::class,
    Basketobject::class  => BasketobjectTranslation::class,
    Basketobjecttype::class => BasketobjecttypeTranslation::class,
];

switch ($action) {
    case 'init_language':
        $items_id = (int)($_POST['items_id'] ?? 0);
        $itemtype = $_POST['itemtype'] ?? '';
        $item = getItemForItemtype($itemtype);
        if ($item === false || !$item->can($items_id, UPDATE)) {
            throw new ForbiddenHttpException();
        }
        if (empty($language)) {
            throw new BadRequestHttpException();
        }
        $existing = (new $translation_class())->find([
            'items_id' => $items_id,
            'itemtype' => $itemtype,
            'language' => $language,
        ]);
        if (empty($existing)) {
            $tr = new $translation_class();
            $tr->add([
                'items_id'     => $items_id,
                'itemtype'     => $itemtype,
                'key'          => 'name',
                'language'     => $language,
                'translations' => ['one' => ''],
            ]);
        }
        break;

    case 'save_translations':
        $meta_translation_id = (int)($_POST['meta_translation_id'] ?? 0);
        $meta_tr = new $translation_class();
        if (!$meta_tr->getFromDB($meta_translation_id)) {
            throw new BadRequestHttpException();
        }
        $parent_items_id = (int)$meta_tr->fields['items_id'];
        $parent_itemtype = $meta_tr->fields['itemtype'];
        $parent_item = getItemForItemtype($parent_itemtype);
        if ($parent_item === false || !$parent_item->can($parent_items_id, UPDATE)) {
            throw new ForbiddenHttpException();
        }

        $translations_input = $_POST['translations'] ?? [];
        if (!is_array($translations_input)) {
            throw new BadRequestHttpException();
        }

        foreach ($translations_input as $entry) {
            $entry_itemtype = $entry['itemtype'] ?? '';
            $entry_items_id = (int)($entry['items_id'] ?? 0);
            $entry_key      = trim($entry['key'] ?? '');
            $entry_language = $entry['language'] ?? '';
            $entry_value    = $entry['translations']['one'] ?? '';

            if (empty($entry_itemtype) || $entry_items_id <= 0 || empty($entry_key) || empty($entry_language)) {
                continue;
            }

            $tr_class = $itemtype_to_tr_class[$entry_itemtype] ?? null;
            if ($tr_class === null) {
                continue;
            }

            // Security: verify this entry belongs to the item we have edit rights on
            if ($entry_itemtype === $parent_itemtype) {
                if ($entry_items_id !== $parent_items_id) {
                    continue;
                }
            } elseif ($entry_itemtype === Field::class && $parent_itemtype === Metademand::class) {
                $field = new Field();
                if (!$field->getFromDB($entry_items_id)
                    || (int)$field->fields['plugin_metademands_metademands_id'] !== $parent_items_id) {
                    continue;
                }
            } else {
                continue;
            }

            $existing = (new $tr_class())->find([
                'items_id' => $entry_items_id,
                'itemtype' => $entry_itemtype,
                'key'      => $entry_key,
                'language' => $entry_language,
            ]);
            $tr = new $tr_class();
            if (!empty($existing)) {
                $first = reset($existing);
                $tr->update([
                    'id'           => $first['id'],
                    'translations' => ['one' => (string)$entry_value],
                ]);
            } else {
                $tr->add([
                    'items_id'     => $entry_items_id,
                    'itemtype'     => $entry_itemtype,
                    'key'          => $entry_key,
                    'language'     => $entry_language,
                    'translations' => ['one' => (string)$entry_value],
                ]);
            }
        }
        break;

    case 'delete_language':
        $items_id = (int)($_POST['items_id'] ?? 0);
        $itemtype = $_POST['itemtype'] ?? '';
        $item = getItemForItemtype($itemtype);
        if ($item === false || !$item->can($items_id, UPDATE)) {
            throw new ForbiddenHttpException();
        }
        if (empty($language)) {
            throw new BadRequestHttpException();
        }

        // Delete main item's translations
        $existing = (new $translation_class())->find([
            'items_id' => $items_id,
            'itemtype' => $itemtype,
            'language' => $language,
        ]);
        $tr = new $translation_class();
        foreach ($existing as $id => $data) {
            $tr->delete(['id' => $id], true);
        }

        // For Metademand: also delete all Field-level translations belonging to this metademand
        if ($itemtype === Metademand::class) {
            $field_tr  = new FieldTranslation();
            $field_obj = new Field();
            foreach ($field_obj->find(['plugin_metademands_metademands_id' => $items_id]) as $field_id => $field_data) {
                $existing_field_trs = $field_tr->find([
                    'items_id' => $field_id,
                    'itemtype' => Field::class,
                    'language' => $language,
                ]);
                foreach ($existing_field_trs as $ftr_id => $ftr_data) {
                    $field_tr->delete(['id' => $ftr_id], true);
                }
            }
        }
        break;

    default:
        throw new BadRequestHttpException();
}

Html::back();
