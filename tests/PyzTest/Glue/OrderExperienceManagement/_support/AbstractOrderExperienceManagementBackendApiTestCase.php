<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractOrderExperienceManagementBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_ORDER_REFERENCE = 'DE--oem-backend-api-does-not-exist';

    protected OrderExperienceManagementBackendApiIntegrationTester $tester;

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createOrderViaApi(array $attributes): Response
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getOrderCollectionUrl(),
            $this->tester->buildOrderRequestBody($attributes),
        );

        $orderReference = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');

        if ($orderReference !== '') {
            $this->tester->cleanupPlacedOrder($orderReference);
        }

        return $response;
    }
}
