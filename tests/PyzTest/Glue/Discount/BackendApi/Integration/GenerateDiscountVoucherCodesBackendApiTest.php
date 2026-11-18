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
 * @group GenerateDiscountVoucherCodesBackendApiTest
 * Add your own group annotations below this line
 */
class GenerateDiscountVoucherCodesBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string CUSTOM_CODE_PREFIX = 'SUITE-';

    protected const int GENERATED_QUANTITY = 3;

    public function testGeneratesTheRequestedNumberOfCodesAndListsThem(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesGenerateRequestBody(
                $this->tester->buildValidVoucherCodesGenerateAttributes([
                    'quantity' => static::GENERATED_QUANTITY,
                    'customCode' => static::CUSTOM_CODE_PREFIX,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($discountUuid, $attributes['discountUuid']);
        $this->assertSame(static::GENERATED_QUANTITY, $attributes['generatedCount']);
        $this->assertNotNull($attributes['voucherBatch']);
        $this->assertCount(static::GENERATED_QUANTITY, $attributes['codes']);
        $this->assertArrayNotHasKey('quantity', $attributes);
        $this->assertArrayNotHasKey('codeLength', $attributes);
        $this->assertArrayNotHasKey('customCode', $attributes);

        $members = $this->getJsonApiMembers(
            $this->handleApiRequest(
                static::METHOD_GET,
                $this->tester->getDiscountVoucherCodeCollectionUrl($discountUuid, [
                    'filter' => ['discount-voucher-codes.voucherBatch' => (string)$attributes['voucherBatch']],
                ]),
            ),
            static::JSON_API_KEY_DATA,
        );

        $this->assertCount(static::GENERATED_QUANTITY, $members);
        $this->assertEqualsCanonicalizing(
            $attributes['codes'],
            array_map(static fn (array $member): string => (string)$member[static::JSON_API_KEY_ATTRIBUTES]['code'], $members),
        );

        foreach ($members as $member) {
            $this->assertStringStartsWith(static::CUSTOM_CODE_PREFIX, (string)$member[static::JSON_API_KEY_ATTRIBUTES]['code']);
        }
    }

    public function testListsOnlyTheCodesCreatedByTheRequestWhenSingleCodesShareBatchZero(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();
        $firstCode = $this->tester->createUniqueVoucherCustomCode();
        $secondCode = $this->tester->createUniqueVoucherCustomCode();
        $this->generateSingleCustomCode($discountUuid, $firstCode);

        // Act
        $response = $this->generateSingleCustomCode($discountUuid, '  ' . $secondCode . '  ');

        // Assert
        $attributes = $this->getResourceAttributes($response);
        $this->assertSame(0, $attributes['voucherBatch']);
        $this->assertSame(1, $attributes['generatedCount']);
        $this->assertSame([$secondCode], $attributes['codes']);
    }

    protected function generateSingleCustomCode(string $discountUuid, string $customCode): Response
    {
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesGenerateRequestBody([
                'quantity' => 1,
                'codeLength' => null,
                'customCode' => $customCode,
            ]),
        );

        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        return $response;
    }

    /**
     * @dataProvider provideRejectedGenerateRequests
     *
     * @param array<string, mixed> $override
     */
    public function testRejectsAGenerateRequestItCannotAnswer(
        bool $isAuthenticated,
        bool $isExistingUuid,
        bool $isVoucherDiscount,
        array $override,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        $discountUuid = $this->haveDiscountAndAuthenticate($isAuthenticated, $isExistingUuid, $isVoucherDiscount);

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesGenerateRequestBody(
                $this->tester->buildValidVoucherCodesGenerateAttributes($override),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode === null) {
            return;
        }

        $this->assertRespondsWithErrorCode($response, $expectedStatus, $expectedErrorCode);
    }

    /**
     * @return array<string, array{bool, bool, bool, array<string, mixed>, int, string|null}>
     */
    public function provideRejectedGenerateRequests(): array
    {
        return [
            'given a cart rule discount, when codes are generated, then the voucher type code answers' => [
                true, true, false, [],
                Response::HTTP_UNPROCESSABLE_ENTITY, DiscountConfig::RESPONSE_CODE_VOUCHER_DISCOUNT_TYPE,
            ],
            'given a quantity of zero, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['quantity' => 0],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a quantity above the configured maximum, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['quantity' => 14001],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a code length below the configured minimum, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['codeLength' => 2],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a code length above the configured maximum, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['codeLength' => 11],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given neither a code length nor a custom code, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['codeLength' => null, 'customCode' => null],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a custom code that cannot fit the code column with the random part, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['customCode' => str_repeat('c', 246)],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a negative maximum number of uses, when codes are generated, then the framework validation code answers' => [
                true, true, true, ['maxNumberOfUses' => -1],
                Response::HTTP_UNPROCESSABLE_ENTITY, self::RESPONSE_CODE_FRAMEWORK_VALIDATION,
            ],
            'given a uuid no discount carries, when codes are generated, then it responds not found' => [
                true, false, true, [],
                Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_DISCOUNT_NOT_FOUND,
            ],
            'given no token, when codes are generated, then it responds unauthorized' => [
                false, false, true, [],
                Response::HTTP_UNAUTHORIZED, null,
            ],
        ];
    }

    public function testReportsTheGenerationFailureWhenEveryRequestedCodeCollides(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();

        $attributes = $this->tester->buildValidVoucherCodesGenerateAttributes([
            'quantity' => 1,
            'codeLength' => null,
            'customCode' => $this->tester->createUniqueVoucherCustomCode(),
        ]);

        $this->assertRespondsWithStatus(
            $this->handleApiRequest(
                static::METHOD_POST,
                $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
                $this->tester->buildDiscountVoucherCodesGenerateRequestBody($attributes),
            ),
            Response::HTTP_CREATED,
        );

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesGenerateRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            DiscountConfig::RESPONSE_CODE_VOUCHER_CODE_GENERATION_FAILED,
        );
    }

    public function testCreatesNoVoucherPoolForACartRuleDiscount(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveDiscountViaApi();

        // Act
        $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesGenerateUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesGenerateRequestBody(
                $this->tester->buildValidVoucherCodesGenerateAttributes(),
            ),
        );

        // Assert
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodeCollectionUrl($discountUuid),
        );

        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(0, $this->getJsonApiMembers($response, static::JSON_API_KEY_DATA));
    }
}
