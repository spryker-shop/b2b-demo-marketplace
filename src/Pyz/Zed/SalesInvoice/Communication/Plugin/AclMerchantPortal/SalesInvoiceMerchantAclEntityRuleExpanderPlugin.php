<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SalesInvoice\Communication\Plugin\AclMerchantPortal;

use Generated\Shared\Transfer\AclEntityRuleTransfer;
use Spryker\Zed\AclMerchantPortalExtension\Dependency\Plugin\MerchantAclEntityRuleExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Allows merchant users to create, read and update order invoices of their own orders.
 * Needed because `MerchantInvoice/Generate` runs inside the Merchant Portal request when the merchant ships the order.
 *
 * @method \Pyz\Zed\SalesInvoice\SalesInvoiceConfig getConfig()
 * @method \Spryker\Zed\SalesInvoice\Business\SalesInvoiceFacadeInterface getFacade()
 * @method \Spryker\Zed\SalesInvoice\Communication\SalesInvoiceCommunicationFactory getFactory()
 */
class SalesInvoiceMerchantAclEntityRuleExpanderPlugin extends AbstractPlugin implements MerchantAclEntityRuleExpanderPluginInterface
{
    protected const string SCOPE_INHERITED = 'inherited';

    protected const int OPERATION_MASK_READ = 0b1;

    protected const int OPERATION_MASK_CREATE = 0b10;

    protected const int OPERATION_MASK_UPDATE = 0b100;

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\AclEntityRuleTransfer> $aclEntityRuleTransfers
     *
     * @return array<\Generated\Shared\Transfer\AclEntityRuleTransfer>
     */
    public function expand(array $aclEntityRuleTransfers): array
    {
        $aclEntityRuleTransfers[] = (new AclEntityRuleTransfer())
            ->setEntity('Orm\Zed\SalesInvoice\Persistence\SpySalesOrderInvoice')
            ->setScope(static::SCOPE_INHERITED)
            ->setPermissionMask(static::OPERATION_MASK_CREATE | static::OPERATION_MASK_READ | static::OPERATION_MASK_UPDATE);

        return $aclEntityRuleTransfers;
    }
}
