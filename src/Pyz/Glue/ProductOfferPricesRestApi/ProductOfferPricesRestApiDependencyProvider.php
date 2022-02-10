<?php

namespace Pyz\Glue\ProductOfferPricesRestApi;

use Spryker\Glue\PriceProductOfferVolumesRestApi\Plugin\RestProductOfferPricesAttributesMapperPlugin;
use Spryker\Glue\ProductOfferPricesRestApi\ProductOfferPricesRestApiDependencyProvider as SprykerProductPricesRestApiDependencyProvider;

class ProductOfferPricesRestApiDependencyProvider extends SprykerProductPricesRestApiDependencyProvider
{
    /**
     * @return \Spryker\Glue\ProductOfferPricesRestApiExtension\Dependency\Plugin\RestProductOfferPricesAttributesMapperPluginInterface[]
     */
    protected function getRestProductOfferPricesAttributesMapperPlugins(): array
    {
        return [
            new RestProductOfferPricesAttributesMapperPlugin(),
        ];
    }
}
