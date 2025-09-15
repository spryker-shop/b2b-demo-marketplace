<?php

namespace Go\Zed\GuiAssistant\Communication\Builder;

use Generated\Shared\Transfer\CurrencyCriteriaTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\LocaleCriteriaTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceTypeTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteCollectionTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StoreCriteriaTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingService;
use Spryker\Zed\Currency\Business\CurrencyFacade;
use Spryker\Zed\Locale\Business\LocaleFacade;
use Spryker\Zed\PriceProduct\Business\PriceProductFacade;
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
            $value = (new UtilEncodingService())->decodeJson($productAbstractTransfer->getAttributes(), true);
            $productAbstractTransfer->setAttributes(!is_array($value) ? [] : $value);

            if (array_key_exists('newFrom', $data)) {
                $productAbstractTransfer->setNewFrom($data['newFrom'] ?? null);
            }
            if (array_key_exists('newTo', $data)) {
                $productAbstractTransfer->setNewTo($data['newTo'] ?? null);
            }
            if (array_key_exists('prices', $data)) {
                $productAbstractTransfer->setPrices($this->pricesArrayToPriceProductTransfers($data['prices'] ?? []));
            }
            if (array_key_exists('localizations', $data)) {
                $productAbstractTransfer->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($data['localizations'] ?? []));
            }
            if (array_key_exists('stores', $data)) {
                $productAbstractTransfer->setStoreRelation((new StoreRelationTransfer())
                        ->setIdStores(array_map(fn($storeName) => $this->stores[$storeName]->getIdStore() ?? null, $data['stores'] ?? []))
                );
            }

            return $productAbstractTransfer;
        }

        public function createProductAbstractTransferFromArray(array $data): ProductAbstractTransfer
        {
            $productAbstractTransfer = (new ProductAbstractTransfer())
                ->setSku($data['sku'] ?? null)
                ->setNewFrom($data['newFrom'] ?? null)
                ->setNewTo($data['newTo'] ?? null)
                ->setImageSets(new \ArrayObject([]))
                ->setApprovalStatus('approved')
                ->setIdTaxSet(static::PRODUCT_ABSTRACT_DEFAULT_TAX_SET)
                ->setIsActive(static::PRODUCT_ABSTRACT_DEFAULT_IS_ACTIVE)
                ->setPrices($this->pricesArrayToPriceProductTransfers($data['prices'] ?? []))
                ->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($data['localizations'] ?? []))
                ->setStoreRelation((new StoreRelationTransfer())
                    ->setIdStores(array_map(fn($storeName) => $this->stores[$storeName]->getIdStore() ?? null, $data['stores'] ?? []))
                );

            return $productAbstractTransfer;
        }

        public function createProductConcreteTransfersFromArray(array $data): ProductConcreteCollectionTransfer
        {
            $productConcreteCollectionTransfer = new ProductConcreteCollectionTransfer();

            foreach($data['concretes'] ?? [] as $concreteData) {
                $productConcreteTransfer = (new ProductConcreteTransfer())
                    ->setIsActive(static::PRODUCT_CONCRETE_DEFAULT_IS_ACTIVE)
                    ->setAbstractSku($data['sku'] ?? null)
                    ->setSku($data['sku'] . (empty($concreteData['attributes']) ? '-default' : implode('', array_map(fn($k, $v) => '-' . strtolower($k) . '-' . strtolower($v), array_keys($concreteData['attributes']), $concreteData['attributes']))))
                    ->setAttributes($concreteData['attributes'] ?? [])
                    ->setLocalizedAttributes($this->localizationArrayToLocalizedAttributesTransfers($concreteData['localizations'] ?? []));

                $productConcreteCollectionTransfer->addProduct($productConcreteTransfer);
            }

            return $productConcreteCollectionTransfer;
        }


    protected function localizationArrayToLocalizedAttributesTransfers(array $localizations): \ArrayObject {
        $resultLocalizations = [];
        foreach($localizations as $localization) {
            $localeTransfer = $this->locales[$localization['localeName']]; // de_DE

            $resultLocalizations[] = (new \Generated\Shared\Transfer\LocalizedAttributesTransfer())
                ->setLocale($localeTransfer)
                ->setName($localization['name'])
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
            $storeTransfer = $this->stores[$price['storeName']]; // DE
            $currencyTransfer = $this->currencies[$price['currencyCode']]; // CHF
            $priceTypeTransfer = $this->priceTypes[$price['priceTypeName']]; // DEFAULT

            $newKey = sprintf("%s-%s-%s-%s", $storeTransfer->getIdStore(), $currencyTransfer->getIdCurrency(), $priceTypeTransfer->getName(), $priceTypeTransfer->getPriceModeConfiguration());
            $resultPrices[$newKey] = (new \Generated\Shared\Transfer\PriceProductTransfer())
                ->setFkPriceType($priceTypeTransfer->getIdPriceType())
                ->setPriceType($priceTypeTransfer)
                ->setMoneyValue((new MoneyValueTransfer())
                    ->setGrossAmount($price['grossAmount'] ?? null)
                    ->setNetAmount($price['netAmount'] ?? null)
                    ->setFkStore($storeTransfer->getIdStore())
                    ->setFkCurrency($currencyTransfer->getIdCurrency())
                    ->setCurrency($currencyTransfer)
                );
        }

        return new \ArrayObject($resultPrices);
    }
}
