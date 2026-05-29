<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameter;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Demo\Zed\AiCommerce\Business\AiCommerceBusinessFactory getBusinessFactory()
 * @method \Demo\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \Demo\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class UpdateCartItemToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'update_cart_item';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Update item quantity or remove item from cart. Set quantity to 0 to remove the item.';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                name: 'idQuote',
                type: 'integer',
                description: 'The quote ID',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'sku',
                type: 'string',
                description: 'The product SKU',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'quantity',
                type: 'integer',
                description: 'The new quantity. Set to 0 to remove the item.',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'groupKey',
                type: 'string',
                description: 'The group key of the item (for disambiguation)',
                isRequired: false,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param mixed ...$arguments
     */
    public function execute(...$arguments): mixed
    {
        /** @var array<string, mixed> $arguments */
        return $this->getBusinessFactory()->createPlaceOrderCartManager()->updateCartItem($arguments);
    }
}
