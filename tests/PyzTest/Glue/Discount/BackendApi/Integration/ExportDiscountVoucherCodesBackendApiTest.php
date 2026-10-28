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
 * @group ExportDiscountVoucherCodesBackendApiTest
 * Add your own group annotations below this line
 */
class ExportDiscountVoucherCodesBackendApiTest extends AbstractDiscountBackendApiTestCase
{
    protected const string CUSTOM_CODE_PREFIX = 'EXPORT-';

    protected const int GENERATED_QUANTITY = 3;

    protected const string HEADER_ROW = 'code,maxNumberOfUses,numberOfUses,isActive';

    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    protected const string HEADER_CONTENT_DISPOSITION = 'Content-Disposition';

    protected const string CONTENT_TYPE = 'text/csv; charset=UTF-8';

    protected const string CONTENT_DISPOSITION = 'attachment; filename="vouchers.csv"';

    /**
     * @dataProvider provideExportedDiscounts
     */
    public function testStreamsTheVoucherCodesOfTheDiscountAsCsv(bool $hasGeneratedCodes, int $expectedRowCount): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $discountUuid = $this->haveVoucherDiscountViaApi();

        if ($hasGeneratedCodes) {
            $this->haveGeneratedVoucherCodesViaApi($discountUuid, static::CUSTOM_CODE_PREFIX, static::GENERATED_QUANTITY);
        }

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodesExportUrl($discountUuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(static::CONTENT_TYPE, $response->headers->get(static::HEADER_CONTENT_TYPE));
        $this->assertSame(static::CONTENT_DISPOSITION, $response->headers->get(static::HEADER_CONTENT_DISPOSITION));

        $rows = $this->readCsvRows($response);
        $this->assertSame(static::HEADER_ROW, $rows[0]);
        $this->assertCount($expectedRowCount, $rows);

        foreach (array_slice($rows, 1) as $row) {
            $this->assertStringStartsWith(static::CUSTOM_CODE_PREFIX, $row);
        }
    }

    /**
     * @return array<string, array{bool, int}>
     */
    public function provideExportedDiscounts(): array
    {
        return [
            'given a voucher discount without codes, when the export is read, then only the header row is streamed' => [
                false, 1,
            ],
            'given a voucher discount with three codes, when the export is read, then one row per code follows the header' => [
                true, self::GENERATED_QUANTITY + 1,
            ],
        ];
    }

    /**
     * @dataProvider provideRejectedExportRequests
     */
    public function testRejectsAnExportRequestItCannotAnswer(
        bool $isAuthenticated,
        int $expectedStatus,
        ?string $expectedErrorCode,
    ): void {
        // Arrange
        if ($isAuthenticated) {
            $this->tester->actingAsUser();
        }

        // Act
        $response = $this->handleApiRequest(
            static::METHOD_GET,
            $this->tester->getDiscountVoucherCodesExportUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithStatus($response, $expectedStatus);

        if ($expectedErrorCode === null) {
            return;
        }

        $this->assertRespondsWithErrorCode($response, $expectedStatus, $expectedErrorCode);
    }

    /**
     * @return array<string, array{bool, int, string|null}>
     */
    public function provideRejectedExportRequests(): array
    {
        return [
            'given an unknown discount uuid, when the export is read, then it responds not found' => [
                true, Response::HTTP_NOT_FOUND, DiscountConfig::RESPONSE_CODE_DISCOUNT_NOT_FOUND,
            ],
            'given no token, when the export is read, then it responds unauthorized' => [
                false, Response::HTTP_UNAUTHORIZED, null,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    /**
     * @return array<int, string>
     */
    protected function readCsvRows(Response $response): array
    {
        return explode("\n", trim((string)$response->getContent(), "\n"));
    }
}
