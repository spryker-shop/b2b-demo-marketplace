<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Glue\QuoteRequestsRestApi;

use Spryker\Glue\ConfigurableBundlesRestApi\Plugin\QuoteRequestsRestApi\ConfiguredBundleRestQuoteRequestAttributesExpanderPlugin;
use Spryker\Glue\QuoteRequestsRestApi\QuoteRequestsRestApiDependencyProvider as SprykerQuoteRequestsRestApiDependencyProvider;

class QuoteRequestsRestApiDependencyProvider extends SprykerQuoteRequestsRestApiDependencyProvider
{
    /**
     * @return array<\Spryker\Glue\QuoteRequestsRestApiExtension\Dependency\Plugin\RestQuoteRequestAttributesExpanderPluginInterface>
     */
    protected function getRestQuoteRequestAttributesExpanderPlugins(): array
    {
        return [
            new ConfiguredBundleRestQuoteRequestAttributesExpanderPlugin(),
        ];
    }
}
