<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business;

use Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser;
use Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface;
use Go\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisher;
use Go\Zed\ShopConfiguration\Business\Security\EncryptionService;
use Go\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriterInterface;
use Go\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReader;
use Go\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface;
use Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser;
use Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser;
use Go\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizer;
use Go\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface;
use Go\Zed\ShopConfiguration\Business\ConfigValidator\ConfigValidator;
use Go\Zed\ShopConfiguration\Business\ConfigValidator\ConfigValidatorInterface;
use Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolver;
use Go\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisherInterface;
use Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface;
use Go\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriter;
use Go\Zed\ShopConfiguration\ShopConfigurationDependencyProvider;
use Spryker\Service\Synchronization\SynchronizationServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

/**
 * @method \Go\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface getRepository()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface getEntityManager()
 */
class ShopConfigurationBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Go\Zed\ShopConfiguration\Business\ConfigFileReader\ConfigFileReaderInterface
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
     * @return \Go\Zed\ShopConfiguration\Business\ConfigNormalizer\ConfigNormalizerInterface
     */
    public function createConfigNormalizer(): ConfigNormalizerInterface
    {
        return new ConfigNormalizer();
    }

    /**
     * @return \Go\Zed\ShopConfiguration\Business\ConfigValidator\ConfigValidatorInterface
     */
    public function createConfigValidator(): ConfigValidatorInterface
    {
        return new ConfigValidator();
    }

    /**
     * @return \Go\Zed\ShopConfiguration\Business\EffectiveConfigResolver\EffectiveConfigResolverInterface
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
     * @return \Go\Zed\ShopConfiguration\Business\Writer\ShopConfigurationWriterInterface
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
     * @return \Go\Zed\ShopConfiguration\Business\Publisher\ShopConfigurationPublisherInterface
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
     * @return \Go\Zed\ShopConfiguration\Business\Security\EncryptionServiceInterface
     */
    public function createEncryptionService(): EncryptionServiceInterface
    {
        return new EncryptionService(
            $this->getConfig()
        );
    }

    /**
     * @return \Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\YamlParser
     */
    protected function createYamlParser(): YamlParser
    {
        return new YamlParser();
    }

    /**
     * @return \Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\XmlParser
     */
    protected function createXmlParser(): XmlParser
    {
        return new XmlParser();
    }

    /**
     * @return \Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser\JsonParser
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
