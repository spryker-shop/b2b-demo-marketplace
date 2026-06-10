<?php
/**
 * MIT License
 * For full license information,  view the LICENSE file that was distributed with this source code.
 */
namespace Pyz\Service\NewRelic\Plugin;
use SprykerEco\Service\NewRelic\Plugin\NewRelicMonitoringExtensionPlugin as SprykerNewRelicMonitoringExtensionPlugin;

class NewRelicMonitoringExtensionPlugin extends SprykerNewRelicMonitoringExtensionPlugin
{
    /**
     * @param string|null $application
     * @param string|null $store
     * @param string|null $environment
     *
     * @return void
     */
    public function setApplicationName(?string $application = null, ?string $store = null, ?string $environment = null): void
    {
        if (!$this->isActive) {
            return;
        }

        // Custom application environment name, or use $environment as fallback
        $environment = getenv('NEWRELIC_CUSTOM_APP_ENVIRONMENT') ?: $environment;
        $this->application = $application . '-' . $store . ' (' . $environment . ')';
        newrelic_set_appname($this->application, '', false);
    }
}
