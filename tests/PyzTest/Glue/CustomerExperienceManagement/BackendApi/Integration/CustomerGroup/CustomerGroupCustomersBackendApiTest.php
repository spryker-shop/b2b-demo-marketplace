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
 * @group CustomerGroupCustomersBackendApiTest
 * Add your own group annotations below this line
 */
class CustomerGroupCustomersBackendApiTest extends AbstractCustomerGroupBackendApiTestCase
{
    public function testListsTheMembersOfTheGroup(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$customerTransfer->getCustomerReferenceOrFail()],
            $this->getResourceIds($response),
        );

        $attributes = $this->getFirstResourceAttributes($response);
        $this->assertSame($customerTransfer->getEmailOrFail(), $attributes[static::ATTRIBUTE_EMAIL]);
        $this->assertArrayNotHasKey(
            static::ATTRIBUTE_UUID,
            $attributes,
            'The group uuid binds the URI variable and must never be serialized onto a member.',
        );
    }

    public function testListsOnlyTheMembersOfTheGroupInTheUrl(): void
    {
        // Arrange
        $memberTransfer = $this->tester->haveCustomer();
        $outsiderTransfer = $this->tester->haveCustomer();

        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$memberTransfer]);
        $this->haveCustomerGroupWithCustomers([$outsiderTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
        );

        // Assert
        $this->assertSame([$memberTransfer->getCustomerReferenceOrFail()], $this->getResourceIds($response));
    }

    public function testReturnsNotFoundWhenListingMembersOfAnUnknownGroup(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl(static::UNKNOWN_WELL_FORMED_UUID),
        );

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testAddsCustomersToTheGroup(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();
        $firstCustomerTransfer = $this->tester->haveCustomer();
        $secondCustomerTransfer = $this->tester->haveCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    $firstCustomerTransfer->getCustomerReferenceOrFail(),
                    $secondCustomerTransfer->getCustomerReferenceOrFail(),
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertEqualsCanonicalizing(
            [
                $firstCustomerTransfer->getCustomerReferenceOrFail(),
                $secondCustomerTransfer->getCustomerReferenceOrFail(),
            ],
            $this->tester->getAssignedCustomerReferences(
                $customerGroupTransfer->getUuidOrFail(),
            ),
        );
    }

    public function testAddingACustomerKeepsTheOnesAlreadyAssigned(): void
    {
        // Arrange
        $assignedCustomerTransfer = $this->tester->haveCustomer();
        $addedCustomerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$assignedCustomerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [$addedCustomerTransfer->getCustomerReferenceOrFail()],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertEqualsCanonicalizing(
            [
                $assignedCustomerTransfer->getCustomerReferenceOrFail(),
                $addedCustomerTransfer->getCustomerReferenceOrFail(),
            ],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
            'POST adds to the group; it must not replace the customers already assigned.',
        );
    }

    /**
     * @dataProvider provideMemberSortFields
     *
     * @param array<int, int> $expectedOrder
     */
    public function testSortsTheMembersOfTheGroup(string $sort, array $expectedOrder): void
    {
        // Arrange
        $firstCustomerTransfer = $this->tester->haveCustomer([
            static::ATTRIBUTE_EMAIL => sprintf('a-%s@example.com', uniqid('', false)),
            static::ATTRIBUTE_FIRST_NAME => 'Anna',
            static::ATTRIBUTE_LAST_NAME => 'Almond',
        ]);
        $secondCustomerTransfer = $this->tester->haveCustomer([
            static::ATTRIBUTE_EMAIL => sprintf('b-%s@example.com', uniqid('', false)),
            static::ATTRIBUTE_FIRST_NAME => 'Bob',
            static::ATTRIBUTE_LAST_NAME => 'Brooks',
        ]);
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers(
            [$firstCustomerTransfer, $secondCustomerTransfer],
        );

        $customerReferences = [
            $firstCustomerTransfer->getCustomerReferenceOrFail(),
            $secondCustomerTransfer->getCustomerReferenceOrFail(),
        ];

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl(
                $customerGroupTransfer->getUuidOrFail(),
                ['sort' => $sort],
            ),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$customerReferences[$expectedOrder[0]], $customerReferences[$expectedOrder[1]]],
            $this->getResourceIds($response),
        );
    }

    /**
     * @return array<string, array{string, array<int, int>}>
     */
    public function provideMemberSortFields(): array
    {
        return [
            'email ascending' => ['email', [0, 1]],
            'email descending' => ['-email', [1, 0]],
            'firstName ascending' => ['firstName', [0, 1]],
            'firstName descending' => ['-firstName', [1, 0]],
            'lastName ascending' => ['lastName', [0, 1]],
            'lastName descending' => ['-lastName', [1, 0]],
        ];
    }

    public function testAppliesTheSecondMemberSortFieldWhenTheFirstTies(): void
    {
        // Arrange
        $sharedLastName = 'Sharedname';
        $firstCustomerTransfer = $this->tester->haveCustomer([
            static::ATTRIBUTE_FIRST_NAME => 'Anna',
            static::ATTRIBUTE_LAST_NAME => $sharedLastName,
        ]);
        $secondCustomerTransfer = $this->tester->haveCustomer([
            static::ATTRIBUTE_FIRST_NAME => 'Bob',
            static::ATTRIBUTE_LAST_NAME => $sharedLastName,
        ]);
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers(
            [$firstCustomerTransfer, $secondCustomerTransfer],
        );

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl(
                $customerGroupTransfer->getUuidOrFail(),
                ['sort' => 'lastName,-firstName'],
            ),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [
                $secondCustomerTransfer->getCustomerReferenceOrFail(),
                $firstCustomerTransfer->getCustomerReferenceOrFail(),
            ],
            $this->getResourceIds($response),
        );
    }

    public function testNarrowsTheMembersByFreeTextSearch(): void
    {
        // Arrange
        $searchTerm = uniqid('member', false);
        $matchingCustomerTransfer = $this->tester->haveCustomer([static::ATTRIBUTE_LAST_NAME => $searchTerm]);
        $otherCustomerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers(
            [$matchingCustomerTransfer, $otherCustomerTransfer],
        );

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl(
                $customerGroupTransfer->getUuidOrFail(),
                ['q' => $searchTerm],
            ),
        );

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$matchingCustomerTransfer->getCustomerReferenceOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testReturnsBadRequestForUnsupportedMemberSortField(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCustomerGroupCustomerCollectionUrl(
                $customerGroupTransfer->getUuidOrFail(),
                ['sort' => 'customerReference'],
            ),
        );

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testAddingTheSameCustomerTwiceIsIdempotent(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [$customerTransfer->getCustomerReferenceOrFail()],
            ]),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            'An already-assigned reference is skipped, not rejected.',
        );
        $this->assertSame(
            [$customerTransfer->getCustomerReferenceOrFail()],
            $this->tester->getAssignedCustomerReferences(
                $customerGroupTransfer->getUuidOrFail(),
            ),
            'The assignment must not be duplicated.',
        );
    }

    public function testRejectsUnknownCustomerReferenceAndAddsNothing(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();
        $customerTransfer = $this->tester->haveCustomer();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    $customerTransfer->getCustomerReferenceOrFail(),
                    static::UNKNOWN_CUSTOMER_REFERENCE,
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [],
            $this->tester->getAssignedCustomerReferences(
                $customerGroupTransfer->getUuidOrFail(),
            ),
            'All-or-nothing: the known reference must not be assigned either.',
        );
    }

    public function testRejectsEmptyCustomerReferencesOnAdd(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([static::ATTRIBUTE_CUSTOMER_REFERENCES => []]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode(), (string)$response->getContent());
    }

    public function testReplacesTheWholeAssignmentWithTheSentList(): void
    {
        // Arrange
        $keptCustomerTransfer = $this->tester->haveCustomer();
        $removedCustomerTransfer = $this->tester->haveCustomer();
        $addedCustomerTransfer = $this->tester->haveCustomer();

        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers(
            [$keptCustomerTransfer, $removedCustomerTransfer],
        );

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [
                    $keptCustomerTransfer->getCustomerReferenceOrFail(),
                    $addedCustomerTransfer->getCustomerReferenceOrFail(),
                ],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertEqualsCanonicalizing(
            [
                $keptCustomerTransfer->getCustomerReferenceOrFail(),
                $addedCustomerTransfer->getCustomerReferenceOrFail(),
            ],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
            'The sent list becomes the whole member set: the kept member survives, the missing one is removed.',
        );
    }

    public function testReplacingWithAnEmptyListRemovesEveryMemberAndKeepsTheGroup(): void
    {
        // Arrange
        $firstCustomerTransfer = $this->tester->haveCustomer();
        $secondCustomerTransfer = $this->tester->haveCustomer();

        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers(
            [$firstCustomerTransfer, $secondCustomerTransfer],
        );

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
        );
        $this->assertSame(
            1,
            $this->tester->countCustomerGroupsWithName($customerGroupTransfer->getNameOrFail()),
            'Clearing the assignment must not delete the group.',
        );
    }

    public function testReplacingAnEmptyGroupWithAnEmptyListIsIdempotent(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([
                static::ATTRIBUTE_CUSTOMER_REFERENCES => [],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
            'Clearing a group that is already empty changes nothing.',
        );
    }

    public function testRejectsAReplaceWithoutCustomerReferences(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$customerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCustomerGroupCustomerCollectionUrl($customerGroupTransfer->getUuidOrFail()),
            $this->tester->buildCustomerGroupCustomersRequestBody([]),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode(),
            (string)$response->getContent(),
        );
        $this->assertSame(
            [$customerTransfer->getCustomerReferenceOrFail()],
            $this->tester->getAssignedCustomerReferences($customerGroupTransfer->getUuidOrFail()),
            'An omitted list must be rejected, never read as "remove everyone".',
        );
    }

    public function testRemovesOneMember(): void
    {
        // Arrange
        $removedCustomerTransfer = $this->tester->haveCustomer();
        $keptCustomerTransfer = $this->tester->haveCustomer();

        $customerGroupTransfer = $this->haveCustomerGroupWithCustomers([$removedCustomerTransfer, $keptCustomerTransfer]);

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerGroupCustomerUrl(
                $customerGroupTransfer->getUuidOrFail(),
                $removedCustomerTransfer->getCustomerReferenceOrFail(),
            ),
        );

        // Assert
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string)$response->getContent());
        $this->assertSame(
            [$keptCustomerTransfer->getCustomerReferenceOrFail()],
            $this->tester->getAssignedCustomerReferences(
                $customerGroupTransfer->getUuidOrFail(),
            ),
        );
    }

    public function testReturnsNotFoundWhenRemovingANonMember(): void
    {
        // Arrange
        $customerGroupTransfer = $this->haveCustomerGroup();
        $customerTransfer = $this->tester->haveCustomer();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCustomerGroupCustomerUrl(
                $customerGroupTransfer->getUuidOrFail(),
                $customerTransfer->getCustomerReferenceOrFail(),
            ),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_NOT_FOUND,
            $response->getStatusCode(),
            'Removing a customer that is not a member must not answer 204.',
        );
    }
}
