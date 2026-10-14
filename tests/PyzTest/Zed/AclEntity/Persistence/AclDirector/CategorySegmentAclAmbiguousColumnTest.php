<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\AclEntity\Persistence\AclDirector;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AclEntityMetadataCollectionTransfer;
use Generated\Shared\Transfer\AclEntityMetadataConfigTransfer;
use Generated\Shared\Transfer\AclEntityMetadataTransfer;
use Generated\Shared\Transfer\AclEntityParentMetadataTransfer;
use Orm\Zed\Category\Persistence\SpyCategory;
use Orm\Zed\Category\Persistence\SpyCategoryQuery;
use Orm\Zed\Category\Persistence\SpyCategoryStore;
use Orm\Zed\Category\Persistence\SpyCategoryStoreQuery;
use Orm\Zed\Store\Persistence\SpyStore;
use PyzTest\Zed\AclEntity\AclQueryDirectorTester;
use ReflectionClass;
use Spryker\Zed\AclEntity\Persistence\AclEntityPersistenceFactory;
use Spryker\Zed\Category\Persistence\CategoryRepository;
use Spryker\Zed\PropelOrm\Business\Model\Formatter\PropelArraySetFormatter;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group AclEntity
 * @group Persistence
 * @group AclDirector
 * @group CategorySegmentAclAmbiguousColumnTest
 * Add your own group annotations below this line
 */
class CategorySegmentAclAmbiguousColumnTest extends Unit
{
    protected const string CATEGORY_KEY_PREFIX = 'acl-entity-ambiguous-column-category-';

    protected const string STORE_NAME_PREFIX = 'acl-entity-ambiguous-column-store-';

    protected AclQueryDirectorTester $tester;

    protected ?SpyCategory $categoryEntity = null;

    protected ?SpyStore $storeEntity = null;

    protected ?string $categoryKey = null;

    protected function tearDown(): void
    {
        if ($this->categoryEntity) {
            SpyCategoryStoreQuery::create()->filterByFkCategory($this->categoryEntity->getIdCategory())->delete();
        }
        $this->storeEntity?->delete();

        parent::tearDown();
    }

    public function testJoiningInheritedScopeStoreDoesNotCauseAmbiguousColumnErrorOnSelect(): void
    {
        // Arrange
        $this->storeEntity = (new SpyStore())->setName(static::STORE_NAME_PREFIX . uniqid());
        $this->storeEntity->save();

        $this->categoryKey = static::CATEGORY_KEY_PREFIX . uniqid();
        $this->tester->createCategory($this->categoryKey);
        $this->categoryEntity = $this->tester->loadCategoryByCategoryKey($this->categoryKey);

        (new SpyCategoryStore())
            ->setFkCategory($this->categoryEntity->getIdCategory())
            ->setFkStore($this->storeEntity->getIdStore())
            ->save();

        $aclQueryExpander = (new AclEntityPersistenceFactory())->createAclQueryExpander(
            (new AclEntityMetadataConfigTransfer())
                ->setAclEntityMetadataCollection($this->createCategoryStoreAclEntityMetadataCollection()),
        );

        $categoryQuery = SpyCategoryQuery::create()
            ->filterByIdCategory($this->categoryEntity->getIdCategory())
            ->joinAttribute();

        $aclQueryExpander->joinEntityParent(
            $categoryQuery,
            (new AclEntityMetadataTransfer())->setEntityName(SpyStore::class),
        );

        $categoryQuery
            ->clearSelectColumns()
            ->addSelectColumn((new ReflectionClass(CategoryRepository::class))->getConstant('COL_CATEGORY_NAME'))
            ->setFormatter(new PropelArraySetFormatter());

        // Act
        $categoryNames = $categoryQuery->find();

        // Assert
        $this->assertNotEmpty($categoryNames);
        foreach ($categoryNames as $categoryName) {
            $this->assertStringStartsWith($this->categoryKey . ' name ', $categoryName);
        }
    }

    protected function createCategoryStoreAclEntityMetadataCollection(): AclEntityMetadataCollectionTransfer
    {
        return (new AclEntityMetadataCollectionTransfer())
            ->addAclEntityMetadata(
                SpyCategory::class,
                (new AclEntityMetadataTransfer())
                    ->setEntityName(SpyCategory::class)
                    ->setParent((new AclEntityParentMetadataTransfer())->setEntityName(SpyCategoryStore::class)),
            )
            ->addAclEntityMetadata(
                SpyCategoryStore::class,
                (new AclEntityMetadataTransfer())
                    ->setEntityName(SpyCategoryStore::class)
                    ->setParent((new AclEntityParentMetadataTransfer())->setEntityName(SpyStore::class)),
            )
            ->addAclEntityMetadata(
                SpyStore::class,
                (new AclEntityMetadataTransfer())->setEntityName(SpyStore::class),
            );
    }
}
