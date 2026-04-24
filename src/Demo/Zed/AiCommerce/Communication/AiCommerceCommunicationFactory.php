<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Communication;

use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\AddItemToCartToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\CreateQuoteToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\DeleteQuoteToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetCheckoutDataToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetCustomerDetailsToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\GetQuoteSummaryToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\ManageVoucherCodeToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\PlaceOrderToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\SetCartNoteToolPlugin;
use Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\Tool\UpdateCartItemToolPlugin;
use Spryker\Zed\AiFoundation\Dependency\Tools\ToolPluginInterface;
use SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory as SprykerFeatureAiCommerceCommunicationFactory;

/**
 * @method \Pyz\Zed\AiCommerce\AiCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\AiCommerce\Persistence\AiCommerceRepositoryInterface getRepository()
 */
class AiCommerceCommunicationFactory extends SprykerFeatureAiCommerceCommunicationFactory
{
    public function createCreateQuoteToolPlugin(): ToolPluginInterface
    {
        return new CreateQuoteToolPlugin();
    }

    public function createDeleteQuoteToolPlugin(): ToolPluginInterface
    {
        return new DeleteQuoteToolPlugin();
    }

    public function createGetQuoteSummaryToolPlugin(): ToolPluginInterface
    {
        return new GetQuoteSummaryToolPlugin();
    }

    public function createAddItemToCartToolPlugin(): ToolPluginInterface
    {
        return new AddItemToCartToolPlugin();
    }

    public function createUpdateCartItemToolPlugin(): ToolPluginInterface
    {
        return new UpdateCartItemToolPlugin();
    }

    public function createSetCartNoteToolPlugin(): ToolPluginInterface
    {
        return new SetCartNoteToolPlugin();
    }

    public function createManageVoucherCodeToolPlugin(): ToolPluginInterface
    {
        return new ManageVoucherCodeToolPlugin();
    }

    public function createPlaceOrderToolPlugin(): ToolPluginInterface
    {
        return new PlaceOrderToolPlugin();
    }

    public function createGetCheckoutDataToolPlugin(): ToolPluginInterface
    {
        return new GetCheckoutDataToolPlugin();
    }

    public function createGetCustomerDetailsToolPlugin(): ToolPluginInterface
    {
        return new GetCustomerDetailsToolPlugin();
    }
}
