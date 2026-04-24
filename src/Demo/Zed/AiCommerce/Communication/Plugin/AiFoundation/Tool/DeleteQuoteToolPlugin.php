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
 * @method \Pyz\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 */
class DeleteQuoteToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return 'delete_quote';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Delete an existing quote.';
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
                description: 'The ID of the quote to delete',
                isRequired: true,
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
        return $this->getBusinessFactory()->createPlaceOrderQuoteManager()->deleteQuote($arguments);
    }
}
