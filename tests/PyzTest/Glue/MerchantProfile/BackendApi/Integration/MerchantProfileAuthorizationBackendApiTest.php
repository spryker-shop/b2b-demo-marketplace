<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MerchantProfile\BackendApi\Integration;

use PyzTest\Glue\MerchantProfile\AbstractMerchantProfileBackendApiTestCase;
use PyzTest\Glue\MerchantProfile\Helper\MerchantProfileBackendApiHelper;
use Spryker\Glue\MerchantProfile\MerchantProfileConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Who may reach which merchant profile route.
 *
 * The self route (`/merchant-profile`) belongs to a merchant user and the reference route
 * (`/merchant-profiles/{merchantReference}`) to a Back Office user; neither identity may cross into
 * the other's surface. `ROLE_USER` is held by every authenticated caller, so a token carrying no
 * user type must reach nothing at all.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group MerchantProfile
 * @group BackendApi
 * @group Integration
 * @group MerchantProfileAuthorizationBackendApiTest
 * Add your own group annotations below this line
 */
class MerchantProfileAuthorizationBackendApiTest extends AbstractMerchantProfileBackendApiTestCase
{
    protected const string SCOPE_USER = 'user';

    protected const string PUBLIC_PHONE = '+49 30 000000';

    public function testGivenAMerchantUserWhenGetOwnProfileThenItIsReturned(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $merchantTransfer->getMerchantReferenceOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_MERCHANT_REFERENCE] ?? null,
        );
    }

    public function testGivenAMerchantUserWhenGetAnotherMerchantProfileByReferenceThenItIsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $otherMerchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($otherMerchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenAMerchantUserWhenPatchAnotherMerchantProfileByReferenceThenItIsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $otherMerchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($otherMerchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenABackOfficeUserWhenGetAnyMerchantProfileByReferenceThenItIsReturned(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $merchantTransfer->getMerchantReferenceOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_MERCHANT_REFERENCE] ?? null,
        );
    }

    public function testGivenABackOfficeUserWhenGetAnUnknownMerchantReferenceThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl(MerchantProfileBackendApiHelper::UNKNOWN_MERCHANT_REFERENCE),
        );

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_NOT_FOUND, MerchantProfileConfig::RESPONSE_DETAILS_MERCHANT_NOT_FOUND);
    }

    public function testGivenABackOfficeUserWhenPatchAnUnknownMerchantReferenceThenItRespondsNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl(MerchantProfileBackendApiHelper::UNKNOWN_MERCHANT_REFERENCE),
            $this->tester->buildMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_NOT_FOUND, MerchantProfileConfig::RESPONSE_DETAILS_MERCHANT_NOT_FOUND);
    }

    public function testGivenABackOfficeUserWhenGetOwnProfileThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenABackOfficeUserWhenPatchOwnProfileThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * `ROLE_USER` is granted to every authenticated caller, so a token carrying no user-type scope
     * must reach none of the routes. This is the regression test for a resource guarded on
     * `ROLE_USER` by mistake.
     */
    public function testGivenATokenWithoutAUserTypeScopeWhenGetOwnProfileThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->haveMerchantWithProfile();
        $this->tester->actingWithScopes([static::SCOPE_USER]);

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenATokenWithoutAUserTypeScopeWhenGetProfileByReferenceThenItIsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingWithScopes([static::SCOPE_USER]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    public function testGivenNoAuthenticationWhenGetOwnProfileThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->haveMerchantWithProfile();

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenNoAuthenticationWhenGetProfileByReferenceThenItRespondsUnauthorized(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenGetOwnProfileThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->haveMerchantWithProfile();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenGetProfileByReferenceThenItRespondsUnauthorized(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfile();
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * The Merchant Portal refuses the login of a merchant user whose merchant is not approved, so
     * the self route refuses it as well.
     */
    public function testGivenAMerchantUserOfAMerchantAwaitingApprovalWhenGetOwnProfileThenItIsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfileWaitingForApproval();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_FORBIDDEN, MerchantProfileConfig::RESPONSE_DETAILS_MERCHANT_NOT_APPROVED);
    }

    public function testGivenAMerchantUserOfAMerchantAwaitingApprovalWhenPatchOwnProfileThenItIsForbidden(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfileWaitingForApproval();
        $this->tester->actingAsMerchantUser($this->tester->haveUserOfMerchant($merchantTransfer));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE,
            $this->tester->buildOwnMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_FORBIDDEN, MerchantProfileConfig::RESPONSE_DETAILS_MERCHANT_NOT_APPROVED);
        $this->assertNotSame(
            static::PUBLIC_PHONE,
            $this->tester->findMerchantProfile($merchantTransfer->getMerchantReferenceOrFail())->getPublicPhone(),
        );
    }

    /**
     * The approval gate belongs to the self route only. Back Office staff manage a merchant before it
     * is approved, so the reference route must serve a merchant awaiting approval.
     */
    public function testGivenABackOfficeUserWhenGetTheProfileOfAMerchantAwaitingApprovalThenItIsReturned(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfileWaitingForApproval();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $merchantTransfer->getMerchantReferenceOrFail(),
            $this->getResourceAttributes($response)[static::ATTRIBUTE_MERCHANT_REFERENCE] ?? null,
        );
    }

    public function testGivenABackOfficeUserWhenPatchTheProfileOfAMerchantAwaitingApprovalThenItIsUpdated(): void
    {
        // Arrange
        $merchantTransfer = $this->tester->haveMerchantWithProfileWaitingForApproval();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getMerchantProfileUrl($merchantTransfer->getMerchantReferenceOrFail()),
            $this->tester->buildMerchantProfileRequestBody([static::ATTRIBUTE_PUBLIC_PHONE => static::PUBLIC_PHONE]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            static::PUBLIC_PHONE,
            $this->tester->findMerchantProfile($merchantTransfer->getMerchantReferenceOrFail())->getPublicPhone(),
        );
    }

    /**
     * The `merchant-user` scope grants `ROLE_MERCHANT_USER` on the token alone, so a user with no
     * `spy_merchant_user` row at all passes the route guard and is only found to have no merchant
     * once Zed looks for one. That must surface as the deliberate 403 the exception factory raises,
     * never as an unhandled exception.
     */
    public function testGivenATokenWithMerchantScopeButNoMerchantUserWhenGetOwnProfileThenItIsForbidden(): void
    {
        // Arrange
        $this->tester->haveMerchantWithProfile();
        $this->tester->actingAsMerchantUser();

        // Act
        $response = $this->handleApiRequest('GET', MerchantProfileBackendApiHelper::URL_OWN_MERCHANT_PROFILE);

        // Assert
        $this->assertRespondsWithErrorDetail($response, Response::HTTP_FORBIDDEN, MerchantProfileConfig::RESPONSE_DETAILS_MERCHANT_USER_NOT_RESOLVED);
    }
}
