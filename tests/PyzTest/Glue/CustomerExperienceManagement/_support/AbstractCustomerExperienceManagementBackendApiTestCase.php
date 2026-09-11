<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement;

use SprykerTest\ApiPlatform\Test\BackendApiTestCase;
use SprykerTest\ApiPlatform\Test\JsonApiResponseAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * CXM-specific arrangement for the backend integration suite. Everything about the JSON:API envelope
 * itself lives in {@see JsonApiResponseAssertionsTrait}, which is shared with every other API
 * Platform suite; what remains here is the customer domain and this suite's actor.
 *
 * A base class rather than a trait: every subclass needs the same `$tester` type declaration, and a
 * trait cannot carry the property type Codeception's actor injection relies on.
 */
abstract class AbstractCustomerExperienceManagementBackendApiTestCase extends BackendApiTestCase
{
    use JsonApiResponseAssertionsTrait;

    protected const string UNKNOWN_CUSTOMER_REFERENCE = 'DE--cxm-backend-api-does-not-exist';

    protected const string UNKNOWN_UUID = '11111111-2222-3333-4444-555555555555';

    protected CustomerExperienceManagementBackendApiIntegrationTester $tester;

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveAddressViaApi(string $customerReference, array $attributes = []): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerAddressCollectionUrl($customerReference),
            $this->tester->buildCustomerAddressRequestBody(
                $this->tester->buildValidCustomerAddressAttributes($attributes),
            ),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the address under test: %s', (string)$response->getContent()),
        );

        return (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function haveNoteViaApi(string $customerReference, array $attributes = []): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerNoteCollectionUrl($customerReference),
            $this->tester->buildCustomerNoteRequestBody(
                $this->tester->buildValidCustomerNoteAttributes($attributes),
            ),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the note under test: %s', (string)$response->getContent()),
        );

        return (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
    }
}
