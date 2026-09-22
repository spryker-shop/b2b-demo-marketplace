<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CatalogSearch\RestApi;

use Codeception\Util\HttpCode;
use PyzTest\Glue\CatalogSearch\CatalogSearchApiTester;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CatalogSearch
 * @group RestApi
 * @group CatalogSearchRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class CatalogSearchRestApiCest
{
    protected const string HEADER_ACCEPT = 'Accept';

    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    protected const string MIME_TYPE_JSON_API = 'application/vnd.api+json';

    /**
     * @var array<string>
     */
    protected const array PRODUCT_KEYS = ['abstractSku', 'abstractName', 'price', 'prices', 'images'];

    /**
     * @var array<string>
     */
    protected const array IMAGE_KEYS = ['externalUrlSmall', 'externalUrlLarge'];

    /**
     * @var array<string>
     */
    protected const array CURRENCY_KEYS = ['code', 'name', 'symbol'];

    /**
     * @var array<string>
     */
    protected const array CATEGORY_NODE_KEYS = ['nodeId', 'name', 'docCount', 'children'];

    public function requestWithoutAcceptHeaderFallsBackToLegacyJsonApiDefault(CatalogSearchApiTester $I): void
    {
        // Arrange
        $I->unsetHttpHeader(static::HEADER_ACCEPT);

        // Act
        $I->sendGET($I->buildCatalogSearchUrl());

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader(static::HEADER_CONTENT_TYPE, static::MIME_TYPE_JSON_API);
    }

    public function requestWithExplicitJsonApiAcceptHeaderSucceeds(CatalogSearchApiTester $I): void
    {
        // Arrange
        $I->haveHttpHeader(static::HEADER_ACCEPT, static::MIME_TYPE_JSON_API);

        // Act
        $I->sendGET($I->buildCatalogSearchUrl());

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader(static::HEADER_CONTENT_TYPE, static::MIME_TYPE_JSON_API);
    }

    public function requestWithWildcardAcceptHeaderSucceeds(CatalogSearchApiTester $I): void
    {
        // Arrange — browser-style header that only volunteers wildcards.
        $I->haveHttpHeader(static::HEADER_ACCEPT, 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        // Act
        $I->sendGET($I->buildCatalogSearchUrl());

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeHttpHeader(static::HEADER_CONTENT_TYPE, static::MIME_TYPE_JSON_API);
    }

    public function collectionMembersExposeOnlyTheDocumentedProductAndCategoryFields(CatalogSearchApiTester $I): void
    {
        // Arrange
        $I->haveHttpHeader(static::HEADER_ACCEPT, static::MIME_TYPE_JSON_API);

        // Act
        $I->sendGET($I->buildCatalogSearchUrl());

        // Assert
        $I->seeResponseCodeIs(HttpCode::OK);
        $product = $I->grabDataFromResponseByJsonPath('$.data[0].attributes.abstractProducts[0]')[0];
        $I->assertEqualsCanonicalizing(static::PRODUCT_KEYS, array_keys($product));
        $I->assertEqualsCanonicalizing(static::IMAGE_KEYS, array_keys($product['images'][0]));
        $I->assertEqualsCanonicalizing(static::CURRENCY_KEYS, array_keys($product['prices'][0]['currency']));
        $categoryNode = $I->grabDataFromResponseByJsonPath('$.data[0].attributes.categoryTreeFilter[0]')[0];
        $I->assertEqualsCanonicalizing(static::CATEGORY_NODE_KEYS, array_keys($categoryNode));
    }
}
