<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation;

use Demo\Shared\AiCommerce\AiCommerceConstants;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolSetPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Demo\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 */
class PlaceOrderToolSetPlugin extends AbstractPlugin implements ToolSetPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return AiCommerceConstants::TOOL_SET_PLACE_ORDER;
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
        return [
            $this->getFactory()->createCreateQuoteToolPlugin(),
            $this->getFactory()->createGetCheckoutDataToolPlugin(),
            $this->getFactory()->createGetQuoteSummaryToolPlugin(),
            $this->getFactory()->createAddItemToCartToolPlugin(),
            $this->getFactory()->createUpdateCartItemToolPlugin(),
            $this->getFactory()->createSetCartNoteToolPlugin(),
            $this->getFactory()->createManageVoucherCodeToolPlugin(),
            $this->getFactory()->createPlaceOrderToolPlugin(),
            $this->getFactory()->createDeleteQuoteToolPlugin(),
        ];
    }
}
