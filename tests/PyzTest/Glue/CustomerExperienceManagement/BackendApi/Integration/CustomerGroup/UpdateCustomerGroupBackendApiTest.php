<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Generated\Shared\Transfer\CustomerGroupTransfer;
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
 * @group UpdateCustomerGroupBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateCustomerGroupBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    public function testRenamesCustomerGroupWithoutChangingUuid(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();
        $newName = $this->generateCustomerGroupName();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_NAME => $newName]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($newName, $attributes[static::ATTRIBUTE_NAME]);
        $this->assertSame(
            $customerGroupTransfer->getUuidOrFail(),
            $attributes[static::ATTRIBUTE_UUID],
            'Renaming must not change the identifier.',
        );
    }

    public function testKeepsDescriptionWhenOnlyNameIsSent(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup([CustomerGroupTransfer::DESCRIPTION => 'Keep me']);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCustomerGroupName(),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            'Keep me',
            $this->getResourceAttributes($response)[static::ATTRIBUTE_DESCRIPTION],
            'An omitted property must stay as it is.',
        );
    }

    public function testIgnoresCustomerReferencesAndKeepsTheAssignment(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $otherCustomerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCustomerGroupName(),
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    $otherCustomerTransfer->getCustomerReferenceOrFail(),
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$customerTransfer->getCustomerReferenceOrFail()],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
            'Membership is managed through the customers sub-resource: a rename must not replace it.',
        );
    }

    public function testEmptyCustomerReferencesDoesNotClearTheGroup(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_CUSTOMER_REFERENCES => []]),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$customerTransfer->getCustomerReferenceOrFail()],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
            'Clearing a group is PATCH on the customers sub-resource, never a side effect of updating the group.',
        );
    }

    public function testOmittedCustomerReferencesLeavesAssignmentUntouched(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_DESCRIPTION => 'Only the description']),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$customerTransfer->getCustomerReferenceOrFail()],
            $this->tester->getAssignedCustomerReferences(
                $customerGroupTransfer->getUuidOrFail(),
            ),
            'Omitted is not the same as empty: the assignment must survive.',
        );
    }

    public function testRejectsRenameToANameAnotherGroupAlreadyUses(): void
    {
        // Arrange
        $existingCustomerGroup = $this->haveCustomerGroup();
        $customerGroupTransfer = $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => strtoupper($existingCustomerGroup[static::ATTRIBUTE_NAME]),
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testAllowsSendingTheGroupsOwnNameUnchanged(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $customerGroupTransfer->getNameOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            'The uniqueness check must exclude the group being updated.',
        );
    }

    public function testReturnsNotFoundForUnknownUuid(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupUrl(static::UNKNOWN_WELL_FORMED_UUID),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_NAME => $this->generateCustomerGroupName()]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode(), (string)$response->getContent());
    }
}
