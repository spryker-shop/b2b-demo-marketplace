<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SalesInvoice;

use Spryker\Zed\MerchantSalesInvoice\Communication\Plugin\SalesInvoice\MerchantOrderInvoiceExistenceCheckStrategyPlugin;
use Spryker\Zed\MerchantSalesInvoice\Communication\Plugin\SalesInvoice\MerchantOrderInvoiceRenderOrderExpanderPlugin;
use Spryker\Zed\MerchantSalesInvoice\Communication\Plugin\SalesInvoice\MerchantOrderInvoicesExpanderPlugin;
use Spryker\Zed\MerchantSalesInvoice\Communication\Plugin\SalesInvoice\MerchantReferenceOrderInvoiceBeforeSavePlugin;
use Spryker\Zed\SalesInvoice\SalesInvoiceDependencyProvider as SprykerSalesInvoiceDependencyProvider;

class SalesInvoiceDependencyProvider extends SprykerSalesInvoiceDependencyProvider
{
    /**
     * @return array<\Spryker\Zed\SalesInvoiceExtension\Dependency\Plugin\OrderInvoiceBeforeSavePluginInterface>
     */
    protected function getOrderInvoiceBeforeSavePlugins(): array
    {
        return [
            new MerchantReferenceOrderInvoiceBeforeSavePlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SalesInvoiceExtension\Dependency\Plugin\OrderInvoicesExpanderPluginInterface>
     */
    protected function getOrderInvoicesExpanderPlugins(): array
    {
        return [
            new MerchantOrderInvoicesExpanderPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SalesInvoiceExtension\Dependency\Plugin\OrderInvoiceExistenceCheckStrategyPluginInterface>
     */
    protected function getOrderInvoiceExistenceCheckStrategyPlugins(): array
    {
        return [
            new MerchantOrderInvoiceExistenceCheckStrategyPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\SalesInvoiceExtension\Dependency\Plugin\OrderInvoiceRenderOrderExpanderPluginInterface>
     */
    protected function getOrderInvoiceRenderOrderExpanderPlugins(): array
    {
        return [
            new MerchantOrderInvoiceRenderOrderExpanderPlugin(),
        ];
    }
}
