<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SecurityOauthKnpu;

use Generated\Shared\Transfer\OauthKnpuProviderConfigTransfer;
use Spryker\Zed\SecurityOauthKnpu\SecurityOauthKnpuConfig as SprykerSecurityOauthKnpuConfig;

class SecurityOauthKnpuConfig extends SprykerSecurityOauthKnpuConfig
{
    protected const string SSO_MERCHANT_PORTAL_CLIENT_NAME = 'sso_merchant_portal';

    protected const string SSO_MERCHANT_PORTAL_STATE_PREFIX = 'oauth_mp';

    protected const string SSO_MERCHANT_PORTAL_LINK_TEXT = 'Login with SSO';

    protected const string DEFAULT_STATE_PREFIX = 'knpu_oauth';

    protected const string DEFAULT_CLIENT_NAME = 'sso_zed';

    protected const string DEFAULT_LINK_TEXT = 'Login with SSO';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Generated\Shared\Transfer\OauthKnpuProviderConfigTransfer>
     */
    public function getMerchantUserProviderConfigs(): array
    {
        return [
            (new OauthKnpuProviderConfigTransfer())
                ->setClientName(static::SSO_MERCHANT_PORTAL_CLIENT_NAME)
                ->setStatePrefix(static::SSO_MERCHANT_PORTAL_STATE_PREFIX)
                ->setLinkText(static::SSO_MERCHANT_PORTAL_LINK_TEXT),
        ];
    }

    /**
     * @api
     *
     * @return array<\Generated\Shared\Transfer\OauthKnpuProviderConfigTransfer>
     */
    public function getZedUserProviderConfigs(): array
    {
        return [
            (new OauthKnpuProviderConfigTransfer())
                ->setClientName(static::DEFAULT_CLIENT_NAME)
                ->setStatePrefix(static::DEFAULT_STATE_PREFIX)
                ->setLinkText(static::DEFAULT_LINK_TEXT),
        ];
    }
}
