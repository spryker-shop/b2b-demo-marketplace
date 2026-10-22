<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SalesInvoice\Communication\Plugin\AclMerchantPortal;

use Generated\Shared\Transfer\AclEntityMetadataConfigTransfer;
use Generated\Shared\Transfer\AclEntityMetadataTransfer;
use Generated\Shared\Transfer\AclEntityParentMetadataTransfer;
use Spryker\Zed\AclMerchantPortalExtension\Dependency\Plugin\AclEntityConfigurationExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Overrides the metadata registered by `\Spryker\Zed\SalesInvoice\Communication\Plugin\AclMerchantPortal\SalesInvoiceAclEntityConfigurationExpanderPlugin`:
 * `SpySalesOrderInvoice` is registered as a regular child of `SpySalesOrder` (like `SpySalesOrderTotals`) instead of a sub-entity.
 * A sub-entity inherits the `create` permission of its root entity, and merchants have no `create` permission on `SpySalesOrder`,
 * so merchant users could not generate invoices when shipping an order from the Merchant Portal.
 * Must be registered after the core plugin.
 *
 * @method \Pyz\Zed\SalesInvoice\SalesInvoiceConfig getConfig()
 * @method \Spryker\Zed\SalesInvoice\Business\SalesInvoiceFacadeInterface getFacade()
 * @method \Spryker\Zed\SalesInvoice\Communication\SalesInvoiceCommunicationFactory getFactory()
 */
class SalesInvoiceAclEntityConfigurationExpanderPlugin extends AbstractPlugin implements AclEntityConfigurationExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AclEntityMetadataConfigTransfer $aclEntityMetadataConfigTransfer
     *
     * @return \Generated\Shared\Transfer\AclEntityMetadataConfigTransfer
     */
    public function expand(AclEntityMetadataConfigTransfer $aclEntityMetadataConfigTransfer): AclEntityMetadataConfigTransfer
    {
        $aclEntityMetadataConfigTransfer
            ->getAclEntityMetadataCollectionOrFail()
            ->addAclEntityMetadata(
                'Orm\Zed\SalesInvoice\Persistence\SpySalesOrderInvoice',
                (new AclEntityMetadataTransfer())
                    ->setEntityName('Orm\Zed\SalesInvoice\Persistence\SpySalesOrderInvoice')
                    ->setParent((new AclEntityParentMetadataTransfer())->setEntityName('Orm\Zed\Sales\Persistence\SpySalesOrder')),
            );

        return $aclEntityMetadataConfigTransfer;
    }
}
