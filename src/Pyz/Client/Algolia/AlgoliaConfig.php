<?php

namespace Pyz\Client\Algolia;

use SprykerEco\Client\Algolia\AlgoliaConfig as SprykerEcoAlgoliaConfig;

class AlgoliaConfig extends SprykerEcoAlgoliaConfig
{
    /**
     * @return array<string, string>
     */
    public function getProductSortingParamToAttributeMapping(): array
    {
        return [
            'name' => 'abstract_name',
        ];
    }
}
