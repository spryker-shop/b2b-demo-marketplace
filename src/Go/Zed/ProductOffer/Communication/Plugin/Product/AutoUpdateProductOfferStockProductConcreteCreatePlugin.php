<?php

namespace Go\Zed\ProductOffer\Communication\Plugin\Product;

use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductOfferCriteriaTransfer;
use Orm\Zed\ProductOfferStock\Persistence\SpyProductOfferStockQuery;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

class AutoUpdateProductOfferStockProductConcreteCreatePlugin extends AbstractPlugin implements \Spryker\Zed\Product\Dependency\Plugin\ProductConcretePluginUpdateInterface
{

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     *@see \Spryker\Zed\Product\ProductDependencyProvider
     *
     */
    public function update(ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer
    {
        $defaultMerchantReference = $this->getTenantBehaviorFacade()->getCurrentTenantReference();

        if (!$defaultMerchantReference) {
            return $productConcreteTransfer;
        }

        $this->updateProductOfferStocks($productConcreteTransfer, $defaultMerchantReference);

        return $productConcreteTransfer;
    }

    protected function updateProductOfferStocks(ProductConcreteTransfer $productConcreteTransfer, string $merchantReference): void
    {
        $productOfferReference = sprintf('auto-%s-%s', $merchantReference, $productConcreteTransfer->getIdProductConcrete());

        $productOfferTransfer = $this->getProductOfferFacade()->findOne((new ProductOfferCriteriaTransfer())->setProductOfferReference($productOfferReference));

        if (!$productOfferTransfer) {
            return;
        }

        $idMappedStocks = [];
        foreach ($productConcreteTransfer->getStocks() as $stockTransfer) {
            $idMappedStocks[$stockTransfer->getFkStock()] = $stockTransfer;
        }

        $productOfferStockEntities = SpyProductOfferStockQuery::create()
            ->filterByFkStock_In(array_keys($idMappedStocks))
            ->filterByFkProductOffer($productOfferTransfer->getIdProductOffer())
            ->find();

        foreach ($productOfferStockEntities as $productOfferStockEntity) {
            $productOfferStockEntity->setQuantity($idMappedStocks[$productOfferStockEntity->getFkStock()]->getQuantity());
            $productOfferStockEntity->setIsNeverOutOfStock($idMappedStocks[$productOfferStockEntity->getFkStock()]->getIsNeverOutOfStock());
        }
        $productOfferStockEntities->save();
    }

    /**
     * @return \Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface
     */
    protected function getProductOfferFacade(): \Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface
    {
        /** @var \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface $locator */
        $locator = \Spryker\Zed\Kernel\Locator::getInstance();

        return $locator->productOffer()->facade();
    }

    /**
     * @return \Go\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface
     */
    protected function getTenantBehaviorFacade(): \Go\Zed\TenantBehavior\Business\TenantBehaviorFacadeInterface
    {
        /** @var \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface $locator */
        $locator = \Spryker\Zed\Kernel\Locator::getInstance();

        return $locator->tenantBehavior()->facade();
    }
}
