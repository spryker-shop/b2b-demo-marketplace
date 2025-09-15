<?php

namespace Go\Zed\GuiAssistant\Communication\Controller;

use Exception;
use Spryker\Zed\Product\Business\ProductFacade;
use Symfony\Component\HttpFoundation\Request;

class ProductConcretesController extends AbstractResourceController
{

    // get collection , get item
    public function getAction(Request $request)
    {
        try {
            if (empty($this->getPathParameters($request)['abstractSku'])) {
                throw new Exception('abstractSku path parameter is required');
            }

            $this->validateResourceRequest($request);

            $productConcreteSkus = array_keys((new ProductFacade())->suggestProductConcrete(
                $this->getOptions($request)['q'] ?? $this->getPathParameters($request)['concreteSku'],
            ));

            $productConcreteTransfers = [];
            foreach($productConcreteSkus as $sku) {
                $productTransfer = (new ProductFacade())->getProductConcrete($sku);
                if ($productTransfer->getAbstractSku() !== $this->getPathParameters($request)['abstractSku']) {
                    continue;
                }

                $productConcreteTransfers[] = $productTransfer->toArray();
            }

            return $this->jsonResponse(['status' => 'ok', 'result' => $productConcreteTransfers], 200);
        } catch (Exception $e) {
            return $this->jsonResponse($this->errorJson($request, $e), 500);
        }
    }
//
//    public function patchAction(Request $request)
//    {
//        try {
//            $this->validateResourceRequest($request);
//
//            $productAbstractTransfer = $this->getProductAbstractBySku($this->getPathParameters($request)['abstractSku'])->getProductAbstracts()->getIterator()->current();
//
//            $k = (new GuiAssistantCommunicationFactory())->createProductTransferBuilder();
//            $productAbstractTransfer = $k->updateProductAbstractTransferFromArray($this->getPayload($request), $productAbstractTransfer);
//
//            $idProductAbstract = (new ProductFacade())->saveProductAbstract($productAbstractTransfer);
//
//            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);
//
//            return $this->jsonResponse(['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()], 200);
//        } catch (\Exception $e) {
//            return $this->jsonResponse($this->errorJson($request, $e), 500);
//        }
//    }
//
//    public function putAction(Request $request)
//    {
//        try {
//            $this->validateResourceRequest($request);
//
//            $k = (new GuiAssistantCommunicationFactory())->createProductTransferBuilder();
//            $productAbstractTransfer = $k->createProductAbstractTransferFromArray($this->getPayload($request));
//            $concreteProductCollection = $k->createProductConcreteTransfersFromArray($this->getPayload($request));
//
//            // CREATE
//            $idProductAbstract = (new ProductFacade())->addProduct($productAbstractTransfer, $concreteProductCollection->getProducts()->getArrayCopy());
//
//            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);
//
//            return $this->jsonResponse(['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()], 200);
//        } catch (\Exception $e) {
//            return $this->jsonResponse($this->errorJson($request, $e), 500);
//        }
//    }

//    protected function getProductAbstractById(int $idProductAbstract): ProductAbstractCollectionTransfer
//    {
//        $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
//            ->setPagination(
//                (new PaginationTransfer())
//                    ->setPage(1)
//                    ->setMaxPerPage(5)
//                    ->setLimit(5)
//                    ->setOffset(0)
//            )
//            ->setProductAbstractConditions(
//                (new ProductAbstractConditionsTransfer())
//                    ->setIds([$idProductAbstract])
//            )
//            ->setProductAbstractRelations(
//                (new ProductAbstractRelationsTransfer())
//                    ->setWithStoreRelations(true)
//                    ->setWithLocalizedAttributes(true)
//                    ->setWithVariants(true)
//            );
//
//        return (new ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
//    }
//
//    protected function getProductAbstractBySku(string $sku): ProductAbstractCollectionTransfer
//    {
//        $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
//            ->setPagination(
//                (new PaginationTransfer())
//                    ->setPage(1)
//                    ->setMaxPerPage(5)
//                    ->setLimit(5)
//                    ->setOffset(0)
//            )
//            ->setProductAbstractConditions(
//                (new ProductAbstractConditionsTransfer())
//                    ->setSkus([$sku])
//            )
//            ->setProductAbstractRelations(
//                (new ProductAbstractRelationsTransfer())
//                    ->setWithStoreRelations(true)
//                    ->setWithLocalizedAttributes(true)
//                    ->setWithVariants(true)
//            );
//
//        return (new ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
//    }

    protected function errorJson(Request $request, Exception $error)
    {
        return [
            'status' => 'error',
            'error' => $error->getMessage(),
            'result' => null,
            'query' => $this->getOptions($request),
            'pathParams' => $this->getPathParameters($request),
            'payload' => $this->getPayload($request),
        ];
    }

    protected function getYmlLocation(): string
    {
        return __DIR__ . '/../../chat_openapi.yaml';
    }

    protected function getResourcePath(): string
    {
        return '/products/abstracts/{abstractSku}/concretes/{concreteSku}';
    }
}
