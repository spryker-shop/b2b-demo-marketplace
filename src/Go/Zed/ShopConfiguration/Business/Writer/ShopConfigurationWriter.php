<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\Writer;

use Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer;
use Generated\Shared\Transfer\ShopConfigurationValueTransfer;
use Go\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriterInterface;
use Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface;
use Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface;
use Go\Zed\ShopConfiguration\ShopConfigurationConfig;

class ShopConfigurationWriter implements ShopConfigurationWriterInterface
{
    /**
     * @var \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface
     */
    protected ShopConfigurationEntityManagerInterface $entityManager;

    /**
     * @var \Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface
     */
    protected EncryptionServiceInterface $encryptionService;

    /**
     * @var \Go\Zed\ShopConfiguration\ShopConfigurationConfig
     */
    protected ShopConfigurationConfig $config;

    /**
     * @param \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface $entityManager
     * @param \Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface $encryptionService
     * @param \Go\Zed\ShopConfiguration\ShopConfigurationConfig $config
     */
    public function __construct(
        ShopConfigurationEntityManagerInterface $entityManager,
        EncryptionServiceInterface $encryptionService,
        ShopConfigurationConfig $config
    ) {
        $this->entityManager = $entityManager;
        $this->encryptionService = $encryptionService;
        $this->config = $config;
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer $saveRequestTransfer
     *
     * @return void
     */
    public function saveConfiguration(ShopConfigurationSaveRequestTransfer $saveRequestTransfer): void
    {
        $this->saveConfigurationValues(
            $saveRequestTransfer->getStore(),
            $saveRequestTransfer->getLocale(),
            $saveRequestTransfer->getValues()
        );
    }

    /**
     * @param string $store
     * @param string|null $locale
     * @param array<string, mixed> $values
     *
     * @return void
     */
    public function saveConfigurationValues(string $store, ?string $locale, array $values): void
    {
        $configurationValueTransfers = [];

        foreach ($values as $configKey => $value) {
            $configurationValueTransfer = $this->createConfigurationValueTransfer(
                $store,
                $locale,
                $configKey,
                $value
            );

            $configurationValueTransfers[] = $configurationValueTransfer;
        }

        try {
            $this->entityManager->saveConfigurationValues($configurationValueTransfers);
        } catch (\Exception $e) {
            // Log error - database might not be available
            error_log(sprintf('Failed to save configuration values: %s', $e->getMessage()));
        }
    }

    /**
     * @param string $store
     * @param string|null $locale
     * @param string $configKey
     * @param mixed $value
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationValueTransfer
     */
    protected function createConfigurationValueTransfer(
        string $store,
        ?string $locale,
        string $configKey,
        $value
    ): ShopConfigurationValueTransfer {
        // Extract module and section from config key if possible
        $keyParts = explode('.', $configKey);
        $module = count($keyParts) > 1 ? $keyParts[0] : 'Unknown';
        $section = 'general'; // Default section

        // Encrypt sensitive values
        $valueJson = json_encode($value);
        if ($this->encryptionService->isSensitiveField($configKey)) {
            $valueJson = $this->encryptionService->encrypt($valueJson, $configKey);
        }

        return (new ShopConfigurationValueTransfer())
            ->setScopeStore($store === 'ALL' ? null : $store)
            ->setScopeLocale($locale)
            ->setModule($module)
            ->setSection($section)
            ->setConfigKey($configKey)
            ->setValueJson($valueJson)
            ->setDataType($this->determineDataType($value))
            ->setIsOverridden(true);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function determineDataType($value): string
    {
        if (is_bool($value)) {
            return 'bool';
        }

        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_array($value)) {
            return 'array';
        }

        return 'string';
    }
}
