<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business;

use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReader;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser;
use Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser;
use Pyz\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizer;
use Pyz\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface;
use Pyz\Zed\ShopConfiguration\Business\ConfigValidator\ConfigValidator;
use Pyz\Zed\ShopConfiguration\Business\ConfigValidator\ConfigValidatorInterface;
use Pyz\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolver;
use Pyz\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface;
use Pyz\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisher;
use Pyz\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisherInterface;
use Pyz\Zed\ShopConfiguration\Business\Security\EncryptionService;
use Pyz\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface;
use Pyz\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriter;
use Pyz\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriterInterface;
use Pyz\Zed\ShopConfiguration\Persistence\ShopConfigurationRepository;
use Pyz\Zed\ShopConfiguration\ShopConfigurationDependencyProvider;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

/**
 * @method \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 * @method \Pyz\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface getRepository()
 * @method \Pyz\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface getEntityManager()
 */
class ShopConfigurationBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface
     */
    public function createConfigFileReader(): ConfigFileReaderInterface
    {
        return new ConfigFileReader(
            $this->getConfig(),
            $this->createYamlParser(),
            $this->createXmlParser(),
            $this->createJsonParser()
        );
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface
     */
    public function createConfigNormalizer(): ConfigNormalizerInterface
    {
        return new ConfigNormalizer();
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigValidator\ConfigValidatorInterface
     */
    public function createConfigValidator(): ConfigValidatorInterface
    {
        return new ConfigValidator();
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface
     */
    public function createEffectiveConfigResolver(): EffectiveConfigResolverInterface
    {
        return new EffectiveConfigResolver(
            $this->createConfigFileReader(),
            $this->createConfigNormalizer(),
            $this->getRepository(),
            $this->createEncryptionService()
        );
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriterInterface
     */
    public function createShopConfigurationWriter(): ShopConfigurationWriterInterface
    {
        return new ShopConfigurationWriter(
            $this->getEntityManager(),
            $this->createEncryptionService(),
            $this->getConfig()
        );
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisherInterface
     */
    public function createShopConfigurationPublisher(): ShopConfigurationPublisherInterface
    {
        return new ShopConfigurationPublisher(
            $this->createEffectiveConfigResolver(),
            $this->getRedisClient(),
            $this->getSynchronizationService(),
            $this->getUtilEncodingService()
        );
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface
     */
    public function createEncryptionService(): EncryptionServiceInterface
    {
        return new EncryptionService(
            $this->getConfig()
        );
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser
     */
    protected function createYamlParser(): YamlParser
    {
        return new YamlParser();
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser
     */
    protected function createXmlParser(): XmlParser
    {
        return new XmlParser();
    }

    /**
     * @return \Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser
     */
    protected function createJsonParser(): JsonParser
    {
        return new JsonParser();
    }

    /**
     * @return \Spryker\Service\UtilEncoding\UtilEncodingServiceInterface
     */
    protected function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    /**
     * @return \Spryker\Zed\Store\Business\StoreFacadeInterface
     */
    protected function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::FACADE_STORE);
    }

    /**
     * @return \Spryker\Zed\Locale\Business\LocaleFacadeInterface
     */
    protected function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::FACADE_LOCALE);
    }

    /**
     * @return mixed
     */
    protected function getRedisClient()
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::CLIENT_REDIS);
    }

    /**
     * @return \Spryker\Service\Synchronization\SynchronizationServiceInterface
     */
    protected function getSynchronizationService(): SynchronizationServiceInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::SERVICE_SYNCHRONIZATION);
    }
}
