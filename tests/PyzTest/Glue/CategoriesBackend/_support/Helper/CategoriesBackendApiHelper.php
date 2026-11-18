<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CategoriesBackend\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\CategoryCollectionRequestTransfer;
use Generated\Shared\Transfer\CategoryConditionsTransfer;
use Generated\Shared\Transfer\CategoryCriteriaTransfer;
use Generated\Shared\Transfer\CategoryLocalizedAttributesTransfer;
use Generated\Shared\Transfer\CategoryTemplateConditionsTransfer;
use Generated\Shared\Transfer\CategoryTemplateCriteriaTransfer;
use Generated\Shared\Transfer\CategoryTransfer;
use Generated\Shared\Transfer\NodeTransfer;
use Generated\Shared\Transfer\ProductCategoryConditionsTransfer;
use Generated\Shared\Transfer\ProductCategoryCriteriaTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Orm\Zed\Category\Persistence\SpyCategoryTemplateQuery;
use RuntimeException;
use Spryker\Zed\Category\Business\CategoryFacadeInterface;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\ProductCategory\Business\ProductCategoryFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * Arrange-side helper for the Categories Backend API tests: creates categories through the real
 * Category facade (the same write path the API's processor uses), so tests exercise the API
 * classes only in the Act step. All writes happen inside the per-test transaction opened by
 * TransactionHelper and are rolled back automatically — combined with unique keys/names no
 * data leaks between tests or runs.
 */
class CategoriesBackendApiHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    public const string RESOURCE_CATEGORIES = 'categories';

    public const string RESOURCE_CATEGORY_PRODUCTS = 'category-products';

    protected const string PATH_PRODUCTS = 'products';

    protected const string PATH_ASSIGN_PRODUCTS = 'assign-products';

    protected const string PATH_UNASSIGN_PRODUCTS = 'unassign-products';

    protected const string ATTRIBUTE_CATEGORY_KEY = 'categoryKey';

    protected const string ATTRIBUTE_PARENT_CATEGORY_KEY = 'parentCategoryKey';

    protected const string ATTRIBUTE_TEMPLATE_NAME = 'templateName';

    protected const string ATTRIBUTE_LOCALIZED_ATTRIBUTES = 'localizedAttributes';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    protected const string ATTRIBUTE_NAME = 'name';

    /**
     * @param array<string, mixed> $query
     */
    public function getCategoryCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_CATEGORIES) . $this->formatQuery($query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCategoryUrl(string $categoryKey, array $query = []): string
    {
        return sprintf('/%s/%s', static::RESOURCE_CATEGORIES, $categoryKey) . $this->formatQuery($query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCategoryProductCollectionUrl(string $categoryKey, array $query = []): string
    {
        return sprintf('/%s/%s/%s', static::RESOURCE_CATEGORIES, $categoryKey, static::PATH_PRODUCTS) . $this->formatQuery($query);
    }

    public function getCategoryProductUrl(string $categoryKey, string $sku): string
    {
        return sprintf('/%s/%s/%s/%s', static::RESOURCE_CATEGORIES, $categoryKey, static::PATH_PRODUCTS, $sku);
    }

    public function getAssignProductsUrl(string $categoryKey): string
    {
        return sprintf('/%s/%s/%s', static::RESOURCE_CATEGORIES, $categoryKey, static::PATH_ASSIGN_PRODUCTS);
    }

    public function getUnassignProductsUrl(string $categoryKey): string
    {
        return sprintf('/%s/%s/%s', static::RESOURCE_CATEGORIES, $categoryKey, static::PATH_UNASSIGN_PRODUCTS);
    }

    /**
     * A complete, valid POST payload: generated key, the root category as parent, a persisted
     * template and a generated name in every available locale. Every key can be overridden.
     *
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidCategoryAttributes(array $override = []): array
    {
        $localizedAttributes = [];
        $name = $this->generateCategoryName();
        foreach ($this->getAvailableLocaleNames() as $localeName) {
            $localizedAttributes[] = [static::ATTRIBUTE_LOCALE_NAME => $localeName, static::ATTRIBUTE_NAME => $name];
        }

        return array_merge([
            static::ATTRIBUTE_CATEGORY_KEY => $this->generateCategoryKey(),
            static::ATTRIBUTE_PARENT_CATEGORY_KEY => $this->getRootCategoryKey(),
            static::ATTRIBUTE_TEMPLATE_NAME => $this->haveCategoryTemplate(),
            static::ATTRIBUTE_LOCALIZED_ATTRIBUTES => $localizedAttributes,
        ], $override);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCategoryRequestBody(array $attributes, ?string $categoryKey = null): string
    {
        return $this->buildRequestBody(static::RESOURCE_CATEGORIES, $attributes, $categoryKey);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCategoryProductsRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(static::RESOURCE_CATEGORY_PRODUCTS, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function buildRequestBody(string $type, array $attributes, ?string $id = null): string
    {
        $data = ['type' => $type, 'attributes' => $attributes];
        if ($id !== null) {
            $data['id'] = $id;
        }

        return (string)json_encode(['data' => $data]);
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

    /**
     * Creates a persisted category through the Category facade.
     *
     * Supported seed keys: categoryKey, isRoot, parentCategoryKey, extraParentCategoryKeys,
     * templateName (created on the fly when omitted), position, isActive, localizedAttributes
     * (list of [localeName, name, metaTitle, metaDescription, metaKeywords]), stores.
     *
     * @param array<string, mixed> $seed
     *
     * @throws \RuntimeException
     */
    public function haveCategoryViaFacade(array $seed = []): CategoryTransfer
    {
        $categoryTransfer = (new CategoryTransfer())
            ->setCategoryKey($seed['categoryKey'] ?? $this->generateCategoryKey())
            ->setIsActive($seed['isActive'] ?? true)
            ->setIsInMenu(true)
            ->setIsSearchable(true)
            ->setIsClickable(true)
            ->setFkCategoryTemplate($this->resolveIdCategoryTemplate($seed['templateName'] ?? $this->haveCategoryTemplate()));

        if (!empty($seed['uuid'])) {
            $categoryTransfer->setUuid($seed['uuid']);
        }

        $categoryNodeTransfer = (new NodeTransfer())->setIsRoot(!empty($seed['isRoot']));
        if (isset($seed['position'])) {
            $categoryNodeTransfer->setNodeOrder($seed['position']);
        }

        $categoryTransfer->setCategoryNode($categoryNodeTransfer);

        if (!empty($seed['parentCategoryKey'])) {
            $categoryTransfer->setParentCategoryNode($this->getMainNodeByCategoryKey($seed['parentCategoryKey']));
        }

        foreach ($seed['extraParentCategoryKeys'] ?? [] as $extraParentCategoryKey) {
            $categoryTransfer->addExtraParent($this->getMainNodeByCategoryKey($extraParentCategoryKey));
        }

        $localizedAttributeEntries = $seed['localizedAttributes']
            ?? [['localeName' => $this->getAvailableLocaleNames()[0], 'name' => $this->generateCategoryName()]];

        foreach ($localizedAttributeEntries as $localizedAttributeEntry) {
            $categoryTransfer->addLocalizedAttributes(
                $this->createCategoryLocalizedAttributesTransfer($localizedAttributeEntry),
            );
        }

        if (!empty($seed['stores'])) {
            $categoryTransfer->setStoreRelation($this->createStoreRelationTransfer($seed['stores']));
        }

        $categoryCollectionResponseTransfer = $this->getCategoryFacade()->createCategoryCollection(
            (new CategoryCollectionRequestTransfer())
                ->addCategory($categoryTransfer)
                ->setIsTransactional(true),
        );

        $this->assertCollectionResponseHasNoErrors($categoryCollectionResponseTransfer->getErrors());

        $createdCategoryTransfers = $categoryCollectionResponseTransfer->getCategories()->getArrayCopy();
        if ($createdCategoryTransfers === []) {
            throw new RuntimeException('Category facade returned no created category.');
        }

        return $createdCategoryTransfers[0];
    }

    /**
     * Returns the categoryKey of the (single) root category, creating one when the database has
     * none — the created root is rolled back with the test transaction.
     */
    public function getRootCategoryKey(): string
    {
        $categoryCollectionTransfer = $this->getCategoryFacade()->getCategoryCollection(
            (new CategoryCriteriaTransfer())->setCategoryConditions(
                (new CategoryConditionsTransfer())->setIsRoot(true),
            ),
        );

        $rootCategoryTransfers = $categoryCollectionTransfer->getCategories()->getArrayCopy();
        if ($rootCategoryTransfers !== []) {
            return $rootCategoryTransfers[0]->getCategoryKeyOrFail();
        }

        return $this->haveCategoryViaFacade(['isRoot' => true])->getCategoryKeyOrFail();
    }

    /**
     * Ensures a category template with the given (or a generated unique) name exists and returns
     * its name. A created row is rolled back with the test transaction.
     */
    public function haveCategoryTemplate(?string $templateName = null): string
    {
        $templateName = $templateName ?? uniqid('pm-test-template-', false);

        $categoryTemplateEntity = SpyCategoryTemplateQuery::create()
            ->filterByName($templateName)
            ->findOneOrCreate();

        if ($categoryTemplateEntity->isNew()) {
            $categoryTemplateEntity->setTemplatePath(sprintf('@CategoryGui/_test/%s.twig', $templateName));
            $categoryTemplateEntity->save();
        }

        return $templateName;
    }

    /**
     * @return array<string> Locale names available in this environment, e.g. ['de_DE', 'en_US'].
     */
    public function getAvailableLocaleNames(): array
    {
        return array_keys($this->getLocaleFacade()->getLocaleCollection());
    }

    /**
     * @return array<string> Store names available in this environment, e.g. ['DE', 'AT'].
     */
    public function getExistingStoreNames(): array
    {
        $storeNames = [];
        foreach ($this->getStoreFacade()->getAllStores() as $storeTransfer) {
            $storeNames[] = $storeTransfer->getNameOrFail();
        }

        return $storeNames;
    }

    public function generateCategoryKey(): string
    {
        return uniqid('pm-test-', false);
    }

    /**
     * Single lowercase token, so the slugified URL segment equals the name verbatim.
     */
    public function generateCategoryName(): string
    {
        return uniqid('pmcat', false);
    }

    /**
     * @param array<string, mixed> $localizedAttributeEntry
     *
     * @throws \RuntimeException
     */
    protected function createCategoryLocalizedAttributesTransfer(array $localizedAttributeEntry): CategoryLocalizedAttributesTransfer
    {
        $localeTransfersIndexedByLocaleName = $this->getLocaleFacade()->getLocaleCollection();
        $localeName = $localizedAttributeEntry['localeName'];

        if (!isset($localeTransfersIndexedByLocaleName[$localeName])) {
            throw new RuntimeException(sprintf('Locale "%s" is not available in this environment.', $localeName));
        }

        $categoryLocalizedAttributesTransfer = (new CategoryLocalizedAttributesTransfer())
            ->setLocale($localeTransfersIndexedByLocaleName[$localeName]);

        unset($localizedAttributeEntry['localeName']);

        return $categoryLocalizedAttributesTransfer->fromArray($localizedAttributeEntry, true);
    }

    /**
     * @param array<string> $storeNames
     */
    protected function createStoreRelationTransfer(array $storeNames): StoreRelationTransfer
    {
        $storeRelationTransfer = new StoreRelationTransfer();
        foreach ($this->getStoreFacade()->getStoreTransfersByStoreNames($storeNames) as $storeTransfer) {
            $storeRelationTransfer->addStores($storeTransfer);
            $storeRelationTransfer->addIdStores($storeTransfer->getIdStoreOrFail());
        }

        return $storeRelationTransfer;
    }

    protected function getMainNodeByCategoryKey(string $categoryKey): NodeTransfer
    {
        $categoryCollectionTransfer = $this->getCategoryFacade()->getCategoryCollection(
            (new CategoryCriteriaTransfer())->setCategoryConditions(
                (new CategoryConditionsTransfer())->addCategoryKey($categoryKey),
            ),
        );

        $categoryTransfers = $categoryCollectionTransfer->getCategories()->getArrayCopy();
        if ($categoryTransfers === []) {
            throw new RuntimeException(sprintf('Category with key "%s" does not exist.', $categoryKey));
        }

        return $categoryTransfers[0]->getCategoryNodeOrFail();
    }

    protected function resolveIdCategoryTemplate(string $templateName): int
    {
        $categoryTemplateCollectionTransfer = $this->getCategoryFacade()->getCategoryTemplateCollection(
            (new CategoryTemplateCriteriaTransfer())->setCategoryTemplateConditions(
                (new CategoryTemplateConditionsTransfer())->addName($templateName),
            ),
        );

        foreach ($categoryTemplateCollectionTransfer->getCategoryTemplates() as $categoryTemplateTransfer) {
            if ($categoryTemplateTransfer->getName() === $templateName) {
                return $categoryTemplateTransfer->getIdCategoryTemplateOrFail();
            }
        }

        throw new RuntimeException(sprintf('Category template "%s" does not exist.', $templateName));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ErrorTransfer>|iterable $errorTransfers
     *
     * @throws \RuntimeException
     */

    /**
     * @param iterable<\Generated\Shared\Transfer\ErrorTransfer> $errorTransfers
     *
     * @throws \RuntimeException
     */
    protected function assertCollectionResponseHasNoErrors(iterable $errorTransfers): void
    {
        $errorMessages = [];
        foreach ($errorTransfers as $errorTransfer) {
            $errorMessages[] = (string)$errorTransfer->getMessage();
        }

        if ($errorMessages !== []) {
            throw new RuntimeException(sprintf('Category facade write failed: %s', implode(' ', $errorMessages)));
        }
    }

    /**
     * Assigns an abstract product to a category through the real ProductCategory facade; rolled
     * back with the per-test transaction.
     */
    public function haveProductAssignedToCategory(int $idCategory, int $idProductAbstract, ?int $position = null): void
    {
        $this->getProductCategoryFacade()->createProductCategoryMappings($idCategory, [$idProductAbstract]);

        if ($position !== null) {
            $this->getProductCategoryFacade()->updateProductMappingsOrder($idCategory, [$idProductAbstract => $position]);
        }

        // Cleanups run LIFO: this removes the mapping before the ProductDataHelper cleanup deletes
        // the product row, so the teardown does not trip over the foreign key.
        $this->getDataCleanupHelper()->_addCleanup(function () use ($idCategory, $idProductAbstract): void {
            $this->getProductCategoryFacade()->removeProductCategoryMappings($idCategory, [$idProductAbstract]);
        });
    }

    /**
     * Reads the current assignment state straight from the ProductCategory facade, for
     * all-or-nothing assertions against the database.
     */
    public function isProductAssignedToCategory(int $idCategory, int $idProductAbstract): bool
    {
        $productCategoryCollectionTransfer = $this->getProductCategoryFacade()->getProductCategoryCollection(
            (new ProductCategoryCriteriaTransfer())->setProductCategoryConditions(
                (new ProductCategoryConditionsTransfer())->setProductAbstractIds([$idProductAbstract]),
            ),
        );

        foreach ($productCategoryCollectionTransfer->getProductCategories() as $productCategoryTransfer) {
            if ($productCategoryTransfer->getFkCategory() === $idCategory) {
                return true;
            }
        }

        return false;
    }

    /**
     * Counts the mapping rows between one category and one abstract product — a duplicate-free
     * assignment has exactly one row; used by the idempotency assertions.
     */
    public function countProductCategoryAssignments(int $idCategory, int $idProductAbstract): int
    {
        $productCategoryCollectionTransfer = $this->getProductCategoryFacade()->getProductCategoryCollection(
            (new ProductCategoryCriteriaTransfer())->setProductCategoryConditions(
                (new ProductCategoryConditionsTransfer())->setProductAbstractIds([$idProductAbstract]),
            ),
        );

        $count = 0;
        foreach ($productCategoryCollectionTransfer->getProductCategories() as $productCategoryTransfer) {
            if ($productCategoryTransfer->getFkCategory() !== $idCategory) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    protected function getProductCategoryFacade(): ProductCategoryFacadeInterface
    {
        return $this->getLocator()->productCategory()->facade();
    }

    protected function getCategoryFacade(): CategoryFacadeInterface
    {
        return $this->getLocator()->category()->facade();
    }

    protected function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getLocator()->locale()->facade();
    }

    protected function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getLocator()->store()->facade();
    }
}
