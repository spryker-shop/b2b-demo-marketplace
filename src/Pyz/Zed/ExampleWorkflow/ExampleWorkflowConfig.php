<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class ExampleWorkflowConfig extends AbstractBundleConfig
{
    /**
     * Requires the Propel event behavior on spy_company.
     */
    public const EVENT_COMPANY_CREATE = 'Entity.spy_company.create';

    public const SUBJECT_TYPE_COMPANY = 'Company';

    public const EVENT_MERCHANT_CREATE = 'Merchant.created';

    public const SUBJECT_TYPE_MERCHANT = 'Merchant';

    /**
     * Requires the Propel event behavior on spy_merchant_relationship.
     */
    public const EVENT_MERCHANT_RELATIONSHIP_CREATE = 'Entity.spy_merchant_relationship.create';

    public const SUBJECT_TYPE_MERCHANT_RELATIONSHIP = 'MerchantRelationship';

    /**
     * Requires the Propel event behavior on spy_merchant_product_abstract.
     */
    public const EVENT_MERCHANT_PRODUCT_ABSTRACT_CREATE = 'Entity.spy_merchant_product_abstract.create';

    public const SUBJECT_TYPE_MERCHANT_PRODUCT = 'MerchantProduct';
}
