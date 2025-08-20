<?php

namespace Pyz\Service\Synchronization\Plugin;

use Generated\Shared\Transfer\SynchronizationDataTransfer;
use Spryker\Service\Synchronization\Model\KeyFilterInterface;
use Spryker\Service\Synchronization\SynchronizationConfig;

class DefaultKeyGeneratorPlugin extends \Spryker\Service\Synchronization\Plugin\DefaultKeyGeneratorPlugin
{
    public function __construct(KeyFilterInterface $keyFilter, SynchronizationConfig $synchronizationConfig, protected \Pyz\Client\TenantBehavior\TenantBehaviorClientInterface $tenantBehaviorClient)
    {
        parent::__construct($keyFilter, $synchronizationConfig);
    }

    /**
     * @param \Generated\Shared\Transfer\SynchronizationDataTransfer $dataTransfer
     *
     * @return string
     */
    protected function getStoreAndLocaleKey(SynchronizationDataTransfer $dataTransfer)
    {
        $store = $dataTransfer->getStore();
        $locale = $dataTransfer->getLocale();
        $tenant = $this->getIdTenant($dataTransfer);

        $key = '';
        if ($store) {
            $key = sprintf('%s', strtolower($store));
        }

        if ($locale) {
            if ($key) {
                $key .= ':';
            }

            $key = sprintf('%s%s', $key, strtolower($locale));
        }

        if ($tenant) {
            if ($key) {
                $key .= ':';
            }

            $key = sprintf('%s%s', $key, strtolower($tenant));
        }

        return $key;
    }

    /**
     * @param \Generated\Shared\Transfer\SynchronizationDataTransfer $dataTransfer
     *
     * @return string|null
     */
    protected function getIdTenant(SynchronizationDataTransfer $dataTransfer): ?string
    {
        if ($dataTransfer->getIdTenant()) {
            return $dataTransfer->getIdTenant();
        }

        if (APPLICATION === 'YVES' || APPLICATION === 'GLUE') {
            return $this->tenantBehaviorClient->getCurrentTenantId();
        }

        return null;
    }
}
