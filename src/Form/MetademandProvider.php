<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Metademands\Form;

use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\Provider\LeafProviderInterface;
use Glpi\FuzzyMatcher\FuzzyMatcher;
use Glpi\FuzzyMatcher\PartialMatchStrategy;
use GlpiPlugin\Metademands\Metademand;
use Override;
use GlpiPlugin\Metademands\Group;

use Session;

/** @implements LeafProviderInterface<MetademandForServiceCatalog> */
final class MetademandProvider implements LeafProviderInterface
{

    private FuzzyMatcher $matcher;

    private array $entity_restriction_cache = [];

//    private function __construct()
//    {
//        $this->matcher = new FuzzyMatcher(new PartialMatchStrategy());
//    }

    private function getCachedEntityRestriction(string $table, mixed $value, bool $is_recursive): array
    {
        $cache_key = md5(serialize([
            'table' => $table,
            'value' => $value,
            'is_recursive' => $is_recursive,
        ]));

        if (!isset($this->entity_restriction_cache[$cache_key])) {
            $this->entity_restriction_cache[$cache_key] = getEntitiesRestrictCriteria(
                table: $table,
                value: $value,
                is_recursive: $is_recursive,
            );
        }

        return $this->entity_restriction_cache[$cache_key];
    }

    #[Override]
    public function getItems(ItemRequest $item_request): array
    {
        $category_id = $item_request->getCategoryID();
        $filter = $item_request->getFilter();
        $parameters = $item_request->getFormAccessParameters();
        $this->matcher = new FuzzyMatcher(new PartialMatchStrategy());
        $metas = [];
        $category_restriction = [];
        if ($category_id !== null) {
            $category_restriction = [
                'forms_categories_id' => $category_id,
            ];
        }
        $entity_restriction = $this->getCachedEntityRestriction(
            table: Metademand::getTable(),
            value: $parameters->getSessionInfo()->getActiveEntitiesIds(),
            is_recursive: true,
        );

        $raw_forms = (new Metademand())->find(
            [
                'is_active' => 1,
                'is_template' => 0,
                'is_deleted' => 0,
            ] + $category_restriction + $entity_restriction,
            ['name']
        );

        foreach ($raw_forms as $raw_form) {
            $meta = new Metademand();
            $meta->getFromResultSet($raw_form);
            $meta->post_getFromDB();

            // Fuzzy matching
            $name = $meta->fields['name'] ?? "";
            $description = $meta->fields['description'] ?? "";
            if (!$this->matcher->match($name, $filter)
                && !$this->matcher->match($description, $filter)
            ) {
                continue;
            }

            /// Note: this is in theory less performant than applying the parameters
            // directly to the SQL query (which would require more complicated code).
            // However, the number of forms is expected to be low, so this is acceptable.
            // If performance becomes an issue, we can revisit this and/or add a cache.
            if (!Group::isUserHaveRight($meta->getID())) {
                continue;
            }

            $metas[] = $meta;
        }

        return $metas;
    }


    #[Override]
    public function getItemsLabel(): string
    {
        return Form::getTypeName(Session::getPluralNumber());
    }

    #[Override]
    public function getWeight(): int
    {
        return 15;
    }
}
