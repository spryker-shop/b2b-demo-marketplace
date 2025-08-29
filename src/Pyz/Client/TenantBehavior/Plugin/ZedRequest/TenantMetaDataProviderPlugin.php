<?php

namespace Pyz\Client\TenantBehavior\Plugin\ZedRequest;

use Generated\Shared\Transfer\TenantTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ZedRequestExtension\Dependency\Plugin\MetaDataProviderPluginInterface;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

/**
 * @method \Pyz\Client\TenantBehavior\TenantBehaviorFactory getFactory()
 * @method \Pyz\Client\TenantBehavior\TenantBehaviorClient getClient()
 */
class TenantMetaDataProviderPlugin extends AbstractPlugin implements MetaDataProviderPluginInterface
{
    public function getRequestMetaData(TransferInterface $transfer)
    {
        return (new TenantTransfer())->setId($this->getClient()->getCurrentTenantId());
    }
}
