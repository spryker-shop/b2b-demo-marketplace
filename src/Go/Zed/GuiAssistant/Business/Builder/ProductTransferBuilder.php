<?php

namespace Go\Zed\GuiAssistant\Business\Builder;

use Generated\Shared\Transfer\CurrencyCriteriaTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\LocaleCriteriaTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductCriteriaTransfer;
use Generated\Shared\Transfer\PriceProductDimensionTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StoreCriteriaTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Stock\Persistence\SpyStock;
use Orm\Zed\Stock\Persistence\SpyStockQuery;
use Orm\Zed\Tax\Persistence\SpyTaxSet;
use Orm\Zed\Tax\Persistence\SpyTaxSetQuery;
use Spryker\Service\UtilEncoding\UtilEncodingService;
use Spryker\Shared\PriceProduct\PriceProductConfig;
use Spryker\Zed\Currency\Business\CurrencyFacade;
use Spryker\Zed\Locale\Business\LocaleFacade;
use Spryker\Zed\PriceProduct\Business\PriceProductFacade;
use Spryker\Zed\Stock\Persistence\StockQueryContainer;
use Spryker\Zed\Store\Business\StoreFacade;

class ProductTransferBuilder
{
    protected const PRODUCT_ABSTRACT_DEFAULT_IS_ACTIVE = true;
    protected const PRODUCT_CONCRETE_DEFAULT_IS_ACTIVE = true;
    protected const PRODUCT_ABSTRACT_DEFAULT_TAX_SET = 1;

    /** @var LocaleTransfer[] */
    protected array $locales;

    /** @var PriceTypeTransfer[]  */
    protected array $priceTypes;

    /** @var CurrencyTransfer[] */
    protected array $currencies;

    /** @var StoreTransfer[] */
    protected array $stores;

    public function __construct()
    {
        $this->locales = (new LocaleFacade())->getLocaleCollection(new LocaleCriteriaTransfer());
        $this->currencies = (new CurrencyFacade())->getCurrencyCollection(new CurrencyCriteriaTransfer())->getCurrencies()->getArrayCopy();
        $stores = (new StoreFacade())->getStoreCollection(new StoreCriteriaTransfer());
        $this->stores = [];
        /** @var StoreTransfer $storeTransfer */
        foreach($stores->getStores() as $storeTransfer) {
            $this->stores[$storeTransfer->getName()] = $storeTransfer;
        }

        $this->priceTypes = [];
        $priceTypes = (new PriceProductFacade())->getPriceTypeValues();
        /** @var PriceTypeTransfer $priceType */
        foreach ($priceTypes as $priceType) {
            $this->priceTypes[$priceType->getName()] = $priceType;
        }
    }

        public function updateProductAbstractTransferFromArray(array $data, ProductAbstractTransfer $productAbstractTransfer): ProductAbstractTransfer
        {
            // Facade wrongly returns the Attributes parameter
            $value = (new UtilEncodingService())->decodeJson($productAbstractTransfer->getAttributes() ?? '{}', true);
            $productAbstractTransfer->setAttributes(!is_array($value) ? [] : $value);

            if (array_key_exists('newFrom', $data)) {
                $productAbstractTransfer->setNewFrom($data['newFrom'] ?? null);
            }
            if (array_key_exists('newTo', $data)) {
                $productAbstractTransfer->setNewTo($data['newTo'] ?? null);
            }
            if (array_key_exists('prices', $data)) {
                $productAbstractTransfer->setPrices($this->pricesArrayToPriceProductTransfers($data['prices'] ?? []));

                $existingPrices = $this->getAbstractDefaultPrices($productAbstractTransfer->getIdProductAbstractOrFail(), false, true);
                /** @var PriceProductTransfer $price */
                foreach($productAbstractTransfer->getPrices() as $key => $price) {
                    /** @var PriceProductTransfer|null $existingPriceTransfer */
                    $existingPriceTransfer = $existingPrices[$price->getPriceTypeName()][$price->getMoneyValue()->getStore()->getName()][$price->getMoneyValue()->getCurrency()->getCode()] ?? null;
                    if ($existingPriceTransfer === null) {
                        continue;
                    }
                    $existingPriceTransfer->getMoneyValue()->setGrossAmount($price->getMoneyValue()->getGrossAmount());
                    $existingPriceTransfer->getMoneyValue()->setNetAmount($price->getMoneyValue()->getNetAmount());
                    $price->fromArray($existingPriceTransfer->toArray(true), true);
                }
            }

            if (array_key_exists('localizations', $data)) {
                $productAbstractTransfer->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($data['localizations'] ?? []));
            }
            if (array_key_exists('stores', $data)) {
                $productAbstractTransfer->setStoreRelation((new StoreRelationTransfer())
                        ->setIdStores(array_map(fn($storeName) => ($this->stores[$storeName] ?? null)?->getIdStore() ?? null, $data['stores'] ?? []))
                );
            }

            return $productAbstractTransfer;
        }

        public function updateProductConcreteTransferFromArray(array $data, ProductConcreteTransfer $productConcreteTransfer): ProductConcreteTransfer
        {
            $value = $productConcreteTransfer->getAttributes() ?? '{}';
            $productConcreteTransfer->setAttributes(is_array($value) ? $value : ((new UtilEncodingService())->decodeJson($value, true) ?? []));

            if (array_key_exists('prices', $data)) {
                $productConcreteTransfer->setPrices($this->pricesArrayToPriceProductTransfers($data['prices']));

                if ($productConcreteTransfer->getIdProductConcrete()) {
                    $existingPrices = $this->getConcreteDefaultPrices($productConcreteTransfer->getFkProductAbstract(), $productConcreteTransfer->getIdProductConcrete(), false, true);
                    /** @var PriceProductTransfer $price */
                    foreach($productConcreteTransfer->getPrices() as $key => $price) {
                        /** @var PriceProductTransfer|null $existingPriceTransfer */
                        $existingPriceTransfer = $existingPrices[$price->getPriceTypeName()][$price->getMoneyValue()->getStore()->getName()][$price->getMoneyValue()->getCurrency()->getCode()] ?? null;
                        if ($existingPriceTransfer === null) {
                            continue;
                        }
                        $existingPriceTransfer->getMoneyValue()->setGrossAmount($price->getMoneyValue()->getGrossAmount());
                        $existingPriceTransfer->getMoneyValue()->setNetAmount($price->getMoneyValue()->getNetAmount());
                        $price->fromArray($existingPriceTransfer->toArray(true), true);
                    }
                }
            }

            if (array_key_exists('stocks', $data)) {
                // TODO: For simplicity, we only support currently the first available stock type
                $stockTypeName = SpyStockQuery::create()->findOneByIsActive(true)->getName();

                $stocks = [];
                foreach($data['stocks'] as $stock) {
                    $stocks[] = (new StockProductTransfer())
                        ->setSku($productConcreteTransfer->getSkuOrFail())
                        ->setStockType($stockTypeName)
                        ->setQuantity($stock['quantity'] ?? 0)
                        ->setIsNeverOutOfStock($stock['isNeverOutOfStock'] ?? false);
                }

                $productConcreteTransfer->setStocks(new \ArrayObject($stocks));
            }

            if (array_key_exists('attributes', $data)) {
                $productConcreteTransfer->setAttributes($data['attributes'] ?? []);
            }

            if (array_key_exists('localizations', $data)) {
                $productConcreteTransfer->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($data['localizations'] ?? []));
            }

            return $productConcreteTransfer;
        }

        public function getConcreteDefaultPrices($idProductAbstract, $idProductConcrete, $isFlat = false, $isTransfer = false): array
        {
            $priceProducts = (new PriceProductFacade())->findProductConcretePricesWithoutPriceExtraction(
                $idProductConcrete,
                $idProductAbstract,
                (new PriceProductCriteriaTransfer())
                    ->setOnlyConcretePrices(true)
                    ->setPriceDimension((new PriceProductDimensionTransfer())
                        ->setType(PriceProductConfig::PRICE_DIMENSION_DEFAULT)
                    ),
            );

            return $this->mapPrices($priceProducts, $isFlat, $isTransfer);
        }

        public function getAbstractDefaultPrices($idProductAbstract, $isFlat = false, $isTransfer = false): array
        {
            $priceProducts = (new PriceProductFacade())->findProductAbstractPricesWithoutPriceExtraction(
                $idProductAbstract,
                (new PriceProductCriteriaTransfer())
                    ->setOnlyConcretePrices(true)
                    ->setPriceDimension((new PriceProductDimensionTransfer())
                        ->setType(PriceProductConfig::PRICE_DIMENSION_DEFAULT)
                    ),
            );

            return $this->mapPrices($priceProducts, $isFlat, $isTransfer);
        }

        protected function mapPrices($priceProducts, $isFlat = false, $isTransfer = false): array
        {
            $prices = [];
            /** @var PriceProductTransfer $priceProductTransfer */
            foreach($priceProducts as $priceProductTransfer) {
                $priceTypeName = $priceProductTransfer->getPriceTypeName();
                $currencyCode = $priceProductTransfer->getMoneyValue()->getCurrency()->getCode();
                $storeName = $priceProductTransfer->getMoneyValue()->getStore()->getName();

                $prices[$priceTypeName][$storeName][$currencyCode] = $isTransfer ? $priceProductTransfer : [
                    'currencyCode' => $currencyCode,
                    'storeName' => $storeName,
                    'priceTypeName' => $priceTypeName,
                    'gross' => $priceProductTransfer->getMoneyValue()->getGrossAmount(),
                    'net' => $priceProductTransfer->getMoneyValue()->getNetAmount(),
                    'idPriceProductDefault' => $priceProductTransfer->getPriceDimension()->getIdPriceProductDefault(),
                ];
            }

            if ($isFlat) {
                $prices = array_reduce(
                    $prices,
                    fn($acc, $lvlA) => array_merge($acc, array_values(array_merge([], ...array_values($lvlA)))),
                    []
                );
            }

            return $prices;
        }


        public function createProductAbstractTransferFromArray(array $data): ProductAbstractTransfer
        {
            $productAbstractTransfer = (new ProductAbstractTransfer())
                ->setSku($data['sku'] ?? null)
                ->setNewFrom($data['newFrom'] ?? null)
                ->setNewTo($data['newTo'] ?? null)
                ->setImageSets(new \ArrayObject([]))
                ->setApprovalStatus('approved')
                ->setIdTaxSet(SpyTaxSetQuery::create()->findOne()->getIdTaxSet())
                ->setIsActive(static::PRODUCT_ABSTRACT_DEFAULT_IS_ACTIVE)
                ->setPrices($this->pricesArrayToPriceProductTransfers($data['prices'] ?? []))
                ->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($data['localizations'] ?? []))
                ->setStoreRelation((new StoreRelationTransfer())
                    ->setIdStores(array_map(fn($storeName) => ($this->stores[$storeName] ?? null)?->getIdStore() ?? null, $data['stores'] ?? []))
                );

            return $productAbstractTransfer;
        }

        public function createProductConcreteTransfersFromArray(array $data): ProductConcreteCollectionTransfer
        {
            $productConcreteCollectionTransfer = new ProductConcreteCollectionTransfer();

            foreach($data['concretes'] ?? [] as $concreteData) {
                $concreteSku = $data['sku'] . (empty($concreteData['attributes']) ? '-default' : implode('', array_map(fn($k, $v) => '-' . strtolower($k) . '-' . strtolower($v), array_keys($concreteData['attributes']), $concreteData['attributes'])));
                $concreteSku = preg_replace('/[^a-zA-Z0-9]/', '_', trim($concreteSku));
                $productConcreteTransfer = (new ProductConcreteTransfer())
                    ->setIsActive(static::PRODUCT_CONCRETE_DEFAULT_IS_ACTIVE)
                    ->setAbstractSku($data['sku'])
                    ->setSku($concreteSku)
                    ->setAttributes($concreteData['attributes'] ?? [])
                    ->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($concreteData['localizations'] ?? []));

                $productConcreteCollectionTransfer->addProduct($productConcreteTransfer);
            }

            return $productConcreteCollectionTransfer;
        }


    protected function localizationArrayToLocalizedAttributesTransfers(array $localizations): \ArrayObject {
        $resultLocalizations = [];
        foreach($localizations as $localization) {
            if (!array_key_exists($localization['localeName'], $this->locales)) {
                throw new \Exception('Locale ' . $localization['localeName'] . ' not found');
            }

            $localeTransfer = $this->locales[$localization['localeName']]; // de_DE

            $resultLocalizations[] = (new \Generated\Shared\Transfer\LocalizedAttributesTransfer())
                ->setLocale($localeTransfer)
                ->setName($localization['name'])
                ->setIsSearchable(true)
                ->setDescription($localization['description'] ?? null)
                ->setMetaTitle($localization['metaTitle'] ?? null)
                ->setMetaDescription($localization['metaDescription'] ?? null)
                ->setMetaKeywords($localization['metaKeywords'] ?? null);
        }

        return new \ArrayObject($resultLocalizations);
    }

    protected function pricesArrayToPriceProductTransfers(array $prices): \ArrayObject {
        $resultPrices = [];
        foreach ($prices as $price) {
            if (!array_key_exists($price['storeName'], $this->stores)) {
                throw new \Exception('Store ' . $price['storeName'] . ' not found');
            }
            if (!array_key_exists($price['currencyCode'], $this->currencies)) {
                throw new \Exception('Currency ' . $price['currencyCode'] . ' not found');
            }
            if (!array_key_exists($price['priceTypeName'], $this->priceTypes)) {
                throw new \Exception('Price type ' . $price['priceTypeName'] . ' not found');
            }

            $storeTransfer = $this->stores[$price['storeName']]; // DE
            $currencyTransfer = $this->currencies[$price['currencyCode']]; // CHF
            $priceTypeTransfer = $this->priceTypes[$price['priceTypeName']]; // DEFAULT

            $newKey = sprintf("%s-%s-%s-%s", $storeTransfer->getIdStore(), $currencyTransfer->getIdCurrency(), $priceTypeTransfer->getName(), $priceTypeTransfer->getPriceModeConfiguration());
            $resultPrices[$newKey] = (new \Generated\Shared\Transfer\PriceProductTransfer())
                ->setFkPriceType($priceTypeTransfer->getIdPriceType())
                ->setPriceType($priceTypeTransfer)
                ->setPriceTypeName($priceTypeTransfer->getName())
                ->setMoneyValue((new MoneyValueTransfer())
                    ->setGrossAmount($price['grossAmount'] ?? null)
                    ->setNetAmount($price['netAmount'] ?? null)
                    ->setFkStore($storeTransfer->getIdStore())
                    ->setFkCurrency($currencyTransfer->getIdCurrency())
                    ->setCurrency($currencyTransfer)
                    ->setStore($storeTransfer)
                );
        }

        return new \ArrayObject($resultPrices);
    }
}
