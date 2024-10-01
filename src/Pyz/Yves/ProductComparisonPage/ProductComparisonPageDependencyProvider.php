<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage;

use Pyz\Client\ProductAttributeGroupStorage\ProductAttributeGroupStorageClientInterface;
use Pyz\Client\ProductComparison\ProductComparisonClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ProductComparisonPageDependencyProvider extends AbstractBundleDependencyProvider
{
    public const CLIENT_LOCALE = 'CLIENT_LOCALE';

    public const CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public const CLIENT_PRODUCT_STORAGE = 'CLIENT_PRODUCT_STORAGE';

    public const CLIENT_STORE = 'CLIENT_STORE';

    public const SERVICE_FORM_CSRF_PROVIDER = 'form.csrf_provider';

    public const CLIENT_PRODUCT_COMPARISON = 'CLIENT_PRODUCT_COMPARISON';

    public const CLIENT_PRODUCT_ATTRIBUTE_GROUP_STORAGE = 'CLIENT_PRODUCT_ATTRIBUTE_GROUP_STORAGE';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $this->addLocaleClient($container);
        $this->addCustomerClient($container);
        $this->addCsrfProviderService($container);
        $this->addProductStorageClient($container);
        $this->addProductComparisonClient($container);
        $this->addProductAttributeGroupStorageClient($container);

        return $container;
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addLocaleClient(Container $container): void
    {
        $container->set(self::CLIENT_LOCALE, static function (Container $container): LocaleClientInterface {
            return $container->getLocator()->locale()->client();
        });
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addProductStorageClient(Container $container): void
    {
        $container->set(self::CLIENT_PRODUCT_STORAGE, static function (Container $container): ProductStorageClientInterface {
            return $container->getLocator()->productStorage()->client();
        });
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addCustomerClient(Container $container): void
    {
        $container->set(self::CLIENT_CUSTOMER, static function (Container $container): CustomerClientInterface {
            return $container->getLocator()->customer()->client();
        });
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addCsrfProviderService(Container $container): void
    {
        $container->set(self::SERVICE_FORM_CSRF_PROVIDER, static function (Container $container): CsrfTokenManagerInterface {
            return $container->getApplicationService(static::SERVICE_FORM_CSRF_PROVIDER);
        });
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addProductComparisonClient(Container $container): void
    {
        $container->set(self::CLIENT_PRODUCT_COMPARISON, static function (Container $container): ProductComparisonClientInterface {
            return $container->getLocator()->productComparison()->client();
        });
    }

    /**
     * @param \Spryker\Yves\Kernel\Container $container
     *
     * @return void
     */
    private function addProductAttributeGroupStorageClient(Container $container): void
    {
        $container->set(self::CLIENT_PRODUCT_ATTRIBUTE_GROUP_STORAGE, static function (Container $container): ProductAttributeGroupStorageClientInterface {
            return $container->getLocator()->productAttributeGroupStorage()->client();
        });
    }
}
