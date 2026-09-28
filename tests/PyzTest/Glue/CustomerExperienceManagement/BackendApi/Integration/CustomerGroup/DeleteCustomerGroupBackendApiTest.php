<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CustomerGroup
 * @group DeleteCustomerGroupBackendApiTest
 * Add your own group annotations below this line
 */
class DeleteCustomerGroupBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    public function testDeletesCustomerGroupAndKeepsTheCustomers(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());

        $this->assertSame(
            0,
            $this->tester->countCustomerGroupsWithName($customerGroupTransfer->getNameOrFail()),
            'The group row must be gone.',
        );

        $customerResponse = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerUrl($customerTransfer->getCustomerReferenceOrFail()),
        );
        $this->assertSame(
            Response::HTTP_OK,
            $customerResponse->getStatusCode(),
            'Deleting a group must not delete its members.',
        );
    }

    public function testReturnsNotFoundForUnknownUuid(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerGroupUrl(static::UNKNOWN_WELL_FORMED_UUID),
        );

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode(), (string)$response->getContent());
    }
}
