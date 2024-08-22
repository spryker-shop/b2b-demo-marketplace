<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pyz\Yves\ProductComparisonPage\Validator;

use Pyz\Yves\ProductComparisonPage\ProductComparisonPageConfig;

class ComparisonValidator
{
    public function isValidNumberOfProductsForComparison(array $ids): bool
    {
        return count($ids) < ProductComparisonPageConfig::COMPARISON_LIST_LIMIT;
    }
}
