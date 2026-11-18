<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount\BackendApi\Integration;

use PyzTest\Glue\Discount\AbstractDiscountBackendApiTestCase;
use Spryker\Glue\Discount\DiscountConfig;
use Spryker\Shared\Discount\DiscountConstants;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Discount
 * @group BackendApi
 * @group Integration
 * @group GetCollectionDiscountsBackendApiTest
 * Add your own group annotations below this line
 */
class GetCollectionDiscountsBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string FIXTURE_ALPHA = 'a';

    protected const string FIXTURE_BETA = 'b';

    protected const string FIXTURE_GAMMA = 'c';

    protected const string VALID_FROM_EARLY = '2026-01-01 00:00:00';

    protected const string VALID_TO_EARLY = '2026-06-30 23:59:59';

    protected const string VALID_FROM_LATE = '2027-01-01 00:00:00';

    protected const string VALID_TO_LATE = '2027-06-30 23:59:59';

    protected const string ATTRIBUTE_DISPLAY_NAME = 'displayName';

    protected string $displayNamePrefix = '';

    /**
     * @dataProvider provideCollectionFilters
     *
     * @param array<string, mixed> $filters
     * @param array<int, string> $expectedFixtureKeys
     */
    public function testFiltersTheDiscountCollection(array $filters, array $expectedFixtureKeys): void
    {
        // Arrange
        $this->haveDiscountFixtures();

        // Act
        $members = $this->getDiscountCollectionMembers([
            'filter' => ['discounts.displayName' => $this->displayNamePrefix] + $filters,
            'sort' => static::ATTRIBUTE_DISPLAY_NAME,
        ]);

        // Assert
        $this->assertSame($expectedFixtureKeys, $this->mapMembersToDiscountFixtureKeys($members));
    }

    /**
     * @return array<string, array{array<string, mixed>, array<int, string>}>
     */
    public function provideCollectionFilters(): array
    {
        return [
            'given only the display name filter, when the collection is read, then every fixture is returned' => [
                [],
                [self::FIXTURE_ALPHA, self::FIXTURE_BETA, self::FIXTURE_GAMMA],
            ],
            'given the voucher type filter, when the collection is read, then only voucher discounts are returned' => [
                ['discounts.discountType' => DiscountConstants::TYPE_VOUCHER],
                [self::FIXTURE_BETA],
            ],
            'given the bare property filter form, when the collection is read, then it is accepted too' => [
                ['discountType' => DiscountConstants::TYPE_VOUCHER],
                [self::FIXTURE_BETA],
            ],
            'given the active filter, when the collection is read, then only activated discounts are returned' => [
                ['discounts.isActive' => 'true'],
                [self::FIXTURE_BETA],
            ],
            'given the inactive filter, when the collection is read, then only the others are returned' => [
                ['discounts.isActive' => 'false'],
                [self::FIXTURE_ALPHA, self::FIXTURE_GAMMA],
            ],
            'given a store filter, when the collection is read, then only discounts of that store are returned' => [
                ['discounts.store' => 'AT'],
                [self::FIXTURE_GAMMA],
            ],
            'given a valid from filter, when the collection is read, then only discounts starting later are returned' => [
                ['discounts.validFrom' => self::VALID_FROM_LATE],
                [self::FIXTURE_GAMMA],
            ],
            'given a valid to filter, when the collection is read, then only discounts ending earlier are returned' => [
                ['discounts.validTo' => self::VALID_TO_EARLY],
                [self::FIXTURE_ALPHA, self::FIXTURE_BETA],
            ],
        ];
    }

    /**
     * @dataProvider provideCollectionSorts
     *
     * @param array<int, string> $expectedFixtureKeys
     */
    public function testSortsTheDiscountCollection(string $sortField, bool $isDescending, array $expectedFixtureKeys): void
    {
        // Arrange
        $this->haveDiscountFixtures();

        // Act
        $members = $this->getDiscountCollectionMembers([
            'filter' => ['discounts.displayName' => $this->displayNamePrefix],
            'sort' => ($isDescending ? '-' : '') . $sortField,
        ]);

        // Assert
        $this->assertSame($expectedFixtureKeys, $this->mapMembersToDiscountFixtureKeys($members));
    }

    /**
     * @return array<string, array{string, bool, array<int, string>}>
     */
    public function provideCollectionSorts(): array
    {
        return [
            'given an ascending display name sort, when the collection is read, then the names are ordered' => [
                'displayName', false, [self::FIXTURE_ALPHA, self::FIXTURE_BETA, self::FIXTURE_GAMMA],
            ],
            'given a descending display name sort, when the collection is read, then the names are reversed' => [
                'displayName', true, [self::FIXTURE_GAMMA, self::FIXTURE_BETA, self::FIXTURE_ALPHA],
            ],
            'given an ascending activity sort, when the collection is read, then the inactive discounts come first in creation order' => [
                'isActive', false, [self::FIXTURE_ALPHA, self::FIXTURE_GAMMA, self::FIXTURE_BETA],
            ],
            'given a descending activity sort, when the collection is read, then the active discount comes first and the others follow newest first' => [
                'isActive', true, [self::FIXTURE_BETA, self::FIXTURE_GAMMA, self::FIXTURE_ALPHA],
            ],
            'given an ascending exclusivity sort, when the collection is read, then the exclusive discount comes last' => [
                'isExclusive', false, [self::FIXTURE_ALPHA, self::FIXTURE_BETA, self::FIXTURE_GAMMA],
            ],
            'given a descending exclusivity sort, when the collection is read, then the exclusive discount comes first' => [
                'isExclusive', true, [self::FIXTURE_GAMMA, self::FIXTURE_BETA, self::FIXTURE_ALPHA],
            ],
            'given an ascending priority sort, when the collection is read, then the priorities are ordered' => [
                'priority', false, [self::FIXTURE_ALPHA, self::FIXTURE_BETA, self::FIXTURE_GAMMA],
            ],
            'given a descending priority sort, when the collection is read, then the priorities are reversed' => [
                'priority', true, [self::FIXTURE_GAMMA, self::FIXTURE_BETA, self::FIXTURE_ALPHA],
            ],
            'given an ascending creation sort, when the collection is read, then the oldest discount comes first' => [
                'createdAt', false, [self::FIXTURE_ALPHA, self::FIXTURE_BETA, self::FIXTURE_GAMMA],
            ],
            'given a descending creation sort, when the collection is read, then the newest discount comes first' => [
                'createdAt', true, [self::FIXTURE_GAMMA, self::FIXTURE_BETA, self::FIXTURE_ALPHA],
            ],
        ];
    }

    public function testEveryConfiguredSortableFieldHasASortFixture(): void
    {
        // Assert
        $this->assertSame(
            [],
            array_diff(
                (new DiscountConfig())->getDiscountCollectionSortableFields(),
                array_column($this->provideCollectionSorts(), 0),
            ),
        );
    }

    public function testRejectsASortFieldOutsideTheAllowList(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountCollectionUrl(['sort' => 'id_discount; DROP TABLE spy_discount; --']),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_BAD_REQUEST,
            DiscountConfig::RESPONSE_CODE_INVALID_SORT_FIELD,
        );
    }

    /**
     * @dataProvider provideUnparsableFilterQueries
     *
     * @param array<string, mixed> $filters
     */
    public function testRejectsAFilterValueItCannotParse(array $filters): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountCollectionUrl(['filter' => $filters]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_FRAMEWORK_VALIDATION);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public function provideUnparsableFilterQueries(): array
    {
        return [
            'given a valid from filter that is not a date, when the collection is read, then it is rejected' => [
                ['discounts.validFrom' => 'abc'],
            ],
            'given a valid to filter without a time, when the collection is read, then it is rejected' => [
                ['discounts.validTo' => '2026-12-31'],
            ],
            'given an active filter that is not a boolean, when the collection is read, then it is rejected' => [
                ['discounts.isActive' => 'maybe'],
            ],
        ];
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
        $this->haveDiscountFixtures();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountCollectionUrl([
                'filter' => ['discounts.displayName' => $this->displayNamePrefix],
                'sort' => static::ATTRIBUTE_DISPLAY_NAME,
            ] + $pageQuery),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $document = $this->decodeJsonApi($response);
        $this->assertCount($expectedItemCount, $document[static::JSON_API_KEY_DATA]);
        $this->assertCollectionPagination(
            $document,
            3,
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
                [], 3, 1, 10, 1, [self::LINK_FIRST, self::LINK_LAST],
            ],
            'given a page limit of two, when the collection is read, then the first page holds two items and links to the next' => [
                ['page' => ['limit' => '2']], 2, 1, 2, 2, [self::LINK_FIRST, self::LINK_LAST, self::LINK_NEXT],
            ],
            'given a page offset of two, when the collection is read, then the second page is returned and links to the previous' => [
                ['page' => ['limit' => '2', 'offset' => '2']], 1, 2, 2, 2, [self::LINK_FIRST, self::LINK_LAST, self::LINK_PREV],
            ],
            'given a page limit above the resource maximum, when the collection is read, then it is capped' => [
                ['page' => ['limit' => '1000']], 3, 1, self::RESOURCE_MAXIMUM_ITEMS_PER_PAGE, 1, [self::LINK_FIRST, self::LINK_LAST],
            ],
        ];
    }

    public function testOmitsTheVoucherCodesExportLinkFromEveryCollectionMember(): void
    {
        // Arrange
        $this->haveDiscountFixtures();

        // Act
        $members = $this->getDiscountCollectionMembers([
            'filter' => ['discounts.displayName' => $this->displayNamePrefix],
            'sort' => static::ATTRIBUTE_DISPLAY_NAME,
        ]);

        // Assert
        $this->assertCount(3, $members);

        foreach ($members as $member) {
            $links = (array)($member[static::JSON_API_KEY_LINKS] ?? []);
            $this->assertArrayHasKey(static::JSON_API_KEY_SELF, $links);
            $this->assertArrayNotHasKey(static::LINK_VOUCHER_CODES_EXPORT, $links);
        }
    }

    public function testRejectsARequestWithoutAToken(): void
    {
        // Act
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    protected function haveDiscountFixtures(): void
    {
        $this->tester->actingAsUser();
        $this->displayNamePrefix = $this->tester->createUniqueDisplayName();

        $this->haveDiscountViaApi([
            'displayName' => $this->displayNamePrefix . static::FIXTURE_ALPHA,
            'discountType' => DiscountConstants::TYPE_CART_RULE,
            'isExclusive' => false,
            'priority' => 10,
            'validFrom' => static::VALID_FROM_EARLY,
            'validTo' => static::VALID_TO_EARLY,
            'stores' => [$this->tester->getStoreName()],
        ]);
        $this->haveActiveDiscountViaApi($this->tester->buildValidVoucherDiscountAttributes([
            'displayName' => $this->displayNamePrefix . static::FIXTURE_BETA,
            'isExclusive' => false,
            'priority' => 20,
            'validFrom' => static::VALID_FROM_EARLY,
            'validTo' => static::VALID_TO_EARLY,
            'stores' => [$this->tester->getStoreName()],
        ]));
        $this->haveDiscountViaApi([
            'displayName' => $this->displayNamePrefix . static::FIXTURE_GAMMA,
            'discountType' => DiscountConstants::TYPE_CART_RULE,
            'isExclusive' => true,
            'priority' => 30,
            'validFrom' => static::VALID_FROM_LATE,
            'validTo' => static::VALID_TO_LATE,
            'stores' => [$this->tester->getSecondStoreName()],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $members
     *
     * @return array<int, string>
     */
    protected function mapMembersToDiscountFixtureKeys(array $members): array
    {
        return $this->mapMembersToFixtureKeys($members, static::ATTRIBUTE_DISPLAY_NAME, $this->displayNamePrefix);
    }
}
