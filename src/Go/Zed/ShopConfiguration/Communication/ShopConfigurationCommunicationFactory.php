<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Communication;

use Go\Zed\ShopConfiguration\Communication\Form\StoreConfigurationForm;
use Go\Zed\ShopConfiguration\ShopConfigurationDependencyProvider;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method \Go\Zed\ShopConfiguration\ShopConfigurationConfig getConfig()
 * @method \Go\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepositoryInterface getRepository()
 * @method \Go\Zed\ShopConfiguration\Persistence\ShopConfigurationEntityManagerInterface getEntityManager()
 */
class ShopConfigurationCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Spryker\Zed\Store\Business\StoreFacadeInterface
     */
    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(ShopConfigurationDependencyProvider::FACADE_STORE);
    }

    /**
     * @param array|null $data
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getStoreConfigurationForm($data = null, array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(StoreConfigurationForm::class, $data, $options);
    }

    /**
     * @return \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface
     */
    public function getLocator(): \Spryker\Shared\Kernel\LocatorLocatorInterface
    {
        return $this->getContainer()->getLocator();
    }

    /**
     * @return \Spryker\Service\FileSystem\FileSystemServiceInterface
     */
    public function getFileSystemService(): \Spryker\Service\FileSystem\FileSystemServiceInterface
    {
        return $this->getLocator()->fileSystem()->service();
    }
}
