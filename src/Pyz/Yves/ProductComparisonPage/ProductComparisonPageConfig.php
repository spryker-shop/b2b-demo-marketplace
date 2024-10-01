<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage;

use Spryker\Yves\Kernel\AbstractBundleConfig;

class ProductComparisonPageConfig extends AbstractBundleConfig
{
    public const COMPARISON_LIST_LIMIT = 3;

    public const COMPARISON_PRODUCT_ATTRIBUTE_GROUPS_ORDER = [
        'Capabilities',
        'Standards & certifications',
        'Compatibility',
        'Quality & maturity',
        'Services provided',
        'Usage requirements',
        'Product packaging',
        'Commercial information',
    ];
}
