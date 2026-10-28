<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ProductOfferServicePoint;

use Spryker\Zed\ProductOfferServicePoint\ProductOfferServicePointConfig as SprykerProductOfferServicePointConfig;

class ProductOfferServicePointConfig extends SprykerProductOfferServicePointConfig
{
    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\Controller\EditOfferController::indexAction()
     */
    protected const string URL_PRODUCT_OFFER_EDIT = '/self-service-portal/edit-offer';

    /**
     * @uses \SprykerFeature\Zed\SelfServicePortal\Communication\Controller\EditOfferController::PARAM_ID_PRODUCT_OFFER
     */
    protected const string PARAM_ID_PRODUCT_OFFER_EDIT_URL = 'id_product_offer';

    /**
     * @uses \Spryker\Zed\ProductOfferGui\Communication\Controller\ViewController::indexAction()
     */
    protected const string URL_PRODUCT_OFFER_VIEW = '/product-offer-gui/view';

    /**
     * @uses \Spryker\Zed\ProductOfferGui\Communication\Controller\ViewController::PARAM_ID_PRODUCT_OFFER
     */
    protected const string PARAM_ID_PRODUCT_OFFER_VIEW_URL = 'id-product-offer';

    /**
     * @api
     */
    public function findProductOfferEditUrl(): ?string
    {
        return static::URL_PRODUCT_OFFER_EDIT;
    }

    /**
     * @api
     */
    public function findProductOfferViewUrl(): ?string
    {
        return static::URL_PRODUCT_OFFER_VIEW;
    }
}
