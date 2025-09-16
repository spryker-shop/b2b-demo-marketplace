<?php

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Business;

use Go\Zed\GuiAssistant\Business\Request\Request;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Spryker\Zed\Product\Business\ProductFacade;

/**
 * @method \Go\Zed\GuiAssistant\Business\GuiAssistantBusinessFactory getFactory()
 */
class GuiAssistantFacade extends AbstractFacade implements GuiAssistantFacadeInterface
{
    protected const OPENAPI_LOCATION = APPLICATION_ROOT_DIR . '/src/Go/Zed/GuiAssistant/chat_openapi.txt';

    public function routeEndpoint(string $httpMethod, string $schemaPath, array $queryParams, array $pathParams, array $payload)
    {
        switch($httpMethod.$schemaPath) {
            case 'GET/product-abstracts':
                return $this->getProductAbstracts($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'PUT/product-abstracts':
            case 'POST/product-abstracts':
                return $this->putProductAbstract($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}':
                return $this->getProductAbstracts($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'PATCH/product-abstracts/{abstractSku}':
                return $this->patchProductAbstract($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}/concretes':
                return $this->getProductConcretes($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}/concretes/{concreteSku}':
                return $this->getProductConcretes($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/stores':
                return $this->getStores($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);

            default:
                return ['error' => sprintf('Unknown endpoint: %s %s ', $httpMethod, $schemaPath)];
        }
    }

    /**
     * @inheritDoc
     */
    public function getProductAbstracts(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $productAbstractCollectionTransfer = (new \Spryker\Zed\Product\Business\ProductFacade())->getPaginatedProductAbstractSuggestions(
                $queryParams['q'] ?? $pathParams['abstractSku'],
                (new \Generated\Shared\Transfer\PaginationTransfer())
                    ->setPage($queryParams['page'] ?? 1)
                    ->setMaxPerPage(5)
                    ->setLimit($queryParams['limit'] ?? 5)
                    ->setOffset($queryParams['offset'] ?? 0)
            );

            $foundSkus = array_map(fn($e) => $e->getSku(), $productAbstractCollectionTransfer->getProductAbstracts()->getArrayCopy());
            $includeConcretes = ($queryParams['include'] ?? '') === 'concretes';
            $productAbstractCriteriaTransfer = (new \Generated\Shared\Transfer\ProductAbstractCriteriaTransfer())
                ->setPagination(
                    (new \Generated\Shared\Transfer\PaginationTransfer())
                        ->setPage($queryParams['page'] ?? 1)
                        ->setMaxPerPage(5)
                        ->setLimit($queryParams['limit'] ?? 5)
                        ->setOffset($queryParams['offset'] ?? 0)
                )
                ->setProductAbstractConditions(
                    (new \Generated\Shared\Transfer\ProductAbstractConditionsTransfer())
                        ->setSkus(empty($foundSkus) ? ['no-matching-skus-found'] : $foundSkus)
                )
                ->setProductAbstractRelations(
                    (new \Generated\Shared\Transfer\ProductAbstractRelationsTransfer())
                        ->setWithStoreRelations(true)
                        ->setWithLocalizedAttributes(true)
                        ->setWithVariants($includeConcretes)
                );

            $productAbstractCollectionTransfer = (new \Spryker\Zed\Product\Business\ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);

            return ['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function patchProductAbstract(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $productAbstractTransfer = $this->getProductAbstractBySku($pathParams['abstractSku'])->getProductAbstracts()->getIterator()->current();

            $productAbstractTransfer = $this->getFactory()->createProductTransferBuilder()->updateProductAbstractTransferFromArray($payload, $productAbstractTransfer);

            $idProductAbstract = (new \Spryker\Zed\Product\Business\ProductFacade())->saveProductAbstract($productAbstractTransfer);

            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);

            return ['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function putProductAbstract(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $productAbstractTransfer = $this->getFactory()->createProductTransferBuilder()->createProductAbstractTransferFromArray($payload);
            $concreteProductCollection = $this->getFactory()->createProductTransferBuilder()->createProductConcreteTransfersFromArray($payload);

            // CREATE
            $idProductAbstract = (new \Spryker\Zed\Product\Business\ProductFacade())->addProduct($productAbstractTransfer, $concreteProductCollection->getProducts()->getArrayCopy());

            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);

            return ['status' => 'ok', 'result' => $productAbstractCollectionTransfer->toArray()];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }


    public function getProductConcretes(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload)
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $productConcreteSkus = array_keys((new ProductFacade())->suggestProductConcrete(
                $queryParams['q'] ?? $pathParams['concreteSku'],
            ));
            $productConcreteTransfers = [];
            foreach($productConcreteSkus as $sku) {
                $productTransfer = (new ProductFacade())->getProductConcrete($sku);
                if ($productTransfer->getAbstractSku() !== $pathParams['abstractSku']) {
                    continue;
                }

                $productConcreteTransfers[] = $productTransfer->toArray();
            }

            return ['status' => 'ok', 'result' => $productConcreteTransfers];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }

    public function getStores(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload)
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $stores = (new \Spryker\Zed\Store\Business\StoreFacade())->getAllStores();
            $stores = array_map(fn($storeTransfer) => [
                'storeName' => $storeTransfer->getName(),
                'availableCurrencyCodes' => $storeTransfer->getAvailableCurrencyIsoCodes(),
                'availableLocaleNames' => array_values($storeTransfer->getAvailableLocaleIsoCodes()),
            ], $stores);

            return ['status' => 'ok', 'result' => $stores];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }

    /**
     * Helper method to get product abstract by ID
     */
    protected function getProductAbstractById(int $idProductAbstract): \Generated\Shared\Transfer\ProductAbstractCollectionTransfer
    {
        $productAbstractCriteriaTransfer = (new \Generated\Shared\Transfer\ProductAbstractCriteriaTransfer())
            ->setPagination(
                (new \Generated\Shared\Transfer\PaginationTransfer())
                    ->setPage(1)
                    ->setMaxPerPage(5)
                    ->setLimit(5)
                    ->setOffset(0)
            )
            ->setProductAbstractConditions(
                (new \Generated\Shared\Transfer\ProductAbstractConditionsTransfer())
                    ->setIds([$idProductAbstract])
            )
            ->setProductAbstractRelations(
                (new \Generated\Shared\Transfer\ProductAbstractRelationsTransfer())
                    ->setWithStoreRelations(true)
                    ->setWithLocalizedAttributes(true)
                    ->setWithVariants(true)
            );

        return (new \Spryker\Zed\Product\Business\ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
    }

    /**
     * Helper method to get product abstract by SKU
     */
    protected function getProductAbstractBySku(string $sku): \Generated\Shared\Transfer\ProductAbstractCollectionTransfer
    {
        $productAbstractCriteriaTransfer = (new \Generated\Shared\Transfer\ProductAbstractCriteriaTransfer())
            ->setPagination(
                (new \Generated\Shared\Transfer\PaginationTransfer())
                    ->setPage(1)
                    ->setMaxPerPage(5)
                    ->setLimit(5)
                    ->setOffset(0)
            )
            ->setProductAbstractConditions(
                (new \Generated\Shared\Transfer\ProductAbstractConditionsTransfer())
                    ->setSkus([$sku])
            )
            ->setProductAbstractRelations(
                (new \Generated\Shared\Transfer\ProductAbstractRelationsTransfer())
                    ->setWithStoreRelations(true)
                    ->setWithLocalizedAttributes(true)
                    ->setWithVariants(true)
            );

        return (new \Spryker\Zed\Product\Business\ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
    }

    /**
     * Helper method to create error response array
     */
    protected function errorArray(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload, \Exception $error): array
    {
        return [
            'status' => 'error',
            'error' => $error->getMessage(),
            'result' => null,
            'httpMethod' => $httpMethod,
            'resourcePath' => $resourcePath,
            'query' => $queryParams,
            'pathParams' => $pathParams,
            'payload' => $payload,
        ];
    }

    protected function validateResourceRequest(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): void
    {
        // Extract all path parameters from resourcePath (e.g., {abstractSku}, {concreteSku})
        preg_match_all('/\{([^}]+)\}/', $resourcePath, $matches);
        $requiredPathParams = $matches[1] ?? [];

        // Check if all required path parameters are provided
        foreach ($requiredPathParams as $requiredParam) {
            if (!array_key_exists($requiredParam, $pathParams)) {
                throw new \InvalidArgumentException("Missing required path parameter: {$requiredParam}");
            }
        }

        $endpoint = $resourcePath;

        foreach($pathParams as $pathParamKey => $pathParamValue) {
            $endpoint = preg_replace('/\{' . preg_quote($pathParamKey, '/') . '\}/', (string)$pathParamValue, $endpoint);
        }

        $validator = (new \League\OpenAPIValidation\PSR7\ValidatorBuilder)->fromYamlFile(static::OPENAPI_LOCATION)->getRequestValidator();
        $validator->validate(new Request($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $endpoint));
    }
}
