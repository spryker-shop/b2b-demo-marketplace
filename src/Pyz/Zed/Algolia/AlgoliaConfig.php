<?php

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
