<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Generated\Shared\Transfer\CustomerGroupTransfer;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
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
 * @group CreateCustomerGroupBackendApiTest
 * Add your own group annotations below this line
 */
class CreateCustomerGroupBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    public function testCreatesCustomerGroupWithoutMembers(): void
    {
        // Arrange
        $name = $this->generateCustomerGroupName();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $name,
                static::ATTRIBUTE_DESCRIPTION => 'Wholesale partners',
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string)$response->getContent());

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($name, $attributes[static::ATTRIBUTE_NAME]);
        $this->assertSame('Wholesale partners', $attributes[static::ATTRIBUTE_DESCRIPTION]);
        $this->assertNotEmpty($attributes[static::ATTRIBUTE_UUID], 'The created group must expose a uuid.');
        $this->assertNotEmpty($attributes[static::ATTRIBUTE_CREATED_AT]);

        $this->assertSame(
            [],
            $this->tester->getAssignedCustomerReferences($attributes[static::ATTRIBUTE_UUID]),
            'A group created without customerReferences must be empty.',
        );
    }

    public function testCreatesCustomerGroupWithMembers(): void
    {
        // Arrange
        $firstCustomerTransfer = $this->tester->haveCustomer();
        $secondCustomerTransfer = $this->tester->haveCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCustomerGroupName(),
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    $firstCustomerTransfer->getCustomerReferenceOrFail(),
                    $secondCustomerTransfer->getCustomerReferenceOrFail(),
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), (string)$response->getContent());

        $assignedCustomerReferences = $this->tester->getAssignedCustomerReferences(
            $this->getResourceAttributes($response)[static::ATTRIBUTE_UUID],
        );

        $this->assertEqualsCanonicalizing(
            [
                $firstCustomerTransfer->getCustomerReferenceOrFail(),
                $secondCustomerTransfer->getCustomerReferenceOrFail(),
            ],
            $assignedCustomerReferences,
        );
    }

    public function testRejectsDuplicateNameCaseInsensitively(): void
    {
        // Arrange
        $name = $this->generateCustomerGroupName();
        $this->haveCustomerGroup([CustomerGroupTransfer::NAME => $name]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_NAME => strtoupper($name)]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            1,
            $this->tester->countCustomerGroupsWithName($name),
            'The upper-cased duplicate must not have been created.',
        );
    }

    public function testRejectsUnknownCustomerReferenceAndCreatesNothing(): void
    {
        // Arrange
        $name = $this->generateCustomerGroupName();
        $customerTransfer = $this->tester->haveCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $name,
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    $customerTransfer->getCustomerReferenceOrFail(),
                    static::UNKNOWN_CUSTOMER_REFERENCE,
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
        $this->assertStringContainsString(static::UNKNOWN_CUSTOMER_REFERENCE, (string)$response->getContent());
        $this->assertSame(
            0,
            $this->tester->countCustomerGroupsWithName($name),
            'All-or-nothing: the group must not exist when a reference is unknown.',
        );
    }

    public function testReportsEveryUnknownCustomerReferenceAsItsOwnError(): void
    {
        // Arrange
        $secondUnknownCustomerReference = static::UNKNOWN_CUSTOMER_REFERENCE . '-second';

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCustomerGroupName(),
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    static::UNKNOWN_CUSTOMER_REFERENCE,
                    $secondUnknownCustomerReference,
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);

        $errorDetails = $this->getErrorDetails($response);
        $this->assertCount(
            2,
            $errorDetails,
            'Each unknown reference gets its own errors[] entry, never one entry with the messages joined.',
        );
        $this->assertSame(
            [
                sprintf(
                    CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_CUSTOMER_NOT_FOUND,
                    static::UNKNOWN_CUSTOMER_REFERENCE,
                ),
                sprintf(
                    CustomerExperienceManagementConfig::RESPONSE_DETAILS_CUSTOMER_GROUP_CUSTOMER_NOT_FOUND,
                    $secondUnknownCustomerReference,
                ),
            ],
            $errorDetails,
        );
        $this->assertSame(
            [
                CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_CUSTOMER_NOT_FOUND,
                CustomerExperienceManagementConfig::RESPONSE_CODE_CUSTOMER_GROUP_CUSTOMER_NOT_FOUND,
            ],
            $this->getErrorCodes($response),
        );
    }

    public function testRejectsBlankName(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_NAME => '']),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testRejectsNameLongerThanSeventyCharacters(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCollectionUrl(),
            $this->tester->buildCustomerGroupRequestBody([static::ATTRIBUTE_NAME => str_repeat('a', 71)]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }
}
