<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SequenceNumber\Communication\Plugin\AclMerchantPortal;

use Generated\Shared\Transfer\AclEntityMetadataConfigTransfer;
use Spryker\Zed\AclMerchantPortalExtension\Dependency\Plugin\AclEntityConfigurationExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * Sequence numbers are technical counters (e.g. order invoice references) and must be writable from any ACL context,
 * including Merchant Portal requests that trigger OMS commands.
 *
 * @method \Spryker\Zed\SequenceNumber\SequenceNumberConfig getConfig()
 * @method \Spryker\Zed\SequenceNumber\Business\SequenceNumberFacadeInterface getFacade()
 */
class SequenceNumberAclEntityConfigurationExpanderPlugin extends AbstractPlugin implements AclEntityConfigurationExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds `SpySequenceNumber` to the ACL entity allow list.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\AclEntityMetadataConfigTransfer $aclEntityMetadataConfigTransfer
     *
     * @return \Generated\Shared\Transfer\AclEntityMetadataConfigTransfer
     */
    public function expand(AclEntityMetadataConfigTransfer $aclEntityMetadataConfigTransfer): AclEntityMetadataConfigTransfer
    {
        $aclEntityMetadataConfigTransfer->addAclEntityAllowListItem('Orm\Zed\SequenceNumber\Persistence\SpySequenceNumber');

        return $aclEntityMetadataConfigTransfer;
    }
}
