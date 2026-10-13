<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Discount;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractDiscountBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_UUID = '11111111-2222-3333-4444-555555555555';

    protected const string UNKNOWN_VOUCHER_CODE = 'discount-backend-api-does-not-exist';

    /**
     * @uses \Spryker\ApiPlatform\EventSubscriber\GlueApiExceptionSubscriber::ERROR_CODE_VALIDATION
     */
    protected const string RESPONSE_CODE_FRAMEWORK_VALIDATION = '901';

    protected const string ATTRIBUTE_PAGINATION = 'pagination';

    protected const string CONDITION_AND = 'and';

    /**
     * @uses \Spryker\ApiPlatform\ResponseTransform\PaginationLinksTransform::META_PAGINATION
     */
    protected const string JSON_API_KEY_META = 'meta';

    protected const string META_KEY_PAGINATION = 'pagination';

    protected const string PAGINATION_KEY_NUM_FOUND = 'numFound';

    protected const string PAGINATION_KEY_CURRENT_PAGE = 'currentPage';

    protected const string PAGINATION_KEY_MAX_PAGE = 'maxPage';

    protected const string PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE = 'currentItemsPerPage';

    protected const string LINK_FIRST = 'first';

    protected const string LINK_LAST = 'last';

    protected const string LINK_PREV = 'prev';

    protected const string LINK_NEXT = 'next';

    /**
     * @uses \Spryker\Glue\Discount\Api\Backend\Mapper\DiscountResourceMapper
     */
    protected const string LINK_VOUCHER_CODES_EXPORT = 'voucherCodesExport';

    protected const int RESOURCE_MAXIMUM_ITEMS_PER_PAGE = 100;

    protected const string UNKNOWN_CALCULATOR_PLUGIN = 'PLUGIN_CALCULATOR_DOES_NOT_EXIST';

    protected const string METHOD_GET = 'GET';

    protected const string METHOD_POST = 'POST';

    protected const string METHOD_PATCH = 'PATCH';

    protected const string METHOD_DELETE = 'DELETE';

    protected DiscountBackendApiIntegrationTester $tester;

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveDiscountViaApi(array $attributes = []): string
    {
        $this->tester->haveStoresForDiscounts();

        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountCollectionUrl(),
            $this->tester->buildDiscountRequestBody($this->tester->buildValidDiscountAttributes($attributes)),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the discount under test: %s', (string)$response->getContent()),
        );

        return (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveVoucherDiscountViaApi(array $attributes = []): string
    {
        return $this->haveDiscountViaApi($this->tester->buildValidVoucherDiscountAttributes($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveActiveDiscountViaApi(array $attributes = []): string
    {
        $uuid = $this->haveDiscountViaApi($attributes);

        $response = $this->handleApiRequest(
            static::METHOD_PATCH,
            $this->tester->getDiscountUrl($uuid),
            $this->tester->buildDiscountRequestBody(['isActive' => true], $uuid),
        );

        $this->assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            sprintf('Could not activate the discount under test: %s', (string)$response->getContent()),
        );

        return $uuid;
    }

    protected function haveGeneratedVoucherCodesViaApi(
        string $discountUuid,
        string $customCode,
        int $quantity,
        ?int $maxNumberOfUses = null,
    ): int {
        $override = ['quantity' => $quantity, 'customCode' => $customCode];

        if ($maxNumberOfUses !== null) {
            $override['maxNumberOfUses'] = $maxNumberOfUses;
        }

        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesGenerateRequestBody(
                $this->tester->buildValidVoucherCodesGenerateAttributes($override),
            ),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the voucher codes under test: %s', (string)$response->getContent()),
        );

        return (int)$this->getResourceAttributes($response)['voucherBatch'];
    }

    /**
     * @param array<int, array<string, mixed>> $members
     *
     * @return array<int, string>
     */
    protected function mapMembersToFixtureKeys(array $members, string $attribute, string $prefix): array
    {
        return array_map(
            static fn (array $member): string => substr(
                (string)$member[static::JSON_API_KEY_ATTRIBUTES][$attribute],
                strlen($prefix),
                1,
            ),
            $members,
        );
    }

    protected function haveDiscountAndAuthenticate(
        bool $isAuthenticated,
        bool $isExistingUuid,
        bool $isVoucherDiscount = false,
    ): string {
        if (!$isAuthenticated) {
            return static::UNKNOWN_UUID;
        }

        $this->tester->actingAsUser();

        if (!$isExistingUuid) {
            return static::UNKNOWN_UUID;
        }

        return $isVoucherDiscount ? $this->haveVoucherDiscountViaApi() : $this->haveDiscountViaApi();
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDiscountCollectionMembers(array $query): array
    {
        $response = $this->handleApiRequest(static::METHOD_GET, $this->tester->getDiscountCollectionUrl($query));

        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        return $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA);
    }

    /**
     * A rule group holding a single expression carries no condition once it has been round-tripped
     * through the persisted query string, so the default condition is normalised here.
     *
     * @param array<string, mixed> $ruleGroup
     *
     * @return array<string, mixed>
     */
    protected function flattenRuleGroup(array $ruleGroup): array
    {
        return [
            'condition' => $ruleGroup['condition'] ?? static::CONDITION_AND,
            'rules' => array_map(
                static fn (array $rule): array => [
                    'field' => $rule['field'] ?? null,
                    'attribute' => $rule['attribute'] ?? null,
                    'operator' => $rule['operator'] ?? null,
                    'value' => $rule['value'] ?? null,
                ],
                (array)($ruleGroup['rules'] ?? []),
            ),
            'groups' => array_map(
                fn (array $nestedRuleGroup): array => $this->flattenRuleGroup($nestedRuleGroup),
                (array)($ruleGroup['groups'] ?? []),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $document
     * @param array<int, string> $expectedLinkKeys
     */
    protected function assertCollectionPagination(
        array $document,
        int $expectedNumFound,
        int $expectedCurrentPage,
        int $expectedMaxPage,
        int $expectedItemsPerPage,
        array $expectedLinkKeys,
    ): void {
        $this->assertSame(
            [
                static::PAGINATION_KEY_NUM_FOUND => $expectedNumFound,
                static::PAGINATION_KEY_CURRENT_PAGE => $expectedCurrentPage,
                static::PAGINATION_KEY_MAX_PAGE => $expectedMaxPage,
                static::PAGINATION_KEY_CURRENT_ITEMS_PER_PAGE => $expectedItemsPerPage,
            ],
            $document[static::JSON_API_KEY_META][static::META_KEY_PAGINATION] ?? null,
        );

        $links = (array)($document[static::JSON_API_KEY_LINKS] ?? []);

        foreach ([static::LINK_FIRST, static::LINK_LAST, static::LINK_PREV, static::LINK_NEXT] as $linkKey) {
            in_array($linkKey, $expectedLinkKeys, true)
                ? $this->assertArrayHasKey($linkKey, $links)
                : $this->assertArrayNotHasKey($linkKey, $links);
        }

        foreach ((array)($document[static::JSON_API_KEY_DATA] ?? []) as $member) {
            $this->assertArrayNotHasKey(
                static::ATTRIBUTE_PAGINATION,
                (array)($member[static::JSON_API_KEY_ATTRIBUTES] ?? []),
            );
        }
    }
}
