<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\PriceProduct\Business;

use Demo\Zed\PriceProduct\Business\Model\PriceGrouper;
use Demo\Zed\PriceProduct\Business\Model\Product\PriceProductMapper;
use Demo\Zed\PriceProduct\Business\Model\Product\PriceProductMapperInterface;
use Demo\Zed\PriceProduct\Business\Model\Product\PriceProductStoreWriter;
use Demo\Zed\PriceProduct\Business\Model\Reader;
use Demo\Zed\PriceProduct\Dependency\Facade\PriceProductToPriceFacadeInterface;
use Demo\Zed\PriceProduct\PriceProductDependencyProvider;
use Spryker\Zed\PriceProduct\Business\Model\PriceGrouperInterface;
use Spryker\Zed\PriceProduct\Business\Model\Product\PriceProductStoreWriterInterface;
use Spryker\Zed\PriceProduct\Business\Model\ReaderInterface;
use Spryker\Zed\PriceProduct\Business\PriceProductBusinessFactory as SprykerPriceProductBusinessFactory;

/**
 * @method \Spryker\Zed\PriceProduct\Persistence\PriceProductRepositoryInterface getRepository()
 * @method \Spryker\Zed\PriceProduct\Persistence\PriceProductEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\PriceProduct\PriceProductConfig getConfig()
 * @method \Spryker\Zed\PriceProduct\Persistence\PriceProductQueryContainerInterface getQueryContainer()
 */
class PriceProductBusinessFactory extends SprykerPriceProductBusinessFactory
{
    public function createPriceProductMapper(): PriceProductMapperInterface
    {
        return new PriceProductMapper(
            $this->getCurrencyFacade(),
            $this->createPriceTypeMapper(),
            $this->getPriceFacade(),
            $this->getConfig(),
        );
    }

    public function createPriceGrouper(): PriceGrouperInterface
    {
        return new PriceGrouper(
            $this->createReaderModel(),
            $this->createPriceProductMapper(),
            $this->getConfig(),
        );
    }

    public function createReaderModel(): ReaderInterface
    {
        return new Reader(
            $this->getProductFacade(),
            $this->createPriceTypeReader(),
            $this->createPriceProductConcreteReader(),
            $this->createPriceProductAbstractReader(),
            $this->createProductCriteriaBuilder(),
            $this->createPriceProductMapper(),
            $this->getConfig(),
            $this->getPriceProductService(),
            $this->getRepository(),
            $this->createPriceProductExpander(),
        );
    }

    public function getPriceFacade(): PriceProductToPriceFacadeInterface
    {
        return $this->getProvidedDependency(PriceProductDependencyProvider::FACADE_PRICE);
    }

    public function createPriceProductStoreWriter(): PriceProductStoreWriterInterface
    {
        return new PriceProductStoreWriter(
            $this->getQueryContainer(),
            $this->getEntityManager(),
            $this->getRepository(),
            $this->createPriceProductStoreWriterPluginExecutor(),
            $this->getConfig(),
            $this->createPriceProductDefaultWriter(),
            $this->createPriceDataChecksumGenerator(),
            $this->getUtilEncodingService(),
        );
    }
}
