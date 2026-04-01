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
 * @method \SprykerFeature\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class SetCartNoteToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'set_cart_note';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Set a note on the entire quote or a specific item. Omit sku to set quote-level note. Provide sku to set item-level note.';
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
                name: 'cartNote',
                type: 'string',
                description: 'The note text to set',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'customerReference',
                type: 'string',
                description: 'The customer reference (e.g. DE--21)',
                isRequired: true,
            ),
            new ToolParameter(
                name: 'sku',
                type: 'string',
                description: 'The product SKU. If provided, sets note on specific item. If omitted, sets note on entire quote.',
                isRequired: false,
            ),
            new ToolParameter(
                name: 'groupKey',
                type: 'string',
                description: 'The group key of the item (for disambiguation when same SKU appears multiple times)',
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
        return $this->getBusinessFactory()->createPlaceOrderCartManager()->setCartNote($arguments);
    }
}
