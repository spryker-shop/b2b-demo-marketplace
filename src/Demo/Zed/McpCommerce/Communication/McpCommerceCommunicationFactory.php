<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;

/**
 * @method \Demo\Zed\McpCommerce\Business\McpCommerceFacadeInterface getFacade()
 * @method \Demo\Zed\McpCommerce\McpCommerceConfig getConfig()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface getRepository()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface getEntityManager()
 */
class McpCommerceCommunicationFactory extends AbstractCommunicationFactory
{
}
