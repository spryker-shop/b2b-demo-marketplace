<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CustomerTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /customers` over a booted GLUE_BACKEND kernel: the happy path plus every rejection the
 * resource distinguishes.
 *
 * Ported from CreateCustomerBackendJsonApiCest. The rejections matter individually because they come
 * from different layers — the framework's constraint validation answers with one code, and the
 * resource's own store/locale checks with their own.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateCustomerBackendApiTest
 * Add your own group annotations below this line
 * @group Customers
 */
class CreateCustomerBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string GLOSSARY_KEY_EMAIL_ALREADY_USED = 'customer.email.already.used';

    protected const string UNKNOWN_STORE_NAME = 'NotAStore';

    protected const string UNKNOWN_LOCALE_NAME = 'xx_XX';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    /**
     * @var array<string>
     */
    protected const array REQUIRED_ATTRIBUTES = [
        CustomerTransfer::EMAIL,
        CustomerTransfer::SALUTATION,
        CustomerTransfer::FIRST_NAME,
        CustomerTransfer::LAST_NAME,
        CustomerTransfer::STORE_NAME,
    ];

    public function testGivenValidDataWhenCreateCustomerThenTheCustomerIsCreated(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCustomerAttributes();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $this->assertAttributesMatch(
            [
                CustomerTransfer::EMAIL => $attributes[CustomerTransfer::EMAIL],
                CustomerTransfer::FIRST_NAME => $attributes[CustomerTransfer::FIRST_NAME],
                CustomerTransfer::LAST_NAME => $attributes[CustomerTransfer::LAST_NAME],
            ],
            $this->getResourceAttributes($response),
        );
        $this->assertNotEmpty(
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? null,
            'The database assigns the customer reference the response is addressed by.',
        );
    }

    public function testGivenNoAttributesWhenCreateCustomerThenEveryRequiredAttributeIsReported(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody([]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_VALIDATION,
        );
        $errorDetails = $this->getErrorDetails($response);

        $this->assertCount(
            count(static::REQUIRED_ATTRIBUTES),
            $errorDetails,
            'Every required attribute is reported, not just the first one to fail.',
        );

        foreach (static::REQUIRED_ATTRIBUTES as $requiredAttribute) {
            $this->assertNotEmpty(
                array_filter($errorDetails, static fn (string $detail): bool => str_starts_with($detail, $requiredAttribute . ' ')),
                sprintf('The response must name "%s" as missing.', $requiredAttribute),
            );
        }
    }

    public function testGivenNoStoreWhenCreateCustomerThenItIsRejectedAndNoCustomerIsCreated(): void
    {
        // Arrange: the store is the context of the registration mail, and a multi-store shop has no default.
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCustomerAttributes();
        unset($attributes[CustomerTransfer::STORE_NAME]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_VALIDATION,
        );
        $this->assertNull(
            $this->tester->findCustomerIdByEmail($attributes[CustomerTransfer::EMAIL]),
            'A rejected request must not leave a customer behind.',
        );
    }

    public function testGivenAnEmailAlreadyInUseWhenCreateCustomerThenItIsRejected(): void
    {
        // Arrange
        $existingCustomerTransfer = $this->tester->haveCustomer();
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCustomerAttributes([
            CustomerTransfer::EMAIL => $existingCustomerTransfer->getEmailOrFail(),
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_VALIDATION,
        );
        $this->assertContains(static::GLOSSARY_KEY_EMAIL_ALREADY_USED, $this->getErrorDetails($response));
    }

    public function testGivenAStoreThatIsNotConfiguredWhenCreateCustomerThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCustomerAttributes([
            CustomerTransfer::STORE_NAME => static::UNKNOWN_STORE_NAME,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNKNOWN_STORE,
        );
    }

    public function testGivenALocaleThatDoesNotExistWhenCreateCustomerThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCustomerAttributes([
            static::ATTRIBUTE_LOCALE_NAME => static::UNKNOWN_LOCALE_NAME,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_UNKNOWN_LOCALE,
        );
    }

    public function testGivenNoAuthenticationWhenCreateCustomerThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($this->tester->buildValidCustomerAttributes()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
