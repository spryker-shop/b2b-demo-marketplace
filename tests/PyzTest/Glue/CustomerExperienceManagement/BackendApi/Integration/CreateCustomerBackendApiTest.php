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
 */
class CreateCustomerBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string GLOSSARY_KEY_EMAIL_ALREADY_USED = 'customer.email.already.used';

    protected const string UNKNOWN_STORE_NAME = 'NotAStore';

    protected const string UNKNOWN_LOCALE_NAME = 'xx_XX';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    /**
     * Every attribute the Back Office form requires is reported, not just the first one to fail.
     */
    protected const int REQUIRED_ATTRIBUTE_COUNT = 4;

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
        $this->assertCount(
            static::REQUIRED_ATTRIBUTE_COUNT,
            $this->getErrorDetails($response),
            'Every attribute the Back Office form requires is reported, not just the first.',
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

    public function testGivenAPasswordTokenIsRequestedWithoutAStoreWhenCreateCustomerThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCustomerAttributes([
            CustomerTransfer::SEND_PASSWORD_TOKEN => true,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCustomerCollectionUrl(),
            $this->tester->buildCustomerRequestBody($attributes),
        );

        // Assert — the store provides the context for the email template.
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_STORE_NAME_REQUIRED,
        );
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
