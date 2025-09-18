<?php

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Business;

use Generated\Shared\Transfer\LocalizedAttributesTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\PriceProductCriteriaTransfer;
use Generated\Shared\Transfer\PriceProductDimensionTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteConditionsTransfer;
use Generated\Shared\Transfer\ProductConcreteCriteriaTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StockTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Go\Zed\GuiAssistant\Business\Request\Request;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\Product\Persistence\SpyProductAttributeKey;
use Orm\Zed\Product\Persistence\SpyProductAttributeKeyQuery;
use Orm\Zed\Product\Persistence\SpyProductQuery;
use Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeQuery;
use Orm\Zed\ProductAttribute\Persistence\SpyProductManagementAttributeValueQuery;
use Spryker\Service\UtilEncoding\UtilEncodingService;
use Spryker\Zed\Kernel\Business\AbstractFacade;
use Spryker\Zed\PriceProduct\Business\PriceProductFacade;
use Spryker\Shared\PriceProduct\PriceProductConfig;
use Spryker\Zed\Product\Business\ProductFacade;

/**
 * @method \Go\Zed\GuiAssistant\Business\GuiAssistantBusinessFactory getFactory()
 */
class GuiAssistantFacade extends AbstractFacade implements GuiAssistantFacadeInterface
{
    protected const OPENAPI_LOCATION = APPLICATION_ROOT_DIR . '/src/Go/Zed/GuiAssistant/chat_openapi.yaml';

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
            case 'PATCH/product-abstracts/{abstractSku}/concretes/{concreteSku}':
            case 'PUT/product-abstracts/{abstractSku}/concretes':
            case 'POST/product-abstracts/{abstractSku}/concretes':
                return $this->putPatchProductConcrete($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-abstracts/{abstractSku}/concretes/{concreteSku}':
                return $this->getProductConcretes($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/stores':
                return $this->getStores($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);
            case 'GET/product-attributes':
                return $this->getProductAttributes($httpMethod, $schemaPath, $queryParams, $pathParams, $payload);

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
            $productAbstractCollection = $this->filterProductAbstractCollectionArray($productAbstractCollectionTransfer->toArray());
            $productAbstractCollection = $this->addAbstractPrices($productAbstractCollection);

            return ['status' => 'ok', 'result' => $productAbstractCollection];
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

            $productAbstractTransfer = $this->getProductAbstractBySku($pathParams['abstractSku']);
            $productAbstractTransfer = $this->getFactory()->createProductTransferBuilder()->updateProductAbstractTransferFromArray($payload, $productAbstractTransfer);
            $idProductAbstract = (new \Spryker\Zed\Product\Business\ProductFacade())->saveProductAbstract($productAbstractTransfer);

            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);
            $productAbstractCollection = $this->filterProductAbstractCollectionArray($productAbstractCollectionTransfer->toArray());
            $productAbstractCollection = $this->addAbstractPrices($productAbstractCollection);

            return ['status' => 'ok', 'result' => $productAbstractCollection];
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
            if (count($payload['concretes']) < 1) {
                throw new \InvalidArgumentException('At least one concrete product must be provided during abstract creation.');
            }

            $concreteAttributeKeys = null;
            foreach($payload['concretes'] as $concrete) {
                if (count($concrete['attributes']) < 1) {
                    throw new \InvalidArgumentException('At least one attribute must be provided for each concrete product during abstract creation.');
                }

                if ($concreteAttributeKeys === null) {
                    $concreteAttributeKeys = array_keys($concrete['attributes']);
                } elseif (array_diff($concreteAttributeKeys, array_keys($concrete['attributes']))) {
                    throw new \InvalidArgumentException('All concrete products must have the same set of attributes during abstract creation.');
                }
            }

            $productAbstractTransfer = $this->getFactory()->createProductTransferBuilder()->createProductAbstractTransferFromArray($payload);
            $concreteProductCollection = $this->getFactory()->createProductTransferBuilder()->createProductConcreteTransfersFromArray($payload);

            // CREATE
            $idProductAbstract = (new \Spryker\Zed\Product\Business\ProductFacade())->addProduct($productAbstractTransfer, $concreteProductCollection->getProducts()->getArrayCopy());

            $productAbstractCollectionTransfer = $this->getProductAbstractById($idProductAbstract);
            $productAbstractCollection = $this->filterProductAbstractCollectionArray($productAbstractCollectionTransfer->toArray());
            $productAbstractCollection = $this->addAbstractPrices($productAbstractCollection);

            return ['status' => 'ok', 'result' => $productAbstractCollection];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }

    protected function addAbstractPrices(array $productAbstractCollection): array
    {
        foreach($productAbstractCollection['product_abstracts'] as $key => $productAbstractArray) {
            $prices = $this->getFactory()->createProductTransferBuilder()->getAbstractDefaultPrices($productAbstractArray['id_product_abstract'], true, false);
            $productAbstractCollection['product_abstracts'][$key]['prices'] = $prices;
        }

        return $productAbstractCollection;
    }

    public function putPatchProductConcrete(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $concreteSku = $pathParams['concreteSku'] ?? $payload['sku'];
            $productAbstractTransfer = $this->getProductAbstractBySku($pathParams['abstractSku']);

            $this->validateConcreteAttributeKeys($productAbstractTransfer->getIdProductAbstract(), array_keys($payload['attributes'] ?? []));

            // Facade wrongly returns the Attributes parameter
            $value = (new UtilEncodingService())->decodeJson($productAbstractTransfer->getAttributes() ?? '{}', true);
            $productAbstractTransfer->setAttributes(!is_array($value) ? [] : $value);

            $productConcreteTransfer = (new ProductConcreteTransfer())
                ->setImageSets(new \ArrayObject())
                ->setIsActive(true)
                ->setSku($concreteSku)
                ->setFkProductAbstract($productAbstractTransfer->getIdProductAbstract());

            if ($httpMethod === 'PATCH') {
                $productTransfer = (new ProductFacade())->getProductConcrete($concreteSku);
                if (empty($productTransfer->getIdProductConcrete())) {
                    throw new \InvalidArgumentException("Product concrete with SKU {$concreteSku} not found.");
                }

                $productConcreteTransfer->setIdProductConcrete($productTransfer->getIdProductConcrete());

                $productConcreteTransfer->setAttributes($productTransfer->getAttributes() ?? new \ArrayObject());
                $productConcreteTransfer->setLocalizedAttributes($productTransfer->getLocalizedAttributes() ?? new \ArrayObject());
                $productConcreteTransfer->setPrices($productTransfer->getPrices() ?? new \ArrayObject());
                $productConcreteTransfer->setStocks($productTransfer->getStocks() ?? new \ArrayObject());
            }

            $productConcreteTransfer = $this->getFactory()->createProductTransferBuilder()->updateProductConcreteTransferFromArray($payload, $productConcreteTransfer);

            $idProductAbstract = (new \Spryker\Zed\Product\Business\ProductFacade())->saveProduct($productAbstractTransfer,  [$productConcreteTransfer]);

            $productTransfer = (new ProductFacade())->getProductConcrete($concreteSku);

            $prices = $this->getFactory()->createProductTransferBuilder()->getConcreteDefaultPrices($productTransfer->getFkProductAbstract(), $productTransfer->getIdProductConcrete(), true, false);
            $productArray = $this->filterProductConcreteArray($productTransfer->toArray());
            $productArray['prices'] = $prices;

            return ['status' => 'ok', 'result' => [$productArray]];
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

                $prices = $this->getFactory()->createProductTransferBuilder()->getConcreteDefaultPrices($productTransfer->getFkProductAbstract(), $productTransfer->getIdProductConcrete(), true, false);
                $productArray = $this->filterProductConcreteArray($productTransfer->toArray());
                $productArray['prices'] = $prices;

                $productConcreteTransfers[] = $productArray;
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
    protected function getProductAbstractBySku(string $sku): ProductAbstractTransfer
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
                    ->setWithTaxSet(true)
            );

        $productAbstractCollectionTransfer = (new \Spryker\Zed\Product\Business\ProductFacade())->getProductAbstractCollection($productAbstractCriteriaTransfer);
        if ($productAbstractCollectionTransfer->getProductAbstracts()->count() === 0) {
            throw new \InvalidArgumentException("Product abstract with SKU {$sku} not found.");
        }

        foreach($productAbstractCollectionTransfer->getProductAbstracts() as $productAbstract) {
            foreach($productAbstractCollectionTransfer->getProductTaxSets() as $productTaxSet) {
                if ($productAbstract->getSku() === $productTaxSet->getProductAbstractSku()) {
                    $productAbstract->setIdTaxSet($productTaxSet->getTaxSet()->getIdTaxSet());
                }
            }
        }

        foreach($productAbstractCollectionTransfer->getProductAbstracts() as $productAbstract) {
            $storeIds = [];
            $productAbstract->setStoreRelation($productAbstract->getStoreRelation() ?? new StoreRelationTransfer());
            foreach($productAbstract->getStoreRelation()->getStores() ?? [] as $store) {
                $storeIds[] = $store->getIdStore();
            }
            $productAbstract->getStoreRelation()->setIdStores($storeIds);
        }

        return $productAbstractCollectionTransfer->getProductAbstracts()->getIterator()->current();
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

    protected function filterProductAbstractCollectionArray(array $productAbstractCollection): array
    {
        $productAbstractAllowList = ['id_product_abstract', 'sku', 'name', 'approval_status', 'new_from', 'new_to', 'localized_attributes'];
        $productConcreteAllowList = ['sku', 'attributes', 'abstract_sku', 'is_active', 'localized_attributes'];

        $productAbstractCollection['pagination'] = array_filter($productAbstractCollection['pagination'] ?? [], fn($v) => $v !== null);
        unset($productAbstractCollection['product_tax_sets']);

        foreach($productAbstractCollection['product_abstracts'] as $key => $productAbstract) {
            $newProductAbstract = array_intersect_key($productAbstract, array_flip($productAbstractAllowList));
            $newProductAbstract['store_relation']['stores'] = [];
            foreach($productAbstract['store_relation']['stores'] ?? [] as $store) {
                $newProductAbstract['store_relation']['stores'][] = array_filter($store , fn($v) => !empty($v));
            }

            $productAbstractCollection['product_abstracts'][$key] = $newProductAbstract;
        }

        foreach($productAbstractCollection['product_concretes'] as $concretesKey => $productConcretes) {
            foreach($productConcretes['product_concretes'] as $key => $productConcrete) {
                $newProductConcrete = array_intersect_key($productConcrete, array_flip($productConcreteAllowList));
                $newProductConcrete['stores'] = [];
                foreach($productConcrete['stores'] ?? [] as $store) {
                    $newProductConcrete['stores'][] = array_filter($store , fn($v) => !empty($v));
                }

                $productAbstractCollection['product_concretes'][$concretesKey]['product_concretes'][$key] = $newProductConcrete;
            }
        }

        return $productAbstractCollection;
    }

    protected function filterProductConcreteArray(array $productConcrete): array
    {
        $productConcreteAllowList = ['sku', 'attributes', 'abstract_sku', 'is_active', 'localized_attributes', 'stocks'];
        $productConcreteStockAllowList = ['quantity', 'is_never_out_of_stock'];

        $newProductConcrete = array_intersect_key($productConcrete, array_flip($productConcreteAllowList));
        foreach($newProductConcrete['stocks'] ?? [] as $key => $stock) {
            $newProductConcrete['stocks'][$key] = array_intersect_key($stock, array_flip($productConcreteStockAllowList));
        }

        return $newProductConcrete;
    }

    public function getProductAttributes(string $httpMethod, string $resourcePath, array $queryParams, array $pathParams, array $payload): array
    {
        try {
            $this->validateResourceRequest($httpMethod, $resourcePath, $queryParams, $pathParams, $payload);

            $isConcrete = !empty($queryParams['concreteSku']);

            $result = [];

            $concreteSuperAttributes = [];
            if ($isConcrete) {
                $concrete = SpyProductQuery::create()->findOneBySku($queryParams['concreteSku']);
                if (empty($concrete)) {
                    throw new \InvalidArgumentException("Product concrete with SKU {$queryParams['concreteSku']} not found.");
                }
                $attributes = array_keys(json_decode($concrete->getAttributes() ?? '{}', true));
                /** @var SpyProductAttributeKey[] $concreteSuperAttributes */
                $concreteSuperAttributes = SpyProductAttributeKeyQuery::create()
                    ->filterByIsSuper(true)
                    ->filterByKey($attributes, \Propel\Runtime\ActiveQuery\Criteria::IN)
                    ->find()
                    ->toArray();

                foreach($concreteSuperAttributes as $attribute) {
                    $result[$attribute['IdProductAttributeKey']] = [
                        'is_mandatory' => true,
                        'attribute_key' => $attribute['Key'],
                        'choices' => null,
                        'free_text' => null,
                    ];
                }
            }

            /** @var SpyProductAttributeKey[] $attributes Super attributes during abstract creation, optional attributes during concrete addition */
            $attributes = SpyProductAttributeKeyQuery::create()->filterByIsSuper(!$isConcrete)->find()->toArray();

            foreach($attributes as $attribute) {
                $result[$attribute['IdProductAttributeKey']] = [
                    'is_mandatory' => $result[$attribute['IdProductAttributeKey']] ?? false,
                    'attribute_key' => $attribute['Key'],
                    'choices' => null,
                    'free_text' => null,
                ];
            }

            foreach($result as $attributeKeyId => $attributeKey) {
                $productManagementAttribute = SpyProductManagementAttributeQuery::create()->findOneByFkProductAttributeKey($attributeKeyId);
                if ($productManagementAttribute === null) {
                    unset($result[$attributeKeyId]);
                    continue;
                }
                $choices = SpyProductManagementAttributeValueQuery::create()->filterByFkProductManagementAttribute($productManagementAttribute->getIdProductManagementAttribute())->find()->toArray();
                foreach($choices as $key => $choice) {
                    $choices[$key] = $choice['Value'];
                }

                $result[$attributeKeyId]['free_text'] = (bool)$productManagementAttribute->getAllowInput();
                $result[$attributeKeyId]['choices'] = array_filter($choices);

                if ($result[$attributeKeyId]['free_text'] === false && empty($result[$attributeKeyId]['choices'])) {
                    unset($result[$attributeKeyId]);
                    continue;
                }
            }

            return ['status' => 'ok', 'result' => array_values($result)];
        } catch (\Exception $e) {
            return $this->errorArray($httpMethod, $resourcePath, $queryParams, $pathParams, $payload, $e);
        }
    }

    protected function validateConcreteAttributeKeys(int $idProductAbstract, array $actualProductAttributeKeys): void
    {
        $existingProduct = SpyProductQuery::create()->findOneByFkProductAbstract($idProductAbstract);
        $existingProductAttributes = $this->getProductAttributes('GET', '/product-attributes', ['concreteSku' => $existingProduct->getSku()], [], [])['result'];

        foreach($actualProductAttributeKeys as $actualProductAttributeKey) {
            $found = false;
            foreach($existingProductAttributes as $existingProductAttribute) {
                if ($existingProductAttribute['attribute_key'] === $actualProductAttributeKey) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new \InvalidArgumentException("Attribute key: {$actualProductAttributeKey} is not a valid attribute key.");
            }
        }

        foreach($existingProductAttributes as $existingProductAttribute) {
            if ($existingProductAttribute['is_mandatory'] && !in_array($existingProductAttribute['attribute_key'], $actualProductAttributeKeys)) {
                throw new \InvalidArgumentException("Mandatory attribute key: {$existingProductAttribute['attribute_key']} is missing.");
            }
        }

    }

}
