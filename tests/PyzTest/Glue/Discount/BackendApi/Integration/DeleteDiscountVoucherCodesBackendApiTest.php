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
 * @group DeleteDiscountVoucherCodesBackendApiTest
 * Add your own group annotations below this line
 */
class DeleteDiscountVoucherCodesBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string CUSTOM_CODE_PREFIX = 'DEL-';

    protected const string FOREIGN_CODE_PREFIX = 'FOREIGN-';

    protected const int GENERATED_QUANTITY = 3;

    public function testDeletesTheListedCodesOfItsOwnDiscount(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();
        $codes = $this->haveVoucherCodes($discountUuid, static::CUSTOM_CODE_PREFIX);

        // Act
        $response = $this->deleteVoucherCodes($discountUuid, ['codes' => [$codes[0], $codes[2]]]);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertSame([$codes[1]], $this->readVoucherCodes($discountUuid));
    }

    public function testDeletesEveryCodeOfTheDiscountWhenAllIsRequested(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();
        $this->haveVoucherCodes($discountUuid, static::CUSTOM_CODE_PREFIX);
        $otherDiscountUuid = $this->haveVoucherDiscountViaApi();
        $otherCodes = $this->haveVoucherCodes($otherDiscountUuid, static::FOREIGN_CODE_PREFIX);

        // Act
        $response = $this->deleteVoucherCodes($discountUuid, ['all' => true]);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
        $this->assertSame([], $this->readVoucherCodes($discountUuid));
        $this->assertSame($otherCodes, $this->readVoucherCodes($otherDiscountUuid));
    }

    public function testDeletingAllCodesOfAnEmptyPoolIsIdempotent(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();

        // Act
        $response = $this->deleteVoucherCodes($discountUuid, ['all' => true]);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_NO_CONTENT);
    }

    public function testDeletesNothingWhenOneListedCodeBelongsToAnotherDiscount(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();
        $ownCodes = $this->haveVoucherCodes($discountUuid, static::CUSTOM_CODE_PREFIX);
        $foreignCode = $this->haveVoucherCodes($this->haveVoucherDiscountViaApi(), static::FOREIGN_CODE_PREFIX)[0];

        // Act
        $response = $this->deleteVoucherCodes($discountUuid, ['codes' => [$ownCodes[0], $foreignCode, static::UNKNOWN_VOUCHER_CODE]]);

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_VOUCHER_CODE_NOT_FOUND);
        $this->assertSame($ownCodes, $this->readVoucherCodes($discountUuid));

        $errors = $this->decodeJsonApi($response)['errors'];
        $this->assertCount(2, $errors);
        $this->assertStringStartsWith(DiscountConfig::ATTRIBUTE_CODES . '.1 => ', (string)$errors[0]['detail']);
        $this->assertStringContainsString($foreignCode, (string)$errors[0]['detail']);
        $this->assertStringStartsWith(DiscountConfig::ATTRIBUTE_CODES . '.2 => ', (string)$errors[1]['detail']);
    }

    /**
     * @dataProvider provideRejectedDeleteRequests
     *
     * @param array<string, mixed> $attributes
     */
    public function testRejectsADeleteRequestItCannotAnswer(
        bool $isAuthenticated,
        bool $isExistingDiscount,
        array $attributes,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        $discountUuid = static::UNKNOWN_UUID;
        $ownCodes = [];

        if ($isAuthenticated) {
            $this->tester->actingAsUser();

            if ($isExistingDiscount) {
                $discountUuid = $this->haveVoucherDiscountViaApi();
                $ownCodes = $this->haveVoucherCodes($discountUuid, static::CUSTOM_CODE_PREFIX);
            }
        }

        // Act
        $response = $this->deleteVoucherCodes($discountUuid, $attributes);

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode !== null) {
            $this->assertRespondsWithErrorCode($response, $expectedStatus, $expectedErrorCode);
        }

        if ($ownCodes === []) {
            return;
        }

        $this->assertSame($ownCodes, $this->readVoucherCodes($discountUuid));
    }

    /**
     * @return array<string, array{bool, bool, array<string, mixed>, int, string|null}>
     */
    public function provideRejectedDeleteRequests(): array
    {
        return [
            'given neither codes nor all, when codes are deleted, then the request is invalid' => [
                true, true, [], Response::HTTP_BAD_REQUEST, DiscountConfig::RESPONSE_CODE_VOUCHER_CODES_DELETE_REQUEST_INVALID,
            ],
            'given an empty code list, when codes are deleted, then the request is invalid' => [
                true, true, ['codes' => []], Response::HTTP_BAD_REQUEST, DiscountConfig::RESPONSE_CODE_VOUCHER_CODES_DELETE_REQUEST_INVALID,
            ],
            'given both codes and all, when codes are deleted, then the request is invalid' => [
                true, true, ['codes' => [self::UNKNOWN_VOUCHER_CODE], 'all' => true], Response::HTTP_BAD_REQUEST, DiscountConfig::RESPONSE_CODE_VOUCHER_CODES_DELETE_REQUEST_INVALID,
            ],
            'given all false without codes, when codes are deleted, then the request is invalid' => [
                true, true, ['all' => false], Response::HTTP_BAD_REQUEST, DiscountConfig::RESPONSE_CODE_VOUCHER_CODES_DELETE_REQUEST_INVALID,
            ],
            'given a code no discount carries, when codes are deleted, then the voucher code is not found' => [
                true, true, ['codes' => [self::UNKNOWN_VOUCHER_CODE]], Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_VOUCHER_CODE_NOT_FOUND,
            ],
            'given a discount uuid no row carries, when codes are deleted, then the discount is not found' => [
                true, false, ['all' => true], Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_DISCOUNT_NOT_FOUND,
            ],
            'given no token, when codes are deleted, then it responds unauthorized' => [
                false, false, ['all' => true], Response::HTTP_UNAUTHORIZED, null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function deleteVoucherCodes(string $discountUuid, array $attributes): Response
    {
        return $this->handleApiRequest(
            static::METHOD_POST,
            $this->tester->getDiscountVoucherCodesDeleteUrl($discountUuid),
            $this->tester->buildDiscountVoucherCodesDeleteRequestBody($attributes),
        );
    }

    /**
     * @return array<int, string>
     */
    protected function haveVoucherCodes(string $discountUuid, string $customCode): array
    {
        $this->haveGeneratedVoucherCodesViaApi($discountUuid, $customCode, static::GENERATED_QUANTITY);

        return $this->readVoucherCodes($discountUuid);
    }

    /**
     * @return array<int, string>
     */
    protected function readVoucherCodes(string $discountUuid): array
    {
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodeCollectionUrl($discountUuid, ['sort' => 'code']),
        );

        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        return array_column(
            array_column($this->getJsonApiMembers($response, static::JSON_API_KEY_DATA), static::JSON_API_KEY_ATTRIBUTES),
            'code',
        );
    }
}
