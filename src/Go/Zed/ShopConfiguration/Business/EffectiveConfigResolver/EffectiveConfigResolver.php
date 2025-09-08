<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver;

use Generated\Shared\Transfer\ShopConfigurationCollectionTransfer;
use Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface;
use Go\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface;
use Go\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface;
use Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface;
use Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface;

class EffectiveConfigResolver implements EffectiveConfigResolverInterface
{
    /**
     * @var \Go\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface
     */
    protected ConfigFileReaderInterface $configFileReader;

    /**
     * @var \Go\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface
     */
    protected ConfigNormalizerInterface $configNormalizer;

    /**
     * @var \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface
     */
    protected ShopConfigurationRepositoryInterface $repository;

    /**
     * @var \Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface
     */
    protected EncryptionServiceInterface $encryptionService;

    /**
     * @param \Go\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface $configFileReader
     * @param \Go\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface $configNormalizer
     * @param \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface $repository
     * @param \Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface $encryptionService
     */
    public function __construct(
        ConfigFileReaderInterface $configFileReader,
        ConfigNormalizerInterface $configNormalizer,
        ShopConfigurationRepositoryInterface $repository,
        EncryptionServiceInterface $encryptionService
    ) {
        $this->configFileReader = $configFileReader;
        $this->configNormalizer = $configNormalizer;
        $this->repository = $repository;
        $this->encryptionService = $encryptionService;
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function resolveEffectiveConfiguration(string $store, ?string $locale = null): ShopConfigurationCollectionTransfer
    {
        // Get default configuration from files
        $defaultCollection = $this->resolveDefaultConfiguration();

        // Get stored overrides from database
        $storedOverrides = $this->getStoredOverrides($store, $locale);

        // Apply overrides to default configuration
        $effectiveCollection = $this->applyOverrides($defaultCollection, $storedOverrides);

        return $effectiveCollection
            ->setStore($store)
            ->setLocale($locale);
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<string, mixed>
     */
    public function resolveEffectiveConfigurationMap(string $store, ?string $locale = null): array
    {
        $collection = $this->resolveEffectiveConfiguration($store, $locale);
        $configMap = [];

        foreach ($collection->getSections() as $section) {
            foreach ($section->getOptions() as $option) {
                $key = $option->getModule() . '.' . $option->getKey();
                $value = $option->getCurrentValue() ?: $option->getDefault();

                // Decrypt sensitive values
                if ($this->encryptionService->isSensitiveField($option->getKey())) {
                    $value = $this->encryptionService->decrypt($value, $option->getKey());
                }

                $configMap[$key] = $this->parseValue($value, $option->getDataType());
            }
        }

        return $configMap;
    }

    /**
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    public function resolveDefaultConfiguration(): ShopConfigurationCollectionTransfer
    {
        $fileDataTransfers = $this->configFileReader->readAllConfigurationFiles();
        $sections = $this->configNormalizer->normalizeConfigurationData($fileDataTransfers);

        $collection = new ShopConfigurationCollectionTransfer();
        foreach ($sections as $section) {
            $collection->addSection($section);
        }

        return $collection;
    }

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return array<string, string> Key-value map where key is config key
     */
    protected function getStoredOverrides(string $store, ?string $locale = null): array
    {
        try {
            return $this->repository->getEffectiveConfigurationMap($store, $locale);
        } catch (\Exception $e) {
            // If database is not available, return empty overrides
            return [];
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer $defaultCollection
     * @param array<string, string> $storedOverrides
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationCollectionTransfer
     */
    protected function applyOverrides(
        ShopConfigurationCollectionTransfer $defaultCollection,
        array $storedOverrides
    ): ShopConfigurationCollectionTransfer {
        $effectiveCollection = clone $defaultCollection;

        foreach ($effectiveCollection->getSections() as $section) {
            foreach ($section->getOptions() as $option) {
                $optionKey = $option->getModule() . '.' . $option->getKey();

                if (isset($storedOverrides[$optionKey])) {
                    $storedValue = $storedOverrides[$optionKey];

                    // Only apply override if option is overridable
                    if ($option->getOverridable()) {
                        $option->setCurrentValue($storedValue);
                        $option->setIsOverridden(true);
                    }
                } else {
                    $option->setCurrentValue($option->getDefault());
                    $option->setIsOverridden(false);
                }
            }
        }

        return $effectiveCollection;
    }

    /**
     * @param string $value
     * @param string $dataType
     *
     * @return mixed
     */
    protected function parseValue(string $value, string $dataType)
    {
        switch ($dataType) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
            case 'array':
            case 'json':
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            default:
                return $value;
        }
    }
}
