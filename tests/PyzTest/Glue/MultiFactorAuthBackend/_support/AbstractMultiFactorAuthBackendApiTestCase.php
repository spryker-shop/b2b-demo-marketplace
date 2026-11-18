<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MultiFactorAuthBackend;

use Spryker\Glue\MultiFactorAuth\MultiFactorAuthConfig;
use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * MFA arrangement for the backend integration suite. The JSON:API envelope is asserted by
 * {@see JsonApiResponseAssertionsTrait}; what remains here is the MFA vocabulary and the write-request shape
 * shared by the four action resources.
 */
abstract class AbstractMultiFactorAuthBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string ATTRIBUTE_TYPE = 'type';

    protected const string ATTRIBUTE_STATUS = 'status';

    /**
     * @uses \Spryker\Glue\MultiFactorAuth\MultiFactorAuthConfig::getMultiFactorAuthTypeStatuses()
     */
    protected const string STATUS_LABEL_ACTIVATED = 'activated';

    protected const string STATUS_LABEL_PENDING_ACTIVATION = 'activation is pending';

    protected const string STATUS_LABEL_DEACTIVATED = 'deactivated';

    protected MultiFactorAuthBackendApiIntegrationTester $tester;

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, string> $headers
     */
    protected function postMultiFactorAuth(string $url, string $resourceType, array $attributes, array $headers = []): Response
    {
        return $this->handleApiRequest(
            'POST',
            $url,
            $this->tester->buildMultiFactorAuthRequestBody($resourceType, $attributes),
            $headers,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function createMultiFactorAuthCodeHeader(string $code): array
    {
        return [MultiFactorAuthConfig::HEADER_MULTI_FACTOR_AUTH_CODE => $code];
    }

    /**
     * @return array<string, string> Status label indexed by type
     */
    protected function getStatusByType(Response $response): array
    {
        $statusByType = [];

        foreach ($this->getJsonApiMembers($response, static::JSON_API_KEY_DATA) as $member) {
            $attributes = (array)($member[static::JSON_API_KEY_ATTRIBUTES] ?? []);
            $statusByType[$attributes[static::ATTRIBUTE_TYPE]] = $attributes[static::ATTRIBUTE_STATUS];
        }

        return $statusByType;
    }
}
