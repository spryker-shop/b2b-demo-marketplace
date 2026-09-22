<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Algolia;

use SprykerEco\Zed\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    /**
     * @return array<string>
     */
    public function getProductSortingAttributes(): array
    {
        return [
            'rating',
            'abstract_name',
            'prices.eur.gross',
            'prices.eur.net',
        ];
    }

    /**
     * @return array<string>
     */
    public function getCmsPageSortingAttributes(): array
    {
        return ['name'];
    }

    /**
     * @return array<array<string>>
     */
    public function getSuggestionGenerateAttributes(): array
    {
        return [
            ['category'],
            ['attributes.brand'],
        ];
    }
}
