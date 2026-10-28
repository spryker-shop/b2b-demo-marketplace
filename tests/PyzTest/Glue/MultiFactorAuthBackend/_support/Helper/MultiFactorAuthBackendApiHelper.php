<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\MultiFactorAuthBackend\Helper;

use Codeception\Module;
use Codeception\Stub;
use Codeception\Test\TestCaseWrapper;
use Codeception\TestInterface;
use Generated\Shared\Transfer\MultiFactorAuthTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Orm\Zed\MultiFactorAuth\Persistence\SpyUserMultiFactorAuthCodesQuery;
use Orm\Zed\MultiFactorAuth\Persistence\SpyUserMultiFactorAuthQuery;
use Spryker\Glue\MultiFactorAuth\MultiFactorAuthConfig;
use Spryker\Shared\MultiFactorAuth\MultiFactorAuthConstants;
use Spryker\Zed\MultiFactorAuth\Business\MultiFactorAuthFacadeInterface;
use SprykerTest\ApiPlatform\Test\AbstractApiTestCase;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

/**
 * Arranges user MFA state through the MultiFactorAuth facade, the same write path the Back Office and the
 * Merchant Portal use, so the tests exercise the API classes only in the Act step. Sending a code goes through
 * the real mail pipeline, exactly as the activate and trigger resources do.
 *
 * The Glue `MultiFactorAuthConfig` is replaced by a stub for every test of the suite, following
 * {@see \SprykerTest\ApiPlatform\Helper\BackendApiLoginHelper}: under `bootOnce` the subscribers capture their
 * collaborators on the first boot, so the stub is bound once and reads the list of protected resources from
 * this helper, which each test sets through {@see protectBackendResources()}.
 */
class MultiFactorAuthBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    /**
     * @var array<string>
     */
    protected array $protectedBackendResources = [];

    public function _before(TestInterface $test): void
    {
        $this->protectedBackendResources = [];

        $testCase = $test instanceof TestCaseWrapper ? $test->getTestCase() : $test;

        if (!$testCase instanceof AbstractApiTestCase) {
            return;
        }

        $testCase->setService(MultiFactorAuthConfig::class, $this->createMultiFactorAuthConfigStub());
    }

    /**
     * @param array<string> $resourceShortNames
     */
    public function protectBackendResources(array $resourceShortNames): void
    {
        $this->protectedBackendResources = $resourceShortNames;
    }

    public const string URL_MULTI_FACTOR_AUTH_TYPES = '/multi-factor-auth-types';

    public const string URL_MULTI_FACTOR_AUTH_TRIGGER = '/multi-factor-auth-trigger';

    public const string URL_MULTI_FACTOR_AUTH_TYPE_ACTIVATE = '/multi-factor-auth-type-activate';

    public const string URL_MULTI_FACTOR_AUTH_TYPE_VERIFY = '/multi-factor-auth-type-verify';

    public const string URL_MULTI_FACTOR_AUTH_TYPE_DEACTIVATE = '/multi-factor-auth-type-deactivate';

    /**
     * @uses \Spryker\Zed\MultiFactorAuth\Communication\Plugin\Factors\Email\UserEmailMultiFactorAuthPlugin::NAME
     */
    public const string MULTI_FACTOR_AUTH_TYPE_EMAIL = 'email';

    public const string UNKNOWN_MULTI_FACTOR_AUTH_TYPE = 'carrier-pigeon';

    public const string INVALID_MULTI_FACTOR_AUTH_CODE = 'not-a-code';

    public function haveActivatedUserMultiFactorAuth(UserTransfer $userTransfer, string $type = self::MULTI_FACTOR_AUTH_TYPE_EMAIL): void
    {
        $this->getMultiFactorAuthFacade()->activateUserMultiFactorAuth(
            $this->createMultiFactorAuthTransfer($userTransfer, $type, MultiFactorAuthConstants::STATUS_ACTIVE),
        );
    }

    public function havePendingUserMultiFactorAuth(UserTransfer $userTransfer, string $type = self::MULTI_FACTOR_AUTH_TYPE_EMAIL): void
    {
        $this->getMultiFactorAuthFacade()->activateUserMultiFactorAuth(
            $this->createMultiFactorAuthTransfer($userTransfer, $type, MultiFactorAuthConstants::STATUS_PENDING_ACTIVATION),
        );
    }

    /**
     * Sends a code the way the resources do and returns it as the user would read it from the mail.
     */
    public function haveSentUserMultiFactorAuthCode(UserTransfer $userTransfer, string $type = self::MULTI_FACTOR_AUTH_TYPE_EMAIL): string
    {
        $this->getMultiFactorAuthFacade()->sendUserCode(
            $this->createMultiFactorAuthTransfer($userTransfer, $type, MultiFactorAuthConstants::STATUS_ACTIVE),
        );

        $code = $this->findLatestUserMultiFactorAuthCode($userTransfer, $type);

        $this->assertNotNull($code, sprintf('No MFA code was stored for user %s and type %s.', $userTransfer->getUsernameOrFail(), $type));

        return (string)$code;
    }

    public function findLatestUserMultiFactorAuthCode(UserTransfer $userTransfer, string $type = self::MULTI_FACTOR_AUTH_TYPE_EMAIL): ?string
    {
        return SpyUserMultiFactorAuthCodesQuery::create()
            ->useSpyUserMultiFactorAuthQuery()
                ->filterByFkUser($userTransfer->getIdUserOrFail())
                ->filterByType($type)
            ->endUse()
            ->orderByIdUserMultiFactorAuthCode('DESC')
            ->findOne()
            ?->getCode();
    }

    /**
     * @uses \Spryker\Shared\MultiFactorAuth\MultiFactorAuthConstants::STATUS_INACTIVE
     * @uses \Spryker\Shared\MultiFactorAuth\MultiFactorAuthConstants::STATUS_PENDING_ACTIVATION
     * @uses \Spryker\Shared\MultiFactorAuth\MultiFactorAuthConstants::STATUS_ACTIVE
     */
    public function findUserMultiFactorAuthStatus(UserTransfer $userTransfer, string $type = self::MULTI_FACTOR_AUTH_TYPE_EMAIL): ?int
    {
        return SpyUserMultiFactorAuthQuery::create()
            ->filterByFkUser($userTransfer->getIdUserOrFail())
            ->filterByType($type)
            ->findOne()
            ?->getStatus();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildMultiFactorAuthRequestBody(string $resourceType, array $attributes): string
    {
        return (string)json_encode([
            'data' => [
                'type' => $resourceType,
                'attributes' => $attributes,
            ],
        ]);
    }

    protected function createMultiFactorAuthTransfer(UserTransfer $userTransfer, string $type, int $status): MultiFactorAuthTransfer
    {
        return (new MultiFactorAuthTransfer())
            ->setUser($userTransfer)
            ->setType($type)
            ->setStatus($status);
    }

    protected function getMultiFactorAuthFacade(): MultiFactorAuthFacadeInterface
    {
        return $this->getLocator()->multiFactorAuth()->facade();
    }

    protected function createMultiFactorAuthConfigStub(): MultiFactorAuthConfig
    {
        return Stub::make(MultiFactorAuthConfig::class, [
            'getMultiFactorAuthProtectedBackendResources' => fn (): array => $this->protectedBackendResources,
        ]);
    }
}
