<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Demo\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \Demo\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class PlaceOrderToolSetPlugin extends AbstractPlugin implements ToolSetPluginInterface
{
    protected const string TOOL_SET_PLACE_ORDER = 'place_order_tools';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::TOOL_SET_PLACE_ORDER;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface>
     */
    public function getTools(): array
    {
        /** @var \Demo\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory $factory */
        $factory = $this->getFactory();

        return [
            $factory->createCreateQuoteToolPlugin(),
            $factory->createGetCustomerDetailsToolPlugin(),
            $factory->createGetCheckoutDataToolPlugin(),
            $factory->createGetQuoteSummaryToolPlugin(),
            $factory->createAddItemToCartToolPlugin(),
            $factory->createUpdateCartItemToolPlugin(),
            $factory->createSetCartNoteToolPlugin(),
            $factory->createManageVoucherCodeToolPlugin(),
            $factory->createPlaceOrderToolPlugin(),
            $factory->createDeleteQuoteToolPlugin(),
        ];
    }
}
