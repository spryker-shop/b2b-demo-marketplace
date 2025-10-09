<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\ProductOffer\Communication\Plugin\Product;

use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStockTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductExtension\Dependency\Plugin\ProductConcreteCreatePluginInterface;

/**
 * @method \Go\Zed\ProductOffer\GoProductOfferConfig getConfig()
 */
class AutoCreateProductOfferProductConcreteCreatePlugin extends AbstractPlugin implements ProductConcreteCreatePluginInterface
{
    /**
     * {@inheritDoc}
     * - Creates a product offer automatically for newly created products.
     * - Uses the configured default merchant reference.
     * - Skips creation if no default merchant is configured.
     * - Logs errors but doesn't stop product creation process.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function create(ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer
    {
        $defaultMerchantReference = $this->getTenantBehaviorFacade()->getCurrentTenantReference();

        if (!$defaultMerchantReference) {
            return $productConcreteTransfer;
        }

        $this->createProductOffer($productConcreteTransfer, $defaultMerchantReference);

        return $productConcreteTransfer;
    }

    protected function createProductOffer(ProductConcreteTransfer $productConcreteTransfer, string $merchantReference): ProductOfferTransfer
    {
        $productOfferReference = sprintf('auto-%s-%s', $merchantReference, $productConcreteTransfer->getIdProductConcrete());

        $productOfferTransfer = $this->getProductOfferFacade()->findOne((new ProductOfferCriteriaTransfer())->setProductOfferReference($productOfferReference));

        if ($productOfferTransfer) {
            return $productOfferTransfer;
        }
        $merchantTransfer = $this->getMerchantFacade()->findOne((new MerchantCriteriaTransfer())->setMerchantReferences([$merchantReference]));

        $productOfferTransfer = (new ProductOfferTransfer())
            ->setProductOfferReference($productOfferReference)
            ->setMerchantReference($merchantReference)
            ->setConcreteSku($productConcreteTransfer->getSku())
            ->setIdProductConcrete($productConcreteTransfer->getIdProductConcrete())
            ->setIsActive(true)
            ->setApprovalStatus('approved');

        $productOfferTransfer->setStores(
            new \ArrayObject($this->getStoreFacade()->getAllStores()),
        );

        $idMappedStocks = [];
        foreach ($productConcreteTransfer->getStocks() as $stockTransfer) {
            $idMappedStocks[$stockTransfer->getFkStock()] = $stockTransfer;
        }

        foreach ($merchantTransfer->getStocks() as $stockTransfer) {
            $quantity = 0;
            $isNeverOutOfStock = false;
            if (isset($idMappedStocks[$stockTransfer->getIdStock()])) {
                $quantity = $idMappedStocks[$stockTransfer->getIdStock()]->getQuantity();
                $isNeverOutOfStock = $idMappedStocks[$stockTransfer->getIdStock()]->getIsNeverOutOfStock();
            }
            $productOfferTransfer->addProductOfferStock(
                (new ProductOfferStockTransfer())
                    ->setStock($stockTransfer)
                    ->setQuantity($quantity)
                    ->setIsNeverOutOfStock($isNeverOutOfStock),
            );
        }

        return $this->getProductOfferFacade()->create($productOfferTransfer);
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

    protected function getStoreFacade(): \Spryker\Zed\Store\Business\StoreFacadeInterface
    {
        /** @var \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface $locator */
        $locator = \Spryker\Zed\Kernel\Locator::getInstance();

        return $locator->store()->facade();
    }

    protected function getMerchantFacade(): \Spryker\Zed\Merchant\Business\MerchantFacadeInterface
    {
        /** @var \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface $locator */
        $locator = \Spryker\Zed\Kernel\Locator::getInstance();

        return $locator->merchant()->facade();
    }
}
