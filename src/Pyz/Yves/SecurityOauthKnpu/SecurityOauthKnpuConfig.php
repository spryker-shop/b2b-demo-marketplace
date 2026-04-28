<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\SecurityOauthKnpu;

use Generated\Shared\Transfer\OauthKnpuProviderConfigTransfer;
use Spryker\Yves\SecurityOauthKnpu\SecurityOauthKnpuConfig as SprykerSecurityOauthKnpuConfig;

class SecurityOauthKnpuConfig extends SprykerSecurityOauthKnpuConfig
{
    protected const string SSO_YVES_CLIENT_NAME = 'sso_yves';

    protected const string SSO_YVES_STATE_PREFIX = 'sso_yves';

    protected const string SSO_YVES_LINK_TEXT = 'security_oauth_knpu:login:sso';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Generated\Shared\Transfer\OauthKnpuProviderConfigTransfer>
     */
    public function getCustomerProviderConfigs(): array
    {
        return [
            (new OauthKnpuProviderConfigTransfer())
                ->setClientName(static::SSO_YVES_CLIENT_NAME)
                ->setStatePrefix(static::SSO_YVES_STATE_PREFIX)
                ->setLinkText(static::SSO_YVES_LINK_TEXT),
        ];
    }
}
