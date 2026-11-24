<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OauthBackendApi\BackendApi\Integration;

use Generated\Shared\Transfer\UserTransfer;
use PyzTest\Glue\OauthBackendApi\OauthBackendApiBackendApiIntegrationTester;
use Spryker\Client\SecurityBlockerBackoffice\SecurityBlockerBackofficeConfig;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proves through the real backend kernel that `POST /token` is throttled per user account: the auto-registered
 * subscriber runs before the token endpoint for both the JSON:API `tokens` resource and the legacy OAuth body.
 * The endpoint was not throttled at all before.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OauthBackendApi
 * @group BackendApi
 * @group Integration
 * @group TokenLoginThrottlingBackendApiTest
 * Add your own group annotations below this line
 */
class TokenLoginThrottlingBackendApiTest extends BackendApiTestCase
{
    protected const string PASSWORD = 'Change123!';

    protected const string WRONG_PASSWORD = 'not-the-password';

    protected const string CONTENT_TYPE_JSON_API = 'application/vnd.api+json';

    protected const string CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

    /**
     * @uses \Spryker\Glue\SecurityBlockerRestApi\SecurityBlockerRestApiConfig::ERROR_RESPONSE_CODE_ACCOUNT_BLOCKED
     */
    protected const string ERROR_CODE_ACCOUNT_BLOCKED = '4401';

    protected OauthBackendApiBackendApiIntegrationTester $tester;

    /**
     * Every attempt comes from its own client address, so only the account counter can block the last one.
     */
    public function testGivenTheAccountLimitIsReachedWhenTheCorrectPasswordArrivesFromAnotherIpThenTheTokensResourceRespondsTooManyRequests(): void
    {
        // Arrange
        $username = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD])->getUsernameOrFail();
        $accountNumberOfAttempts = $this->getAccountNumberOfAttempts();

        for ($i = 0; $i < $accountNumberOfAttempts; $i++) {
            $this->assertSame(
                Response::HTTP_UNAUTHORIZED,
                $this->postJsonApiToken(sprintf('203.0.113.%d', $i), $username, static::WRONG_PASSWORD)->getStatusCode(),
            );
        }

        // Act
        $response = $this->postJsonApiToken('198.51.100.7', mb_strtoupper($username), static::PASSWORD);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame(static::ERROR_CODE_ACCOUNT_BLOCKED, json_decode((string)$response->getContent(), true)['errors'][0]['code'] ?? null);
    }

    /**
     * Every attempt comes from its own client address, so only the account counter can block the last one. From a
     * single address the per-IP limit of the `backend-api-user` type would block first wherever it is the lower one.
     */
    public function testGivenTheAccountLimitIsReachedWhenTheLegacyPasswordGrantIsPostedThenItRespondsTooManyRequests(): void
    {
        // Arrange
        $username = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD])->getUsernameOrFail();
        $accountNumberOfAttempts = $this->getAccountNumberOfAttempts();

        for ($i = 0; $i < $accountNumberOfAttempts; $i++) {
            $this->assertSame(
                Response::HTTP_BAD_REQUEST,
                $this->postLegacyPasswordGrant(sprintf('203.0.113.%d', $i), $username, static::WRONG_PASSWORD)->getStatusCode(),
            );
        }

        // Act
        $response = $this->postLegacyPasswordGrant('198.51.100.7', $username, static::PASSWORD);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame('temporarily_unavailable', json_decode((string)$response->getContent(), true)[0]['code'] ?? null);
    }

    public function testGivenASuccessfulLegacyLoginWhenFailuresContinueThenTheAccountCounterStartedOver(): void
    {
        // Arrange
        $username = $this->tester->haveUser([UserTransfer::PASSWORD => static::PASSWORD])->getUsernameOrFail();
        $accountNumberOfAttempts = $this->getAccountNumberOfAttempts();

        for ($i = 0; $i < $accountNumberOfAttempts - 1; $i++) {
            $this->postJsonApiToken(sprintf('203.0.113.%d', $i), $username, static::WRONG_PASSWORD);
        }

        $this->assertSame(Response::HTTP_CREATED, $this->postJsonApiToken('192.0.2.1', $username, static::PASSWORD)->getStatusCode());

        for ($i = 0; $i < $accountNumberOfAttempts - 1; $i++) {
            $this->postJsonApiToken(sprintf('198.51.100.%d', $i), $username, static::WRONG_PASSWORD);
        }

        // Act
        $response = $this->postJsonApiToken('192.0.2.2', $username, static::PASSWORD);

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    /**
     * Read from the configuration, so the test follows whatever limit the environment sets.
     */
    protected function getAccountNumberOfAttempts(): int
    {
        return (new SecurityBlockerBackofficeConfig())
            ->getUserLoginRealmConfiguration()
            ->getAccountNumberOfAttemptsOrFail();
    }

    protected function postJsonApiToken(string $ip, string $username, string $password): Response
    {
        return $this->postTokenFromIp(
            $ip,
            (string)json_encode(['data' => ['type' => 'tokens', 'attributes' => ['username' => $username, 'password' => $password]]]),
            static::CONTENT_TYPE_JSON_API,
            static::CONTENT_TYPE_JSON_API,
        );
    }

    protected function postLegacyPasswordGrant(string $ip, string $username, string $password): Response
    {
        return $this->postTokenFromIp(
            $ip,
            http_build_query(['grantType' => 'password', 'username' => $username, 'password' => $password]),
            static::CONTENT_TYPE_FORM,
            static::DEFAULT_ACCEPT_HEADER,
        );
    }

    protected function postTokenFromIp(string $ip, string $content, string $contentType, string $accept): Response
    {
        $request = Request::create(
            rtrim(static::DEFAULT_BASE_URL, '/') . '/token',
            Request::METHOD_POST,
            [],
            [],
            [],
            ['REMOTE_ADDR' => $ip],
            $content,
        );
        $request->headers->set('Accept', $accept);
        $request->headers->set('Content-Type', $contentType);

        return $this->getTestKernel()->handle($request);
    }
}
