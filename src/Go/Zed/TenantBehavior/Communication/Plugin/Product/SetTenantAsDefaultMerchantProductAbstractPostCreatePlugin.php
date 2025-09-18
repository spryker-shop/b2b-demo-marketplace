<?php

namespace Go\Zed\TenantBehavior\Communication\Plugin\Product;

use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductExtension\Dependency\Plugin\ProductAbstractPostCreatePluginInterface;

class SetTenantAsDefaultMerchantProductAbstractPostCreatePlugin extends AbstractPlugin implements ProductAbstractPostCreatePluginInterface
{
    public function postCreate(ProductAbstractTransfer $productAbstractTransfer): ProductAbstractTransfer
    {
        /** @var \Go\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface $tenantBehaviorFacade */
        $tenantBehaviorFacade = \Spryker\Zed\Kernel\Locator::getInstance()->tenantBehavior()->facade();

        $tenantReference = $tenantBehaviorFacade->getCurrentTenantReference();
        if ($tenantReference && !$productAbstractTransfer->getIdMerchant()) {
            /** @var \Spryker\Zed\Merchant\Business\MerchantFacadeInterface $merchantFacade */
            $merchantFacade = \Spryker\Zed\Kernel\Locator::getInstance()->merchant()->facade();
            $merchantTransfer = $merchantFacade->findOne((new MerchantCriteriaTransfer())->setMerchantReferences([$tenantReference]));
            if (!$merchantTransfer) {
                return$productAbstractTransfer;
            }

            $productAbstractTransfer->setIdMerchant(
                $merchantTransfer->getIdMerchant(),
            );
        }

        return $productAbstractTransfer;
    }
}
