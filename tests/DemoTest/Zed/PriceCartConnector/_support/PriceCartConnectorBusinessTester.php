<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace DemoTest\Zed\PriceCartConnector;

use Codeception\Actor;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Shared\Price\PriceConfig;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 * @method \Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface getFacade()
 *
 * @SuppressWarnings(\DemoTest\Zed\PriceCartConnector\PHPMD)
 */
class PriceCartConnectorBusinessTester extends Actor
{
    use _generated\PriceCartConnectorBusinessTesterActions;

    public const string STORE_NAME = 'DE';

    public const string CURRENCY_ISO_CODE = 'EUR';

    public function haveProductWithPrice(int $grossAmount, int $netAmount, ?int $costAmount): ProductConcreteTransfer
    {
        $productConcreteTransfer = $this->haveProduct();

        $this->havePriceProduct([
            PriceProductTransfer::SKU_PRODUCT_ABSTRACT => $productConcreteTransfer->getAbstractSku(),
            PriceProductTransfer::SKU_PRODUCT => $productConcreteTransfer->getSku(),
            PriceProductTransfer::ID_PRODUCT => $productConcreteTransfer->getIdProductConcrete(),
            PriceProductTransfer::ID_PRODUCT_ABSTRACT => $productConcreteTransfer->getFkProductAbstract(),
            PriceProductTransfer::MONEY_VALUE => [
                MoneyValueTransfer::GROSS_AMOUNT => $grossAmount,
                MoneyValueTransfer::NET_AMOUNT => $netAmount,
                MoneyValueTransfer::COST_AMOUNT => $costAmount,
                MoneyValueTransfer::STORE => $this->getStoreTransfer(),
                MoneyValueTransfer::CURRENCY => $this->getCurrencyTransfer(),
            ],
        ]);

        return $productConcreteTransfer;
    }

    public function createCartChangeForSku(string $sku): CartChangeTransfer
    {
        $quoteTransfer = (new QuoteTransfer())
            ->setStore($this->getStoreTransfer())
            ->setCurrency($this->getCurrencyTransfer())
            ->setPriceMode(PriceConfig::PRICE_MODE_GROSS);

        return (new CartChangeTransfer())
            ->setQuote($quoteTransfer)
            ->addItem((new ItemTransfer())->setSku($sku)->setQuantity(1));
    }

    public function getStoreTransfer(): StoreTransfer
    {
        return $this->getLocator()->store()->facade()->getStoreByName(static::STORE_NAME);
    }

    public function getCurrencyTransfer(): CurrencyTransfer
    {
        return $this->getLocator()->currency()->facade()->fromIsoCode(static::CURRENCY_ISO_CODE);
    }
}
