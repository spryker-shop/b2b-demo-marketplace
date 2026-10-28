<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OauthBackendApi\BackendApi\Integration;

use PyzTest\Glue\OauthBackendApi\OauthBackendApiBackendApiIntegrationTester;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group Integration
 * @group MerchantUserRoleAuthorizationBackendApiTest
 * Add your own group annotations below this line
 */
class MerchantUserRoleAuthorizationBackendApiTest extends BackendApiTestCase
{
    protected const array BACK_OFFICE_ONLY_URLS = [
        '/customers',
        '/categories',
        '/categories/computer/products',
        '/products',
    ];

    protected OauthBackendApiBackendApiIntegrationTester $tester;

    /**
     * @dataProvider backOfficeOnlyUrlDataProvider
     */
    public function testGivenMerchantUserTokenWhenReadingABackOfficeResourceThenRespondsForbidden(string $url): void
    {
        // Arrange
        $this->tester->actingAsMerchantUser();

        // Act
        $response = $this->handleApiRequest('GET', $url);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testGivenBackOfficeUserTokenWhenReadingABackOfficeResourceThenRespondsOk(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', '/categories');

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
    }

    /**
     * @return array<string, array<string>>
     */
    public function backOfficeOnlyUrlDataProvider(): array
    {
        $data = [];

        foreach (static::BACK_OFFICE_ONLY_URLS as $url) {
            $data[$url] = [$url];
        }

        return $data;
    }
}
