<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\McpCommerce;

use Codeception\Actor;
use Demo\Zed\McpCommerce\Business\McpCommerceFacade;
use Demo\Zed\McpCommerce\Business\McpCommerceFacadeInterface;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(\PyzTest\Zed\McpCommerce\PHPMD)
 */
class McpCommerceBusinessTester extends Actor
{
    use _generated\McpCommerceBusinessTesterActions;

    /**
     * @var string
     */
    public const CODE_CHALLENGE_METHOD_S256 = 'S256';

    /**
     * @var string
     */
    public const CODE_CHALLENGE_METHOD_PLAIN = 'plain';

    /**
     * @var string
     */
    public const REDIRECT_URI = 'http://localhost:8080/callback';

    /**
     * @var string
     */
    public const CUSTOMER_REFERENCE = 'DE--2';

    /**
     * @var int
     */
    public const ID_CUSTOMER = 2;

    /**
     * @return \Demo\Zed\McpCommerce\Business\McpCommerceFacadeInterface
     */
    public function getFacade(): McpCommerceFacadeInterface
    {
        // Instantiated directly rather than through the locator: the generated IDE autocompletion stub
        // has no `mcpCommerce()` entry for a project-namespace module, so a locator call cannot be
        // statically verified. The facade resolves its own business factory, so nothing is lost.
        return new McpCommerceFacade();
    }

    /**
     * Returns a fresh, cryptographically random PKCE code verifier in the RFC 7636 unreserved
     * character set.
     *
     * @return string
     */
    public function createCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Returns the S256 code challenge for the given verifier: base64url(sha256(verifier)).
     *
     * @param string $codeVerifier
     *
     * @return string
     */
    public function createCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    /**
     * Issues a real authorization code bound to the given challenge, so a test can exercise the
     * redemption path exactly as the `/token` endpoint does.
     *
     * @param string $clientIdentifier
     * @param string $codeChallenge
     * @param string $codeChallengeMethod
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function haveAuthorizationCode(
        string $clientIdentifier,
        string $codeChallenge,
        string $codeChallengeMethod = self::CODE_CHALLENGE_METHOD_S256,
    ): McpAuthorizationCodeTransfer {
        return $this->getFacade()->issueAuthorizationCode(
            (new McpAuthorizationCodeTransfer())
                ->setClientIdentifier($clientIdentifier)
                ->setCustomerReference(static::CUSTOMER_REFERENCE)
                ->setIdCustomer(static::ID_CUSTOMER)
                ->setCodeChallenge($codeChallenge)
                ->setCodeChallengeMethod($codeChallengeMethod)
                ->setRedirectUri(static::REDIRECT_URI),
        );
    }

    /**
     * Registers a client through the real registrar so the redemption client check has a genuine
     * counterpart, and returns its generated identifier.
     *
     * @return string
     */
    public function haveClientIdentifier(): string
    {
        return uniqid('mcp-test-client-', true);
    }

    /**
     * @return \Generated\Shared\Transfer\McpIdentityTransfer
     */
    public function createIdentityTransfer(string $clientIdentifier): McpIdentityTransfer
    {
        return (new McpIdentityTransfer())
            ->setCustomerReference(static::CUSTOMER_REFERENCE)
            ->setIdCustomer(static::ID_CUSTOMER)
            ->setClientIdentifier($clientIdentifier);
    }
}
