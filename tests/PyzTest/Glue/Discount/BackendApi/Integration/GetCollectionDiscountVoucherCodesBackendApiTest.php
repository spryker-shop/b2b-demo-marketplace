<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount\BackendApi\Integration;

use PyzTest\Glue\Discount\AbstractDiscountBackendApiTestCase;
use Spryker\Glue\Discount\DiscountConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Discount
 * @group BackendApi
 * @group Integration
 * @group GetCollectionDiscountVoucherCodesBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionDiscountVoucherCodesBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string CUSTOM_CODE_PREFIX = 'LIST-';

    protected const int GENERATED_QUANTITY = 3;

    protected const string FIXTURE_ALPHA = 'A';

    protected const string FIXTURE_BETA = 'B';

    protected const int SORT_FIXTURE_QUANTITY = 2;

    protected const int SORT_FIXTURE_ALPHA_MAX_NUMBER_OF_USES = 1;

    protected const int SORT_FIXTURE_BETA_MAX_NUMBER_OF_USES = 2;

    protected const int SORT_FIXTURE_ALPHA_NUMBER_OF_USES = 1;

    protected const string ATTRIBUTE_CODE = 'code';

    protected string $discountUuid = '';

    protected int $voucherBatch = 0;

    protected string $codePrefix = '';

    /**
     * @dataProvider provideCollectionQueries
     *
     * @param array<string, mixed> $query
     */
    public function testReadsTheVoucherCodeCollectionOfTheDiscount(array $query, int $expectedCount): void
    {
        // Arrange
        $this->haveVoucherCodeFixtures();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodeCollectionUrl($this->discountUuid, $this->resolveQuery($query)),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $codes = array_column(
            array_column($this->getJsonApiMembers($response, static::JSON_API_KEY_DATA), static::JSON_API_KEY_ATTRIBUTES),
            static::ATTRIBUTE_CODE,
        );
        $this->assertCount($expectedCount, $codes);

        foreach ($codes as $code) {
            $this->assertStringStartsWith(static::CUSTOM_CODE_PREFIX, (string)$code);
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, int}>
     */
    public function provideCollectionQueries(): array
    {
        return [
            'given no query parameters, when the collection is read, then every code of the discount is returned' => [
                [], self::GENERATED_QUANTITY,
            ],
            'given a prefixed code filter, when the collection is read, then the partial match is applied' => [
                ['filter' => ['discount-voucher-codes.code' => self::CUSTOM_CODE_PREFIX]], self::GENERATED_QUANTITY,
            ],
            'given a code filter nothing matches, when the collection is read, then nothing is returned' => [
                ['filter' => ['discount-voucher-codes.code' => 'GetCollectionNoSuchCode']], 0,
            ],
            'given the voucher batch filter, when the collection is read, then the generated batch is returned' => [
                ['filter' => ['voucherBatch' => '%voucherBatch%']], self::GENERATED_QUANTITY,
            ],
        ];
    }

    /**
     * @dataProvider provideCollectionSorts
     *
     * @param array<int, string> $expectedFixtureKeys
     */
    public function testSortsTheVoucherCodeCollection(string $sortField, bool $isDescending, array $expectedFixtureKeys): void
    {
        // Arrange
        $this->haveSortableVoucherCodeFixtures();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodeCollectionUrl(
                $this->discountUuid,
                ['sort' => ($isDescending ? '-' : '') . $sortField],
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $expectedFixtureKeys,
            $this->mapMembersToFixtureKeys(
                $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA),
                static::ATTRIBUTE_CODE,
                $this->codePrefix,
            ),
        );
    }

    /**
     * @return array<string, array{string, bool, array<int, string>}>
     */
    public function provideCollectionSorts(): array
    {
        $alphaFirst = [self::FIXTURE_ALPHA, self::FIXTURE_ALPHA, self::FIXTURE_BETA, self::FIXTURE_BETA];
        $betaFirst = [self::FIXTURE_BETA, self::FIXTURE_BETA, self::FIXTURE_ALPHA, self::FIXTURE_ALPHA];

        return [
            'given an ascending code sort, when the collection is read, then the codes are ordered' => [
                'code', false, $alphaFirst,
            ],
            'given a descending code sort, when the collection is read, then the codes are reversed' => [
                'code', true, $betaFirst,
            ],
            'given an ascending number of uses sort, when the collection is read, then the unused codes come first' => [
                'numberOfUses', false, $betaFirst,
            ],
            'given a descending number of uses sort, when the collection is read, then the used codes come first' => [
                'numberOfUses', true, $alphaFirst,
            ],
            'given an ascending maximum number of uses sort, when the collection is read, then the lower limit comes first' => [
                'maxNumberOfUses', false, $alphaFirst,
            ],
            'given a descending maximum number of uses sort, when the collection is read, then the higher limit comes first' => [
                'maxNumberOfUses', true, $betaFirst,
            ],
            'given an ascending creation sort, when the collection is read, then the first batch comes first' => [
                'createdAt', false, $alphaFirst,
            ],
            'given a descending creation sort, when the collection is read, then the last batch comes first' => [
                'createdAt', true, $betaFirst,
            ],
            'given an ascending voucher batch sort, when the collection is read, then the first batch comes first' => [
                'voucherBatch', false, $alphaFirst,
            ],
            'given a descending voucher batch sort, when the collection is read, then the last batch comes first' => [
                'voucherBatch', true, $betaFirst,
            ],
        ];
    }

    public function testEveryConfiguredSortableFieldHasASortFixture(): void
    {
        // Assert
        $this->assertSame(
            [],
            array_diff(
                (new DiscountConfig())->getDiscountVoucherCodeCollectionSortableFields(),
                array_column($this->provideCollectionSorts(), 0),
            ),
        );
    }

    /**
     * @dataProvider providePaginationQueries
     *
     * @param array<string, mixed> $pageQuery
     * @param array<int, string> $expectedLinkKeys
     */
    public function testReportsThePaginationInTheDocumentMetaAndLinks(
        array $pageQuery,
        int $expectedItemCount,
        int $expectedCurrentPage,
        int $expectedItemsPerPage,
        int $expectedMaxPage,
        array $expectedLinkKeys,
    ): void {
        // Arrange
        $this->haveVoucherCodeFixtures();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodeCollectionUrl($this->discountUuid, $pageQuery),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $document = $this->decodeJsonApi($response);
        $this->assertCount($expectedItemCount, $document[static::JSON_API_KEY_DATA]);
        $this->assertCollectionPagination(
            $document,
            static::GENERATED_QUANTITY,
            $expectedCurrentPage,
            $expectedMaxPage,
            $expectedItemsPerPage,
            $expectedLinkKeys,
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, int, int, int, int, array<int, string>}>
     */
    public function providePaginationQueries(): array
    {
        return [
            'given no page parameter, when the collection is read, then the resource default window applies' => [
                [], self::GENERATED_QUANTITY, 1, 10, 1, [self::LINK_FIRST, self::LINK_LAST],
            ],
            'given a page limit of two, when the collection is read, then the first page holds two codes and links to the next' => [
                ['page' => ['limit' => '2']], 2, 1, 2, 2, [self::LINK_FIRST, self::LINK_LAST, self::LINK_NEXT],
            ],
            'given a page offset of two, when the collection is read, then the second page is returned and links to the previous' => [
                ['page' => ['limit' => '2', 'offset' => '2']], 1, 2, 2, 2, [self::LINK_FIRST, self::LINK_LAST, self::LINK_PREV],
            ],
            'given a page limit above the resource maximum, when the collection is read, then it is capped' => [
                ['page' => ['limit' => '1000']], self::GENERATED_QUANTITY, 1, self::RESOURCE_MAXIMUM_ITEMS_PER_PAGE, 1, [self::LINK_FIRST, self::LINK_LAST],
            ],
        ];
    }

    /**
     * @dataProvider provideRejectedCollectionRequests
     *
     * @param array<string, mixed> $query
     */
    public function testRejectsACollectionRequestItCannotAnswer(
        bool $isAuthenticated,
        bool $isExistingUuid,
        array $query,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        $discountUuid = $this->haveDiscountAndAuthenticate($isAuthenticated, $isExistingUuid, true);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodeCollectionUrl($discountUuid, $query),
        );

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode === null) {
            return;
        }

        $this->assertRespondsWithErrorCode($response, $expectedStatus, $expectedErrorCode);
    }

    /**
     * @return array<string, array{bool, bool, array<string, mixed>, int, string|null}>
     */
    public function provideRejectedCollectionRequests(): array
    {
        return [
            'given a discount uuid no row carries, when the collection is read, then it responds not found' => [
                true, false, [], Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_DISCOUNT_NOT_FOUND,
            ],
            'given a sort field outside the allow list, when the collection is read, then it responds bad request' => [
                true, true, ['sort' => 'id_discount_voucher; DROP TABLE spy_discount_voucher; --'],
                Response::HTTP_BAD_REQUEST, DiscountConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
            ],
            'given a voucher batch filter that is not an integer, when the collection is read, then it responds unprocessable' => [
                true, true, ['filter' => ['discount-voucher-codes.voucherBatch' => 'abc']],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given no token, when the collection is read, then it responds unauthorized' => [
                false, false, [], Response::HTTP_UNAUTHORIZED, null,
            ],
        ];
    }

    protected function haveVoucherCodeFixtures(): void
    {
        $this->tester->actingAsUser();
        $this->discountUuid = $this->haveVoucherDiscountViaApi();
        $this->voucherBatch = $this->haveGeneratedVoucherCodesViaApi(
            $this->discountUuid,
            static::CUSTOM_CODE_PREFIX,
            static::GENERATED_QUANTITY,
        );
    }

    protected function haveSortableVoucherCodeFixtures(): void
    {
        $this->tester->actingAsUser();
        $this->discountUuid = $this->haveVoucherDiscountViaApi();
        $this->codePrefix = $this->tester->createUniqueVoucherCodePrefix();

        $this->haveGeneratedVoucherCodesViaApi(
            $this->discountUuid,
            $this->codePrefix . static::FIXTURE_ALPHA,
            static::SORT_FIXTURE_QUANTITY,
            static::SORT_FIXTURE_ALPHA_MAX_NUMBER_OF_USES,
        );
        $this->haveGeneratedVoucherCodesViaApi(
            $this->discountUuid,
            $this->codePrefix . static::FIXTURE_BETA,
            static::SORT_FIXTURE_QUANTITY,
            static::SORT_FIXTURE_BETA_MAX_NUMBER_OF_USES,
        );
        $this->tester->haveUsedVoucherCodes(
            $this->codePrefix . static::FIXTURE_ALPHA,
            static::SORT_FIXTURE_ALPHA_NUMBER_OF_USES,
        );
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function resolveQuery(array $query): array
    {
        if (isset($query['filter']['voucherBatch'])) {
            $query['filter']['voucherBatch'] = (string)$this->voucherBatch;
        }

        return $query;
    }
}
