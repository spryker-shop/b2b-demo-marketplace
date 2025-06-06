<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Client\Search;

use Spryker\Client\Catalog\Plugin\Config\CatalogSearchConfigBuilder;
use Spryker\Client\Kernel\Container;
use Spryker\Client\MerchantProductOfferSearch\Plugin\Search\MerchantNameSearchConfigExpanderPlugin;
use Spryker\Client\MerchantProductSearch\Plugin\Search\MerchantProductMerchantNameSearchConfigExpanderPlugin;
use Spryker\Client\ProductSearchConfigStorage\Plugin\Config\ProductSearchConfigExpanderPlugin;
use Spryker\Client\Search\Dependency\Plugin\SearchConfigBuilderInterface;
use Spryker\Client\Search\SearchDependencyProvider as SprykerSearchDependencyProvider;
use Spryker\Client\SearchElasticsearch\Plugin\ElasticsearchSearchAdapterPlugin;
use Spryker\Client\SearchElasticsearch\Plugin\ElasticsearchSearchContextExpanderPlugin;
use Spryker\Client\SearchHttp\Plugin\Search\SearchHttpSearchAdapterPlugin;
use Spryker\Client\SearchHttp\Plugin\Search\SearchHttpSearchContextExpanderPlugin;

class SearchDependencyProvider extends SprykerSearchDependencyProvider
{
    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Search\Dependency\Plugin\SearchConfigBuilderInterface
     */
    protected function createSearchConfigBuilderPlugin(Container $container): SearchConfigBuilderInterface // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        return new CatalogSearchConfigBuilder();
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchConfigExpanderPluginInterface>
     */
    protected function createSearchConfigExpanderPlugins(Container $container): array
    {
        $searchConfigExpanderPlugins = parent::createSearchConfigExpanderPlugins($container);

        $searchConfigExpanderPlugins[] = new ProductSearchConfigExpanderPlugin();
        $searchConfigExpanderPlugins[] = new MerchantProductMerchantNameSearchConfigExpanderPlugin();
        $searchConfigExpanderPlugins[] = new MerchantNameSearchConfigExpanderPlugin();

        return $searchConfigExpanderPlugins;
    }

    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchAdapterPluginInterface>
     */
    protected function getClientAdapterPlugins(): array
    {
        return [
            new SearchHttpSearchAdapterPlugin(),
            new ElasticsearchSearchAdapterPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Client\SearchExtension\Dependency\Plugin\SearchContextExpanderPluginInterface>
     */
    protected function getSearchContextExpanderPlugins(): array
    {
        return [
            new SearchHttpSearchContextExpanderPlugin(),
            new ElasticsearchSearchContextExpanderPlugin(),
        ];
    }
}
