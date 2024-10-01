<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage;

use Pyz\Client\ProductAttributeGroupStorage\ProductAttributeGroupStorageClientInterface;
use Pyz\Client\ProductComparison\ProductComparisonClientInterface;
use Pyz\Yves\ProductComparisonPage\Validator\ComparisonValidator;
use Pyz\Yves\ProductComparisonPage\ViewModel\ProductComparisonPageViewBuilder;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ProductComparisonPageFactory extends AbstractFactory
{
    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonPageDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        return $this->getProvidedDependency(ProductComparisonPageDependencyProvider::SERVICE_FORM_CSRF_PROVIDER);
    }

    public function getProductComparisonClient(): ProductComparisonClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonPageDependencyProvider::CLIENT_PRODUCT_COMPARISON);
    }

    public function getProductAttributeGroupStorageClient(): ProductAttributeGroupStorageClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonPageDependencyProvider::CLIENT_PRODUCT_ATTRIBUTE_GROUP_STORAGE);
    }

    public function createProductComparisonPageViewBuilder(): ProductComparisonPageViewBuilder
    {
        return new ProductComparisonPageViewBuilder(
            $this->getProductStorageClient(),
            $this->getLocaleClient(),
            $this->getProductAttributeGroupStorageClient(),
        );
    }

    public function createComparisonValidator(): ComparisonValidator
    {
        return new ComparisonValidator();
    }

    public function getProductStorageClient(): ProductStorageClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonPageDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function getLocaleClient(): LocaleClientInterface
    {
        return $this->getProvidedDependency(ProductComparisonPageDependencyProvider::CLIENT_LOCALE);
    }
}
