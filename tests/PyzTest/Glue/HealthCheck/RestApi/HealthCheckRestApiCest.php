<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\HealthCheck\RestApi;

use Codeception\Util\HttpCode;
use PyzTest\Glue\HealthCheck\HealthCheckApiTester;
use Spryker\Glue\HealthCheck\HealthCheckConfig;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group HealthCheck
 * @group RestApi
 * @group HealthCheckRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class HealthCheckRestApiCest
{
    /**
     * @var string
     */
    protected const RESPONSE_MESSAGE_DISABLED = 'HealthCheck endpoints are disabled for all applications.';

    /**
     * @var string
     */
    protected const RESPONSE_MESSAGE_BAD_REQUEST = 'Requested services not found.';

    /**
     * @var string
     */
    protected const UNKNOWN_SERVICE_NAME = 'unknown-service';

    /**
     * In the test environment `HealthCheckConfig::isHealthCheckEnabled()` resolves to `false`, so a
     * plain `GET /health-check` must surface that state through the JSON:API body with HTTP 403. The
     * assertions go through the tester's cross-shape helper so the same test stays green once the
     * legacy `HealthCheckResourceRoutePlugin` is unwired and API Platform handles the route.
     *
     * @param \PyzTest\Glue\HealthCheck\HealthCheckApiTester $I
     *
     * @return void
     */
    public function requestGetHealthCheckReturnsForbiddenWhenDisabled(HealthCheckApiTester $I): void
    {
        // Act
        $I->sendGET($I->formatUrl(HealthCheckConfig::RESOURCE_HEALTH_CHECK));

        // Assert
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();

        $I->amSure('The response statusCode mirrors HTTP 403')
            ->whenI()
            ->assertSame(
                HttpCode::FORBIDDEN,
                $I->getHealthCheckAttribute('statusCode'),
            );

        $I->amSure('The response message explains that health-check is disabled')
            ->whenI()
            ->assertSame(
                static::RESPONSE_MESSAGE_DISABLED,
                $I->getHealthCheckAttribute('message'),
            );

        $I->amSure('The response status field is null when disabled')
            ->whenI()
            ->assertNull(
                $I->getHealthCheckAttribute('status'),
            );
    }

    /**
     * Requesting an unknown service short-circuits before the disabled-flag check (validator runs
     * first), so this scenario is independent from `HealthCheckConfig::isHealthCheckEnabled()` and
     * reliably returns HTTP 400 with `Requested services not found.` in any environment.
     *
     * @param \PyzTest\Glue\HealthCheck\HealthCheckApiTester $I
     *
     * @return void
     */
    public function requestGetHealthCheckWithUnknownServiceReturnsBadRequest(HealthCheckApiTester $I): void
    {
        // Act
        $url = $I->formatUrl(HealthCheckConfig::RESOURCE_HEALTH_CHECK) . '?services=' . static::UNKNOWN_SERVICE_NAME;
        $I->sendGET($url);

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();

        $I->amSure('The response statusCode mirrors HTTP 400')
            ->whenI()
            ->assertSame(
                HttpCode::BAD_REQUEST,
                $I->getHealthCheckAttribute('statusCode'),
            );

        $I->amSure('The response message explains the bad request')
            ->whenI()
            ->assertSame(
                static::RESPONSE_MESSAGE_BAD_REQUEST,
                $I->getHealthCheckAttribute('message'),
            );
    }

    /**
     * Pins the JSON:API attribute contract: the `attributes` block always exposes the same four
     * keys (`status`, `statusCode`, `message`, `healthCheckServiceResponses`), regardless of the
     * underlying service state or which transport (legacy Glue REST array shape or API Platform
     * object shape) serves the request.
     *
     * @param \PyzTest\Glue\HealthCheck\HealthCheckApiTester $I
     *
     * @return void
     */
    public function requestGetHealthCheckResponseExposesTheExpectedAttributeShape(HealthCheckApiTester $I): void
    {
        // Act
        $I->sendGET($I->formatUrl(HealthCheckConfig::RESOURCE_HEALTH_CHECK));

        // Assert
        $I->seeResponseIsJson();

        $attributes = $I->getHealthCheckAttributes();

        $I->amSure('The response attributes block exists')
            ->whenI()
            ->assertNotEmpty($attributes);

        $I->assertArrayHasKey('status', $attributes);
        $I->assertArrayHasKey('statusCode', $attributes);
        $I->assertArrayHasKey('message', $attributes);
        $I->assertArrayHasKey('healthCheckServiceResponses', $attributes);

        $I->amSure('The healthCheckServiceResponses field is an array')
            ->whenI()
            ->assertIsArray($attributes['healthCheckServiceResponses']);
    }
}
