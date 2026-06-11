<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\WaterTreatmentConfiguratorPageExample\Plugin\Application;

use Spryker\Yves\ApplicationExtension\Dependency\Plugin\SecurityHeaderExpanderPluginInterface;

class WaterTreatmentConfiguratorSecurityHeaderExpanderPlugin implements SecurityHeaderExpanderPluginInterface
{
    /**
     * @see {@link \Spryker\Yves\Application\ApplicationConfig::getSecurityHeaders()}
     *
     * @var string
     */
    protected const HEADER_CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    /**
     * @var string
     */
    protected const ATTRIBUTE_FORM_ACTION = 'form-action';

    /**
     * {@inheritDoc}
     * - Adds the Water Treatment configurator url to the `Content-Security-Policy` header.
     * - Enables redirect to the configurator page with `form-action` protection.
     *
     * @api
     *
     * @param array<string, string> $securityHeaders
     *
     * @return array<string, string>
     */
    public function expand(array $securityHeaders): array
    {
        $contentSecurityPolicyHeader = $securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY] ?? null;

        if (!$contentSecurityPolicyHeader) {
            return $securityHeaders;
        }

        $securityHeaders[static::HEADER_CONTENT_SECURITY_POLICY] = str_replace(
            static::ATTRIBUTE_FORM_ACTION,
            sprintf('%s %s', static::ATTRIBUTE_FORM_ACTION, $this->createConfiguratorUrl()),
            $contentSecurityPolicyHeader,
        );

        return $securityHeaders;
    }

    /**
     * @return string
     */
    protected function createConfiguratorUrl(): string
    {
        return sprintf(
            '%s://%s',
            getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_PORT') === '443' ? 'https' : 'http',
            getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_HOST') ?: '',
        );
    }
}
