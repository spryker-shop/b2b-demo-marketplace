<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\CatalogPriceProductConnector\Plugin;

use Elastica\ResultSet;
use Spryker\Client\CatalogPriceProductConnector\Plugin\CurrencyAwareCatalogSearchResultFormatterPlugin as SprykerCurrencyAwareCatalogSearchResultFormatterPlugin;

class CurrencyAwareCatalogSearchResultFormatterPlugin extends SprykerCurrencyAwareCatalogSearchResultFormatterPlugin
{
    /**
     * @param \Elastica\ResultSet $searchResult
     * @param array<string, mixed> $requestParameters
     *
     * @return array<string, mixed>
     */
    protected function formatSearchResult(ResultSet $searchResult, array $requestParameters): array
    {
        $result = $this->rawCatalogSearchResultFormatterPlugin->formatResult($searchResult, $requestParameters);

        if (!$this->isPriceProductDimensionEnabled()) {
            return $this->formatSearchResultWithoutPriceDimensions($result);
        }

        $priceProductClient = $this->getFactory()->getPriceProductClient();
        $priceProductStorageClient = $this->getFactory()->getPriceProductStorageClient();
        foreach ($result as $key => $product) {
            $currentProductPriceTransfer = $this->getPriceProductAbstractTransfers(
                $product['id_product_abstract'],
                $priceProductClient,
                $priceProductStorageClient,
            );
            $result[$key]['price'] = $currentProductPriceTransfer->getPrice();
            $result[$key]['prices'] = $currentProductPriceTransfer->getPrices();
            $result[$key]['grossMargin'] = $currentProductPriceTransfer->getGrossMargin();
        }

        return $result;
    }
}
