<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\AclEntity\Persistence\AclDirector;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AclEntityRuleTransfer;
use Generated\Shared\Transfer\AclEntitySegmentRequestTransfer;
use Generated\Shared\Transfer\MerchantCategoryTransfer;
use Generated\Shared\Transfer\MerchantProductTransfer;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\RolesTransfer;
use Generated\Shared\Transfer\RoleTransfer;
use Orm\Zed\Merchant\Persistence\Map\SpyMerchantTableMap;
use Orm\Zed\Merchant\Persistence\SpyMerchant;
use Orm\Zed\Merchant\Persistence\SpyMerchantQuery;
use Orm\Zed\MerchantCategory\Persistence\Map\SpyMerchantCategoryTableMap;
use Orm\Zed\MerchantCategory\Persistence\SpyMerchantCategoryQuery;
use Orm\Zed\MerchantProduct\Persistence\SpyMerchantProductAbstract;
use Orm\Zed\Product\Persistence\SpyProductAbstract;
use Orm\Zed\Product\Persistence\SpyProductAbstractQuery;
use Orm\Zed\ProductImage\Persistence\SpyProductImageQuery;
use Orm\Zed\ProductOffer\Persistence\Map\SpyProductOfferTableMap;
use Orm\Zed\ProductOffer\Persistence\SpyProductOffer;
use Orm\Zed\ProductOffer\Persistence\SpyProductOfferQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Pyz\Zed\AclEntity\AclEntityDependencyProvider;
use PyzTest\Zed\AclEntity\AclQueryDirectorTester;
use Spryker\Shared\AclEntity\AclEntityConstants;
use Spryker\Zed\AclEntity\Persistence\Exception\OperationNotAuthorizedException;
use Spryker\Zed\AclMerchantPortal\Communication\Plugin\AclEntity\MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin;
use Spryker\Zed\Merchant\MerchantDependencyProvider;
use Spryker\Zed\ProductOffer\ProductOfferDependencyProvider;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group AclEntity
 * @group Persistence
 * @group AclDirector
 * @group AclQueryDirectorTest
 * Add your own group annotations below this line
 */
class AclQueryDirectorTest extends Unit
{
    protected const string ACL_ENTITY_SEGMENT_MERCHANT_NAME = 'segment merchant bulk';

    protected const string ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE = 'segment_merchant_bulk_reference';

    /**
     * @var \PyzTest\Zed\AclEntity\AclQueryDirectorTester
     */
    protected $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(
            AclEntityDependencyProvider::PLUGINS_ACL_ENTITY_METADATA_COLLECTION_EXPANDER,
            [new MerchantPortalConfigurationAclEntityMetadataConfigExpanderPlugin()],
        );

        $this->tester->setDependency(
            MerchantDependencyProvider::PLUGINS_MERCHANT_POST_CREATE,
            [],
        );

        $this->tester->setDependency(
            ProductOfferDependencyProvider::PLUGINS_PRODUCT_OFFER_POST_CREATE,
            [],
        );

        $this->tester->deleteTestData();
        $this->tester->clearAclQueryScopeResolverCache();
    }

    /**
     * @group AclEntityApplyAclRules
     */
    public function testForeignKeyQueryJoinDoesntInfluenceAclEntityJoins(): void
    {
        // Arrange
        $roleMerchantManagerTransfer = $this->tester->haveRole(
            [
                RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME,
            ],
        );
        $merchantTransfer = $this->tester->haveMerchant();
        $this->tester->createMerchantCategoryRules($merchantTransfer, $roleMerchantManagerTransfer);

        $category1Transfer = $this->tester->createCategory(AclQueryDirectorTester::CATEGORY_1_KEY);
        $this->tester->haveMerchantCategory(
            [
                MerchantCategoryTransfer::FK_MERCHANT => $merchantTransfer->getIdMerchantOrFail(),
                MerchantCategoryTransfer::FK_CATEGORY => $category1Transfer->getIdCategoryOrFail(),
            ],
        );

        $category2Transfer = $this->tester->createCategory(AclQueryDirectorTester::CATEGORY_2_KEY);
        $this->tester->haveMerchantCategory(
            [
                MerchantCategoryTransfer::FK_CATEGORY => $category2Transfer->getIdCategoryOrFail(),
            ],
        );

        $query = SpyMerchantCategoryQuery::create()->distinct();
        $join = new ModelJoin();
        $join->setTableMap(SpyMerchantQuery::create()->getTableMap());
        $join->setLeftTableName(SpyMerchantCategoryTableMap::TABLE_NAME);
        $join->setRightTableName(SpyMerchantTableMap::TABLE_NAME);
        $join->setJoinCondition(
            (new Criteria())->getNewCriterion(
                SpyMerchantTableMap::COL_ID_MERCHANT,
                0,
                Criteria::GREATER_THAN,
            ),
        );
        $query->addJoinObject($join, 'SpyMerchant');

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleMerchantManagerTransfer),
            $this->tester->createMerchantCategoryMerchantHierarchy(),
        );

        // Act
        $query = $aclQueryDirector->applyAclRuleOnSelectQuery($query);
        $merchantCategoryCollection = $query->find();

        // Assert
        $this->assertTrue($query->hasJoin('SpyMerchant'));
        $this->assertSame(1, $merchantCategoryCollection->count());
    }

    /**
     * @group AclEntityApplyAclRules
     */
    public function testReferenceColumnQueryJoinDoesntInfluenceAclEntityJoins(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);
        $merchantTransfer = $this->tester->haveMerchant();

        $this->tester->createProductOfferRules($merchantTransfer, $roleTransfer);

        $this->tester->haveProductOffer(
            [
                ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReferenceOrFail(),
            ],
        );
        $this->tester->haveProductOffer();

        $query = SpyProductOfferQuery::create()->distinct();
        $join = new ModelJoin();
        $join->setLeftTableName(SpyProductOfferTableMap::TABLE_NAME);
        $join->setRightTableName(SpyMerchantTableMap::TABLE_NAME);
        $join->setTableMap(SpyMerchantQuery::create()->getTableMap());
        $join->setJoinCondition(
            (new Criteria())->getNewCriterion(
                SpyMerchantTableMap::COL_ID_MERCHANT,
                0,
                Criteria::GREATER_THAN,
            ),
        );
        $query->addJoinObject($join, 'SpyMerchant');

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMerchantHierarchy(),
        );

        // Act
        $query = $aclQueryDirector->applyAclRuleOnSelectQuery($query);

        // Assert
        $this->assertTrue($query->hasJoin('SpyMerchant'));
        $this->assertSame(1, $query->count());
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testOneRoleRulesDontInfluenceOtherRole(): void
    {
        // Arrange
        $roleMerchant1ManagerTransfer = $this->tester->haveRole(
            [
                RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME,
            ],
        );
        $roleMerchant2ViewerTransfer = $this->tester->haveRole(
            [
                RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_2_NAME,
            ],
        );

        $merchant1Transfer = $this->tester->haveMerchant();
        $merchant2Transfer = $this->tester->haveMerchant();

        $this->tester->createProductOfferManagerRules($merchant1Transfer, $roleMerchant1ManagerTransfer);
        $this->tester->createProductOfferViewerRules($merchant2Transfer, $roleMerchant2ViewerTransfer);

        $merchant1ProductOfferTransfer = $this->tester->haveProductOffer(
            [
                ProductOfferTransfer::FK_MERCHANT => $merchant1Transfer->getIdMerchantOrFail(),
                ProductOfferTransfer::MERCHANT_REFERENCE => $merchant1Transfer->getMerchantReference(),
            ],
        );
        $merchant2ProductOfferTransfer = $this->tester->haveProductOffer(
            [
                ProductOfferTransfer::FK_MERCHANT => $merchant2Transfer->getIdMerchantOrFail(),
                ProductOfferTransfer::MERCHANT_REFERENCE => $merchant2Transfer->getMerchantReference(),
            ],
        );

        $rolesTransfer = (new RolesTransfer())->addRole($roleMerchant1ManagerTransfer)->addRole($roleMerchant2ViewerTransfer);

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            $rolesTransfer,
            $this->tester->createProductOfferMetadataHierarchy(),
        );
        $aclModelDirector = $this->tester->createAclModelDirector(
            $rolesTransfer,
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        $merchant1ProductOfferEntity = $this->tester->findProductOfferByIdProductOffer(
            $merchant1ProductOfferTransfer->getIdProductOfferOrFail(),
        );
        $merchant2ProductOfferEntity = $this->tester->findProductOfferByIdProductOffer(
            $merchant2ProductOfferTransfer->getIdProductOfferOrFail(),
        );

        // Act, Assert
        // User can manage merchant1 and view merchant2 ProductOffer
        $aclModelDirector->inspectUpdate($merchant1ProductOfferEntity);
        $aclModelDirector->inspectDelete($merchant1ProductOfferEntity);
        $query = $aclQueryDirector->applyAclRuleOnSelectQuery(
            SpyProductOfferQuery::create()->filterByIdProductOffer_In(
                [$merchant1ProductOfferTransfer->getIdProductOfferOrFail(), $merchant2ProductOfferTransfer->getIdProductOfferOrFail()],
            ),
        );
        $this->assertSame(2, $query->count());

        // User can't manage merchant2 ProductOffer
        $this->expectException(OperationNotAuthorizedException::class);
        $this->expectExceptionMessage(
            'Operation "update" is restricted for Orm\Zed\ProductOffer\Persistence\SpyProductOffer',
        );
        $aclModelDirector->inspectUpdate($merchant2ProductOfferEntity);
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToAccessibleSegmentForForeignKeyBasedInheritedChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productAbstractInSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantInSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyMerchantProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
                $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractQuery = $aclQueryDirector->applyAclRuleOnDeleteQuery($productAbstractQuery);

        // Assert
        $this->assertSame(
            [$productAbstractInSegmentTransfer->getIdProductAbstractOrFail()],
            array_map(
                function (SpyProductAbstract $productAbstractEntity): int {
                    return $productAbstractEntity->getIdProductAbstract();
                },
                $productAbstractQuery->find()->getArrayCopy(),
            ),
        );
    }

    public function testApplyAclRuleOnDeleteQueryAppliesFreshAccessibleIdsCriterionWhenQueryHasNoExistingPrimaryKeyFilter(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $skuPrefix = 'acl-entity-no-filter-' . uniqid();

        $productAbstractInSegmentTransfer = $this->tester->haveProductAbstract([ProductAbstractTransfer::SKU => $skuPrefix . '-in-segment']);
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantInSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract([ProductAbstractTransfer::SKU => $skuPrefix . '-out-of-segment']);
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyMerchantProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = $aclQueryDirector->applyAclRuleOnDeleteQuery(SpyProductAbstractQuery::create());

        // Assert
        $this->assertSame(
            [$productAbstractInSegmentTransfer->getIdProductAbstractOrFail()],
            array_map(
                function (SpyProductAbstract $productAbstractEntity): int {
                    return $productAbstractEntity->getIdProductAbstract();
                },
                $productAbstractQuery->filterBySku_Like($skuPrefix . '%')->find()->getArrayCopy(),
            ),
        );
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsBulkUpdateToAccessibleSegmentForReferenceColumnBasedInheritedChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productOfferInSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegmentTransfer->getMerchantReference()],
        );
        $productOfferOutOfSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantOutOfSegmentTransfer->getMerchantReference()],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [
                $productOfferInSegmentTransfer->getIdProductOfferOrFail(),
                $productOfferOutOfSegmentTransfer->getIdProductOfferOrFail(),
            ],
        );
        $productOfferQuery = $aclQueryDirector->applyAclRuleOnUpdateQuery($productOfferQuery);

        // Assert
        $this->assertSame(
            [$productOfferInSegmentTransfer->getIdProductOfferOrFail()],
            array_map(
                function (SpyProductOffer $productOfferEntity): int {
                    return $productOfferEntity->getIdProductOffer();
                },
                $productOfferQuery->find()->getArrayCopy(),
            ),
        );
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryDoesNotRestrictBulkDeleteForGlobalScopeRuleForRootEntity(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchant1Transfer = $this->tester->haveMerchant();
        $merchant2Transfer = $this->tester->haveMerchant();

        $productAbstract1Transfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchant1Transfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstract1Transfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstract2Transfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchant2Transfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstract2Transfer->getIdProductAbstractOrFail(),
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyMerchantProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_GLOBAL,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstract1Transfer->getIdProductAbstractOrFail(),
                $productAbstract2Transfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractIds = array_map(
            function (SpyProductAbstract $productAbstractEntity): int {
                return $productAbstractEntity->getIdProductAbstract();
            },
            $aclQueryDirector->applyAclRuleOnDeleteQuery($productAbstractQuery)->find()->getArrayCopy(),
        );

        // Assert
        sort($productAbstractIds);
        $expectedProductAbstractIds = [
            $productAbstract1Transfer->getIdProductAbstractOrFail(),
            $productAbstract2Transfer->getIdProductAbstractOrFail(),
        ];
        sort($expectedProductAbstractIds);
        $this->assertSame($expectedProductAbstractIds, $productAbstractIds);
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryDoesNotRestrictBulkUpdateForGlobalScopeRuleForRootEntity(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchant1Transfer = $this->tester->haveMerchant();
        $merchant2Transfer = $this->tester->haveMerchant();

        $productOffer1Transfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchant1Transfer->getMerchantReference()],
        );
        $productOffer2Transfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchant2Transfer->getMerchantReference()],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_GLOBAL,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [
                $productOffer1Transfer->getIdProductOfferOrFail(),
                $productOffer2Transfer->getIdProductOfferOrFail(),
            ],
        );
        $productOfferIds = array_map(
            function (SpyProductOffer $productOfferEntity): int {
                return $productOfferEntity->getIdProductOffer();
            },
            $aclQueryDirector->applyAclRuleOnUpdateQuery($productOfferQuery)->find()->getArrayCopy(),
        );

        // Assert
        sort($productOfferIds);
        $expectedProductOfferIds = [
            $productOffer1Transfer->getIdProductOfferOrFail(),
            $productOffer2Transfer->getIdProductOfferOrFail(),
        ];
        sort($expectedProductOfferIds);
        $this->assertSame($expectedProductOfferIds, $productOfferIds);
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToNothingForDefaultScopeRuleForRootEntity(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantTransfer = $this->tester->haveMerchant();
        $productAbstractTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyMerchantProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [$productAbstractTransfer->getIdProductAbstractOrFail()],
        );
        $productAbstractQuery = $aclQueryDirector->applyAclRuleOnDeleteQuery($productAbstractQuery);

        // Assert
        $this->assertSame(0, $productAbstractQuery->count());
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsBulkUpdateToNothingForDefaultScopeRuleForRootEntity(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantTransfer = $this->tester->haveMerchant();
        $productOfferTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReference()],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [$productOfferTransfer->getIdProductOfferOrFail()],
        );
        $productOfferQuery = $aclQueryDirector->applyAclRuleOnUpdateQuery($productOfferQuery);

        // Assert
        $this->assertSame(0, $productOfferQuery->count());
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsBulkUpdateToNothingWhenExistingFilterHasNoOverlapWithAccessibleSegment(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegmentTransfer->getMerchantReference()],
        );
        $productOfferOutOfSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantOutOfSegmentTransfer->getMerchantReference()],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [$productOfferOutOfSegmentTransfer->getIdProductOfferOrFail()],
        );
        $productOfferQuery = $aclQueryDirector->applyAclRuleOnUpdateQuery($productOfferQuery);

        // Assert
        $this->assertSame(0, $productOfferQuery->count());
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToAccessibleSegmentForSubEntityRootChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productAbstractInSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantInSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
                $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractIds = array_map(
            function (SpyProductAbstract $productAbstractEntity): int {
                return $productAbstractEntity->getIdProductAbstract();
            },
            $aclQueryDirector->applyAclRuleOnDeleteQuery($productAbstractQuery)->find()->getArrayCopy(),
        );

        // Assert
        $this->assertSame([$productAbstractInSegmentTransfer->getIdProductAbstractOrFail()], $productAbstractIds);
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToNothingForSubEntityRootChainWithPivotConnectionAndNoRule(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);
        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductImageProductCompositeEntityMetadataHierarchy(),
        );

        // Act
        $productImageQuery = $aclQueryDirector->applyAclRuleOnDeleteQuery(SpyProductImageQuery::create());

        // Assert
        $this->assertSame(0, $productImageQuery->count());
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsBulkUpdateToNothingForSubEntityRootChainWithPivotConnectionAndNoRule(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);
        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductImageProductCompositeEntityMetadataHierarchy(),
        );

        // Act
        $productImageQuery = $aclQueryDirector->applyAclRuleOnUpdateQuery(SpyProductImageQuery::create());

        // Assert
        $this->assertSame(0, $productImageQuery->count());
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsBulkUpdateToAccessibleSegmentForSubEntityRootChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productAbstractInSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantInSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
                $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractIds = array_map(
            function (SpyProductAbstract $productAbstractEntity): int {
                return $productAbstractEntity->getIdProductAbstract();
            },
            $aclQueryDirector->applyAclRuleOnUpdateQuery($productAbstractQuery)->find()->getArrayCopy(),
        );

        // Assert
        $this->assertSame([$productAbstractInSegmentTransfer->getIdProductAbstractOrFail()], $productAbstractIds);
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsBulkUpdateToAccessibleSegmentForForeignKeyBasedInheritedChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productAbstractInSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantInSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyMerchantProductAbstract::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
                $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractIds = array_map(
            function (SpyProductAbstract $productAbstractEntity): int {
                return $productAbstractEntity->getIdProductAbstract();
            },
            $aclQueryDirector->applyAclRuleOnUpdateQuery($productAbstractQuery)->find()->getArrayCopy(),
        );

        // Assert
        $this->assertSame([$productAbstractInSegmentTransfer->getIdProductAbstractOrFail()], $productAbstractIds);
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToAccessibleSegmentForReferenceColumnBasedInheritedChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productOfferInSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegmentTransfer->getMerchantReference()],
        );
        $productOfferOutOfSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantOutOfSegmentTransfer->getMerchantReference()],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [
                $productOfferInSegmentTransfer->getIdProductOfferOrFail(),
                $productOfferOutOfSegmentTransfer->getIdProductOfferOrFail(),
            ],
        );
        $productOfferIds = array_map(
            function (SpyProductOffer $productOfferEntity): int {
                return $productOfferEntity->getIdProductOffer();
            },
            $aclQueryDirector->applyAclRuleOnDeleteQuery($productOfferQuery)->find()->getArrayCopy(),
        );

        // Assert
        $this->assertSame([$productOfferInSegmentTransfer->getIdProductOfferOrFail()], $productOfferIds);
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToNothingWhenExistingFilterHasNoOverlapWithAccessibleSegment(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegmentTransfer->getMerchantReference()],
        );
        $productOfferOutOfSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantOutOfSegmentTransfer->getMerchantReference()],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [$productOfferOutOfSegmentTransfer->getIdProductOfferOrFail()],
        );
        $productOfferQuery = $aclQueryDirector->applyAclRuleOnDeleteQuery($productOfferQuery);

        // Assert
        $this->assertSame(0, $productOfferQuery->count());
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryDoesNotRestrictBulkDeleteForGlobalScopeRuleForSubEntityRootChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchant1Transfer = $this->tester->haveMerchant();
        $merchant2Transfer = $this->tester->haveMerchant();

        $productAbstract1Transfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchant1Transfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstract1Transfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstract2Transfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchant2Transfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstract2Transfer->getIdProductAbstractOrFail(),
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_GLOBAL,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstract1Transfer->getIdProductAbstractOrFail(),
                $productAbstract2Transfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractIds = array_map(
            function (SpyProductAbstract $productAbstractEntity): int {
                return $productAbstractEntity->getIdProductAbstract();
            },
            $aclQueryDirector->applyAclRuleOnDeleteQuery($productAbstractQuery)->find()->getArrayCopy(),
        );

        // Assert
        sort($productAbstractIds);
        $expectedProductAbstractIds = [
            $productAbstract1Transfer->getIdProductAbstractOrFail(),
            $productAbstract2Transfer->getIdProductAbstractOrFail(),
        ];
        sort($expectedProductAbstractIds);
        $this->assertSame($expectedProductAbstractIds, $productAbstractIds);
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryDoesNotRestrictBulkUpdateForGlobalScopeRuleForSubEntityRootChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchant1Transfer = $this->tester->haveMerchant();
        $merchant2Transfer = $this->tester->haveMerchant();

        $productAbstract1Transfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchant1Transfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstract1Transfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstract2Transfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchant2Transfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstract2Transfer->getIdProductAbstractOrFail(),
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_GLOBAL,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [
                $productAbstract1Transfer->getIdProductAbstractOrFail(),
                $productAbstract2Transfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractIds = array_map(
            function (SpyProductAbstract $productAbstractEntity): int {
                return $productAbstractEntity->getIdProductAbstract();
            },
            $aclQueryDirector->applyAclRuleOnUpdateQuery($productAbstractQuery)->find()->getArrayCopy(),
        );

        // Assert
        sort($productAbstractIds);
        $expectedProductAbstractIds = [
            $productAbstract1Transfer->getIdProductAbstractOrFail(),
            $productAbstract2Transfer->getIdProductAbstractOrFail(),
        ];
        sort($expectedProductAbstractIds);
        $this->assertSame($expectedProductAbstractIds, $productAbstractIds);
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRestrictsBulkDeleteToNothingWhenExistingFilterHasNoOverlapWithAccessibleSegmentForSubEntityRootChain(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $this->tester->haveProductAbstract();
        $productAbstractInSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantInSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractInSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = SpyProductAbstractQuery::create()->filterByIdProductAbstract_In(
            [$productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail()],
        );
        $productAbstractQuery = $aclQueryDirector->applyAclRuleOnDeleteQuery($productAbstractQuery);

        // Assert
        $this->assertSame(0, $productAbstractQuery->count());
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryRoutesSingleRecordSubEntityQueryToInspectDelete(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$this->tester->haveMerchant()->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Assert
        $this->expectException(OperationNotAuthorizedException::class);

        // Act
        $aclQueryDirector->applyAclRuleOnDeleteQuery(
            SpyProductAbstractQuery::create()->filterByIdProductAbstract($productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail()),
        );
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryRestrictsSingleRecordSubEntityQueryToAccessibleSegment(): void
    {
        // Arrange
        $roleTransfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);

        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();
        $productAbstractOutOfSegmentTransfer = $this->tester->haveProductAbstract();
        $this->tester->haveMerchantProduct(
            [
                MerchantProductTransfer::ID_MERCHANT => $merchantOutOfSegmentTransfer->getIdMerchantOrFail(),
                MerchantProductTransfer::ID_PRODUCT_ABSTRACT => $productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail(),
            ],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => static::ACL_ENTITY_SEGMENT_MERCHANT_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => static::ACL_ENTITY_SEGMENT_MERCHANT_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$this->tester->haveMerchant()->getIdMerchantOrFail()],
            ],
        );

        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($roleTransfer),
            $this->tester->createProductAbstractMerchantSubEntityMetadataHierarchy(),
        );

        // Act
        $productAbstractQuery = $aclQueryDirector->applyAclRuleOnUpdateQuery(
            SpyProductAbstractQuery::create()->filterByIdProductAbstract($productAbstractOutOfSegmentTransfer->getIdProductAbstractOrFail()),
        );

        // Assert
        $this->assertSame(0, $productAbstractQuery->count());
    }

    /**
     * @group AclEntityDelete
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnDeleteQueryUnionsAccessibleSegmentsAcrossMultipleRoles(): void
    {
        // Arrange
        $role1Transfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);
        $role2Transfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_2_NAME]);

        $merchantInSegment1Transfer = $this->tester->haveMerchant();
        $merchantInSegment2Transfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentsTransfer = $this->tester->haveMerchant();

        $productOfferInSegment1Transfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegment1Transfer->getMerchantReference()],
        );
        $productOfferInSegment2Transfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegment2Transfer->getMerchantReference()],
        );
        $productOfferOutOfSegmentsTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantOutOfSegmentsTransfer->getMerchantReference()],
        );

        $aclEntitySegment1Transfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => AclQueryDirectorTester::ACL_ENTITY_SEGMENT_1_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => AclQueryDirectorTester::ACL_ENTITY_SEGMENT_1_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegment1Transfer->getIdMerchantOrFail()],
            ],
        );
        $aclEntitySegment2Transfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => AclQueryDirectorTester::ACL_ENTITY_SEGMENT_2_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => AclQueryDirectorTester::ACL_ENTITY_SEGMENT_2_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegment2Transfer->getIdMerchantOrFail()],
            ],
        );

        foreach ([$role1Transfer, $role2Transfer] as $roleTransfer) {
            $this->tester->haveAclEntityRule(
                [
                    AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                    AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                    AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                    AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
                ],
            );
        }
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $role1Transfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegment1Transfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $role2Transfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegment2Transfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_DELETE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($role1Transfer)->addRole($role2Transfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [
                $productOfferInSegment1Transfer->getIdProductOfferOrFail(),
                $productOfferInSegment2Transfer->getIdProductOfferOrFail(),
                $productOfferOutOfSegmentsTransfer->getIdProductOfferOrFail(),
            ],
        );
        $productOfferIds = array_map(
            function (SpyProductOffer $productOfferEntity): int {
                return $productOfferEntity->getIdProductOffer();
            },
            $aclQueryDirector->applyAclRuleOnDeleteQuery($productOfferQuery)->find()->getArrayCopy(),
        );

        // Assert
        sort($productOfferIds);
        $expectedProductOfferIds = [
            $productOfferInSegment1Transfer->getIdProductOfferOrFail(),
            $productOfferInSegment2Transfer->getIdProductOfferOrFail(),
        ];
        sort($expectedProductOfferIds);
        $this->assertSame($expectedProductOfferIds, $productOfferIds);
    }

    /**
     * @group AclEntityUpdate
     * @group AclEntityApplyAclRules
     */
    public function testApplyAclRuleOnUpdateQueryDoesNotRestrictBulkUpdateWhenOneOfMultipleRolesGrantsGlobalScope(): void
    {
        // Arrange
        $role1Transfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_1_NAME]);
        $role2Transfer = $this->tester->haveRole([RoleTransfer::NAME => AclQueryDirectorTester::ACL_ROLE_2_NAME]);

        $merchantInSegmentTransfer = $this->tester->haveMerchant();
        $merchantOutOfSegmentTransfer = $this->tester->haveMerchant();

        $productOfferInSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantInSegmentTransfer->getMerchantReference()],
        );
        $productOfferOutOfSegmentTransfer = $this->tester->haveProductOffer(
            [ProductOfferTransfer::MERCHANT_REFERENCE => $merchantOutOfSegmentTransfer->getMerchantReference()],
        );

        $aclEntitySegmentTransfer = $this->tester->haveAclEntitySegment(
            [
                AclEntitySegmentRequestTransfer::NAME => AclQueryDirectorTester::ACL_ENTITY_SEGMENT_1_NAME,
                AclEntitySegmentRequestTransfer::REFERENCE => AclQueryDirectorTester::ACL_ENTITY_SEGMENT_1_REFERENCE,
                AclEntitySegmentRequestTransfer::ENTITY => SpyMerchant::class,
                AclEntitySegmentRequestTransfer::ENTITY_IDS => [$merchantInSegmentTransfer->getIdMerchantOrFail()],
            ],
        );

        foreach ([$role1Transfer, $role2Transfer] as $roleTransfer) {
            $this->tester->haveAclEntityRule(
                [
                    AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_INHERITED,
                    AclEntityRuleTransfer::ENTITY => SpyProductOffer::class,
                    AclEntityRuleTransfer::ID_ACL_ROLE => $roleTransfer->getIdAclRole(),
                    AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
                ],
            );
        }
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_SEGMENT,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $role1Transfer->getIdAclRole(),
                AclEntityRuleTransfer::ID_ACL_ENTITY_SEGMENT => $aclEntitySegmentTransfer->getIdAclEntitySegmentOrFail(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );
        $this->tester->haveAclEntityRule(
            [
                AclEntityRuleTransfer::SCOPE => AclEntityConstants::SCOPE_GLOBAL,
                AclEntityRuleTransfer::ENTITY => SpyMerchant::class,
                AclEntityRuleTransfer::ID_ACL_ROLE => $role2Transfer->getIdAclRole(),
                AclEntityRuleTransfer::PERMISSION_MASK => AclEntityConstants::OPERATION_MASK_UPDATE,
            ],
        );

        $aclQueryDirector = $this->tester->createAclQueryDirector(
            (new RolesTransfer())->addRole($role1Transfer)->addRole($role2Transfer),
            $this->tester->createProductOfferMetadataHierarchy(),
        );

        // Act
        $productOfferQuery = SpyProductOfferQuery::create()->filterByIdProductOffer_In(
            [
                $productOfferInSegmentTransfer->getIdProductOfferOrFail(),
                $productOfferOutOfSegmentTransfer->getIdProductOfferOrFail(),
            ],
        );
        $productOfferIds = array_map(
            function (SpyProductOffer $productOfferEntity): int {
                return $productOfferEntity->getIdProductOffer();
            },
            $aclQueryDirector->applyAclRuleOnUpdateQuery($productOfferQuery)->find()->getArrayCopy(),
        );

        // Assert
        sort($productOfferIds);
        $expectedProductOfferIds = [
            $productOfferInSegmentTransfer->getIdProductOfferOrFail(),
            $productOfferOutOfSegmentTransfer->getIdProductOfferOrFail(),
        ];
        sort($expectedProductOfferIds);
        $this->assertSame($expectedProductOfferIds, $productOfferIds);
    }
}
