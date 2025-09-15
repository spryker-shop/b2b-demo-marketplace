<?php

namespace Go\Zed\GuiAssistant\Communication\Controller;

use Exception;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\ProductAbstractCollectionTransfer;
use Generated\Shared\Transfer\ProductAbstractConditionsTransfer;
use Generated\Shared\Transfer\ProductAbstractCriteriaTransfer;
use Generated\Shared\Transfer\ProductAbstractRelationsTransfer;
use Go\Zed\GuiAssistant\Business\GuiAssistantFacade;
use Go\Zed\GuiAssistant\Communication\GuiAssistantCommunicationFactory;
use Spryker\Zed\Product\Business\ProductFacade;
use Symfony\Component\HttpFoundation\Request;

class ProductAbstractsController extends AbstractResourceController
{
    public function newAction(Request $request)
    {
    }

    public function editAction(Request $request)
    {
    }

    // get collection , get item
    public function getAction(Request $request)
    {
//        try {
//            //dd((new OpenAiFactory())->createSchemaUploader()->upload());
//            dd((new OpenAiFactory())->createSchemaUploader()->listFilesInVectorStore());
//        } catch (Exception $e) {
//            dd($e->getMessage());
//        }

        /** @var GuiAssistantFacade $facade */
        $facade = $this->getFacade();
        dd($facade->getProductAbstracts(
            'GET',
            '/product-abstracts/{abstractSku}',
            [],
            ['abstractSku' => 'camera-1500'],
            []
        ));

        return;

        try {
            $this->validateResourceRequest($request);

            $productAbstractCollectionTransfer = (new ProductFacade())->getPaginatedProductAbstractSuggestions(
                $this->getOptions($request)['q'] ?? $this->getPathParameters($request)['abstractSku'],
                (new PaginationTransfer())
                    ->setPage($this->getOptions($request)['page'] ?? 1)
                    ->setMaxPerPage(5)
                    ->setLimit($this->getOptions($request)['limit'] ?? 5)
                    ->setOffset($this->getOptions($request)['offset'] ?? 0)
            );

            $foundSkus = array_map(fn($e) => $e->getSku(), $productAbstractCollectionTransfer->getProductAbstracts()->getArrayCopy());
            $includeConcretes = ($this->getOptions($request)['include'] ?? '') === 'concretes';
            $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
                ->setPagination(
                    (new PaginationTransfer())
                        ->setPage($this->getOptions($request)['page'] ?? 1)
                        ->setMaxPerPage(5)
                        ->setLimit($this->getOptions($request)['limit'] ?? 5)
                        ->setOffset($this->getOptions($request)['offset'] ?? 0)
                )
                ->setProductAbstractConditions(
                    (new ProductAbstractConditionsTransfer())
                        ->setSkus(empty($foundSkus) ? ['no-matching-skus-found'] : $foundSkus)
                )
                ->setProductAbstractRelations(
                    (new ProductAbstractRelationsTransfer())
                        ->setWithStoreRelations(true)
                        ->setWithLocalizedAttributes(true)
                        ->setWithVariants($includeConcretes)
                );

            $productAbstractCollectionTransfer = (new ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);

            return $this->jsonResponse(['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()], 200);
        } catch (Exception $e) {
            return $this->jsonResponse($this->errorJson($request, $e), 500);
        }
    }

    public function patchAction(Request $request)
    {
        try {
            $this->validateResourceRequest($request);

            $productAbstractTransfer = $this->getProductAbstractBySku($this->getPathParameters($request)['abstractSku'])->getProductAbstracts()->getIterator()->current();

            $k = (new GuiAssistantCommunicationFactory())->createProductTransferBuilder();
            $productAbstractTransfer = $k->updateProductAbstractTransferFromArray($this->getPayload($request), $productAbstractTransfer);

            $idProductAbstract = (new ProductFacade())->saveProductAbstract($productAbstractTransfer);

            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);

            return $this->jsonResponse(['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()], 200);
        } catch (\Exception $e) {
            return $this->jsonResponse($this->errorJson($request, $e), 500);
        }
    }

    public function putAction(Request $request)
    {
        try {
            $this->validateResourceRequest($request);

            $k = (new GuiAssistantCommunicationFactory())->createProductTransferBuilder();
            $productAbstractTransfer = $k->createProductAbstractTransferFromArray($this->getPayload($request));
            $concreteProductCollection = $k->createProductConcreteTransfersFromArray($this->getPayload($request));

            // CREATE
            $idProductAbstract = (new ProductFacade())->addProduct($productAbstractTransfer, $concreteProductCollection->getProducts()->getArrayCopy());

            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);

            return $this->jsonResponse(['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()], 200);
        } catch (\Exception $e) {
            return $this->jsonResponse($this->errorJson($request, $e), 500);
        }
    }

    protected function getProductAbstractById(int $idProductAbstract): ProductAbstractCollectionTransfer
    {
        $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
            ->setPagination(
                (new PaginationTransfer())
                    ->setPage(1)
                    ->setMaxPerPage(5)
                    ->setLimit(5)
                    ->setOffset(0)
            )
            ->setProductAbstractConditions(
                (new ProductAbstractConditionsTransfer())
                    ->setIds([$idProductAbstract])
            )
            ->setProductAbstractRelations(
                (new ProductAbstractRelationsTransfer())
                    ->setWithStoreRelations(true)
                    ->setWithLocalizedAttributes(true)
                    ->setWithVariants(true)
            );

        return (new ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
    }

    protected function getProductAbstractBySku(string $sku): ProductAbstractCollectionTransfer
    {
        $productAbstractCriteriaTransfer = (new ProductAbstractCriteriaTransfer())
            ->setPagination(
                (new PaginationTransfer())
                    ->setPage(1)
                    ->setMaxPerPage(5)
                    ->setLimit(5)
                    ->setOffset(0)
            )
            ->setProductAbstractConditions(
                (new ProductAbstractConditionsTransfer())
                    ->setSkus([$sku])
            )
            ->setProductAbstractRelations(
                (new ProductAbstractRelationsTransfer())
                    ->setWithStoreRelations(true)
                    ->setWithLocalizedAttributes(true)
                    ->setWithVariants(true)
            );

        return (new ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
    }

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
        return '/products/abstracts/{abstractSku}';
    }
}
