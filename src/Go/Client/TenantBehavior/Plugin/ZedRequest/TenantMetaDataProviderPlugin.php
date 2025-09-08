<?php

namespace Go\Client\TenantBehavior\Plugin\ZedRequest;

use Generated\Shared\Transfer\TenantTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ZedRequestExtension\Dependency\Plugin\MetaDataProviderPluginInterface;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

/**
 * @method \Go\Client\TenantBehavior\TenantBehaviorFactory getFactory()
 * @method \Go\Client\TenantBehavior\TenantBehaviorClient getClient()
 */
class TenantMetaDataProviderPlugin extends AbstractPlugin implements MetaDataProviderPluginInterface
{
    public function getRequestMetaData(TransferInterface $transfer)
    {
        return (new TenantTransfer())->setIdentifier($this->getClient()->getCurrentTenantReference());
    }
}
