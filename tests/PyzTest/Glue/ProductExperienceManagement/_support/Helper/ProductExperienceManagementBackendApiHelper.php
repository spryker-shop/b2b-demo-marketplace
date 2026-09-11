<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\ProductExperienceManagement\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Currency\Persistence\SpyCurrencyQuery;
use Spryker\Service\UtilUuidGenerator\UtilUuidGeneratorServiceInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use Symfony\Component\Intl\Currencies;

/**
 * Arrange-side helper for the `/products` Backend API suite: URL builders, JSON:API body builders,
 * and the store/currency lookups the products resource needs.
 *
 * Category seeding comes from {@see \PyzTest\Glue\CategoriesBackend\Helper\CategoriesBackendApiHelper},
 * enabled alongside this module — not from {@see \SprykerTest\Zed\Category\Helper\CategoryDataHelper},
 * whose `_before()` installs a `CategoryStoreAssignerPluginInterface` stub with `Expected::once()`
 * for EVERY test in the suite.
 */
class ProductExperienceManagementBackendApiHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    public const string RESOURCE_PRODUCTS = 'products';

    protected const string FILTER_SKU = 'products.sku';

    protected const string FILTER_SKUS = 'products.skus';

    protected const string FILTER_ABSTRACT_SKU = 'products.abstractSku';

    /**
     * @param array<string, mixed> $query
     */
    public function getProductCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_PRODUCTS) . $this->formatQuery($query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getProductUrl(string $sku, array $query = []): string
    {
        return sprintf('/%s/%s', static::RESOURCE_PRODUCTS, rawurlencode($sku)) . $this->formatQuery($query);
    }

    public function getProductCollectionTrailingSlashUrl(): string
    {
        return sprintf('/%s/', static::RESOURCE_PRODUCTS);
    }

    public function getProductCollectionUrlFilteredBySku(string $sku): string
    {
        return $this->getProductCollectionUrl(['filter' => [static::FILTER_SKU => $sku]]);
    }

    /**
     * @param array<string> $skus
     */
    public function getProductCollectionUrlFilteredBySkus(array $skus): string
    {
        return $this->getProductCollectionUrl(['filter' => [static::FILTER_SKUS => array_values($skus)]]);
    }

    public function getProductCollectionUrlFilteredByAbstractSku(string $abstractSku): string
    {
        return $this->getProductCollectionUrl(['filter' => [static::FILTER_ABSTRACT_SKU => $abstractSku]]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildProductRequestBody(array $attributes, ?string $sku = null): string
    {
        $data = ['type' => static::RESOURCE_PRODUCTS, 'attributes' => $attributes];

        if ($sku !== null) {
            $data['id'] = $sku;
        }

        return (string)json_encode(['data' => $data]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function buildRawBody(array $payload): string
    {
        return (string)json_encode($payload);
    }

    public function generateSku(string $prefix = 'pxm-'): string
    {
        return uniqid($prefix, false);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function formatQuery(array $query): string
    {
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }

    public function getStoreTransferByName(string $storeName): StoreTransfer
    {
        return $this->getStoreFacade()->getStoreByName($storeName);
    }

    public function getCurrentStoreName(): string
    {
        return $this->getStoreFacade()->getCurrentStore(true)->getNameOrFail();
    }

    public function getDefaultCurrencyCode(string $storeName): string
    {
        return $this->getStoreFacade()->getStoreByName($storeName)->getDefaultCurrencyIsoCodeOrFail();
    }

    public function getUninstalledCurrencyCode(): ?string
    {
        $installedCurrencyCodes = SpyCurrencyQuery::create()->select('code')->find()->toArray();
        $uninstalledCurrencyCodes = array_values(array_diff(Currencies::getCurrencyCodes(), $installedCurrencyCodes));

        return $uninstalledCurrencyCodes[0] ?? null;
    }

    public function generateUuid(string $name): string
    {
        return $this->getUtilUuidGeneratorService()->generateUuid5FromObjectId($name);
    }

    protected function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getLocator()->store()->facade();
    }

    protected function getUtilUuidGeneratorService(): UtilUuidGeneratorServiceInterface
    {
        return $this->getLocator()->utilUuidGenerator()->service();
    }
}
