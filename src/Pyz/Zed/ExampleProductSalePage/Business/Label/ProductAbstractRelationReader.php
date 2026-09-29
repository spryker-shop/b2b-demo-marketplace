<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleProductSalePage\Business\Label;

use Generated\Shared\Transfer\ProductLabelProductAbstractRelationsTransfer;
use Generated\Shared\Transfer\ProductLabelTransfer;
use Pyz\Zed\ExampleProductSalePage\Business\Exception\ProductLabelSaleNotFoundException;
use Pyz\Zed\ExampleProductSalePage\ExampleProductSalePageConfig;
use Pyz\Zed\ExampleProductSalePage\Persistence\ExampleProductSalePageQueryContainerInterface;
use Spryker\Zed\ProductLabel\Business\ProductLabelFacadeInterface;

class ProductAbstractRelationReader implements ProductAbstractRelationReaderInterface
{
    /**
     * @var \Pyz\Zed\ExampleProductSalePage\Persistence\ExampleProductSalePageQueryContainerInterface
     */
    protected $productSaleQueryContainer;

    /**
     * @var \Pyz\Zed\ExampleProductSalePage\ExampleProductSalePageConfig
     */
    protected $productSaleConfig;

    /**
     * @var \Spryker\Zed\ProductLabel\Business\ProductLabelFacadeInterface
     */
    protected $productLabelFacade;

    public function __construct(
        ExampleProductSalePageQueryContainerInterface $productSaleQueryContainer,
        ExampleProductSalePageConfig $productSaleConfig,
        ProductLabelFacadeInterface $productLabelFacade,
    ) {
        $this->productSaleQueryContainer = $productSaleQueryContainer;
        $this->productSaleConfig = $productSaleConfig;
        $this->productLabelFacade = $productLabelFacade;
    }

    /**
     * @return array<\Generated\Shared\Transfer\ProductLabelProductAbstractRelationsTransfer>
     */
    public function findProductLabelProductAbstractRelationChanges(): array
    {
        $result = [];

        $productLabelNewEntity = $this->getProductLabelNewEntity();

        if (!$productLabelNewEntity->getIsActive()) {
            return [];
        }

        $relationsToDeAssign = $this->findRelationsBecomingInactive($productLabelNewEntity);
        $relationsToAssign = $this->findRelationsBecomingActive($productLabelNewEntity);

        $idProductLabels = array_keys($relationsToDeAssign) + array_keys($relationsToAssign);

        foreach ($idProductLabels as $idProductLabel) {
            $result[] = $this->mapRelationTransfer($idProductLabel, $relationsToAssign, $relationsToDeAssign);
        }

        return $result;
    }

    /**
     * @throws \Pyz\Zed\ExampleProductSalePage\Business\Exception\ProductLabelSaleNotFoundException
     */
    protected function getProductLabelNewEntity(): ProductLabelTransfer
    {
        $labelNewName = $this->productSaleConfig->getLabelSaleName();
        $productLabelTransfer = $this->productLabelFacade->findLabelByLabelName($labelNewName);

        if (!$productLabelTransfer) {
            throw new ProductLabelSaleNotFoundException(sprintf(
                'Product Label "%1$s" doesn\'t exists. You can fix this problem by persisting a new Product Label entity into your database with "%1$s" name.',
                $labelNewName,
            ));
        }

        return $productLabelTransfer;
    }

    /**
     * @return array<int, array<int>>
     */
    protected function findRelationsBecomingInactive(ProductLabelTransfer $productLabelTransfer): array
    {
        $relations = [];

        $productLabelProductAbstractEntities = $this->productSaleQueryContainer
            ->queryRelationsBecomingInactive($productLabelTransfer->getIdProductLabelOrFail())
            ->find();

        foreach ($productLabelProductAbstractEntities as $productLabelProductAbstractEntity) {
            $relations[$productLabelTransfer->getIdProductLabelOrFail()][] = $productLabelProductAbstractEntity->getFkProductAbstract();
        }

        return $relations;
    }

    /**
     * @return array<int, array<int>>
     */
    protected function findRelationsBecomingActive(ProductLabelTransfer $productLabelTransfer): array
    {
        $relations = [];

        $productAbstractEntities = $this->productSaleQueryContainer
            ->queryRelationsBecomingActive($productLabelTransfer->getIdProductLabelOrFail())
            ->find();

        foreach ($productAbstractEntities as $productAbstractEntity) {
            $relations[$productLabelTransfer->getIdProductLabelOrFail()][] = $productAbstractEntity->getIdProductAbstract();
        }

        return $relations;
    }

    /**
     * @param array<int, array<int>> $relationsToAssign
     * @param array<int, array<int>> $relationsToDeAssign
     */
    protected function mapRelationTransfer(
        int $idProductLabel,
        array $relationsToAssign,
        array $relationsToDeAssign,
    ): ProductLabelProductAbstractRelationsTransfer {
        $productLabelProductAbstractRelationsTransfer = new ProductLabelProductAbstractRelationsTransfer();
        $productLabelProductAbstractRelationsTransfer->setIdProductLabel($idProductLabel);

        if (!empty($relationsToAssign[$idProductLabel])) {
            $productLabelProductAbstractRelationsTransfer->setIdsProductAbstractToAssign($relationsToAssign[$idProductLabel]);
        }

        if (!empty($relationsToDeAssign[$idProductLabel])) {
            $productLabelProductAbstractRelationsTransfer->setIdsProductAbstractToDeAssign($relationsToDeAssign[$idProductLabel]);
        }

        return $productLabelProductAbstractRelationsTransfer;
    }
}
