<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce;

use Demo\Zed\AiCommerce\Communication\Plugin\Agent\PlaceOrderAgentPlugin;
use Spryker\Zed\ContentBannerGui\Communication\Plugin\ContentGui\ContentBannerContentGuiEditorPlugin;
use Spryker\Zed\ContentFileGui\Communication\Plugin\ContentGui\ContentFileListContentGuiEditorPlugin;
use Spryker\Zed\ContentNavigationGui\Communication\Plugin\ContentGui\ContentNavigationContentGuiEditorPlugin;
use Spryker\Zed\ContentProductGui\Communication\Plugin\ContentGui\ContentProductContentGuiEditorPlugin;
use Spryker\Zed\ContentProductSetGui\Communication\Plugin\ContentGui\ContentProductSetGuiEditorPlugin;
use Spryker\Zed\Kernel\Container;
use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider as SprykerFeatureAiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\DiscountManagementAgentPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\FormFillAgentPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\GeneralAgentPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\OrderManagementAgentPlugin;

class AiCommerceDependencyProvider extends SprykerFeatureAiCommerceDependencyProvider
{
    public const string FACADE_CART = 'FACADE_CART';

    public const string FACADE_CART_CODE = 'FACADE_CART_CODE';

    public const string FACADE_CART_NOTE = 'FACADE_CART_NOTE';

    public const string FACADE_CHECKOUT = 'FACADE_CHECKOUT';

    public const string FACADE_CUSTOMER = 'FACADE_CUSTOMER';

    public const string FACADE_MESSENGER = 'FACADE_MESSENGER';

    public const string FACADE_PAYMENT = 'FACADE_PAYMENT';

    public const string FACADE_QUOTE = 'FACADE_QUOTE';

    public const string FACADE_SHIPMENT = 'FACADE_SHIPMENT';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addCartFacade($container);
        $container = $this->addCartCodeFacade($container);
        $container = $this->addCartNoteFacade($container);
        $container = $this->addCheckoutFacade($container);
        $container = $this->addCustomerFacade($container);
        $container = $this->addMessengerFacade($container);
        $container = $this->addPaymentFacade($container);
        $container = $this->addQuoteFacade($container);
        $container = $this->addShipmentFacade($container);

        return $container;
    }

    protected function addCartFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART, function (Container $container) {
            return $container->getLocator()->cart()->facade();
        });

        return $container;
    }

    protected function addCartCodeFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART_CODE, function (Container $container) {
            return $container->getLocator()->cartCode()->facade();
        });

        return $container;
    }

    protected function addCartNoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_CART_NOTE, function (Container $container) {
            return $container->getLocator()->cartNote()->facade();
        });

        return $container;
    }

    protected function addCheckoutFacade(Container $container): Container
    {
        $container->set(static::FACADE_CHECKOUT, function (Container $container) {
            return $container->getLocator()->checkout()->facade();
        });

        return $container;
    }

    protected function addCustomerFacade(Container $container): Container
    {
        $container->set(static::FACADE_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->facade();
        });

        return $container;
    }

    protected function addMessengerFacade(Container $container): Container
    {
        $container->set(static::FACADE_MESSENGER, function (Container $container) {
            return $container->getLocator()->messenger()->facade();
        });

        return $container;
    }

    protected function addPaymentFacade(Container $container): Container
    {
        $container->set(static::FACADE_PAYMENT, function (Container $container) {
            return $container->getLocator()->payment()->facade();
        });

        return $container;
    }

    protected function addQuoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE, function (Container $container) {
            return $container->getLocator()->quote()->facade();
        });

        return $container;
    }

    protected function addShipmentFacade(Container $container): Container
    {
        $container->set(static::FACADE_SHIPMENT, function (Container $container) {
            return $container->getLocator()->shipment()->facade();
        });

        return $container;
    }

    /**
     * @return array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface>
     */
    protected function getBackofficeAssistantAgentPlugins(): array
    {
        return [
            new GeneralAgentPlugin(),
            new OrderManagementAgentPlugin(),
            new DiscountManagementAgentPlugin(),
            new FormFillAgentPlugin(),
            new PlaceOrderAgentPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\ContentGuiExtension\Dependency\Plugin\ContentGuiEditorPluginInterface>
     */
    protected function getContentGuiEditorPlugins(): array
    {
        return [
            new ContentBannerContentGuiEditorPlugin(),
            new ContentProductContentGuiEditorPlugin(),
            new ContentProductSetGuiEditorPlugin(),
            new ContentFileListContentGuiEditorPlugin(),
            new ContentNavigationContentGuiEditorPlugin(),
        ];
    }
}
