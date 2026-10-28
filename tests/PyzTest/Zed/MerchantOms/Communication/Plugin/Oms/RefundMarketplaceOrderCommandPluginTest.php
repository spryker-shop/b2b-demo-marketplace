<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\MerchantOms\Communication\Plugin\Oms;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\MerchantOrderItemCriteriaTransfer;
use Generated\Shared\Transfer\MerchantOrderItemTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Pyz\Zed\MerchantOms\Communication\MerchantOmsCommunicationFactory;
use Pyz\Zed\MerchantOms\Communication\Plugin\Oms\RefundMarketplaceOrderCommandPlugin;
use Pyz\Zed\Oms\Business\OmsFacadeInterface;
use PyzTest\Zed\MerchantOms\MerchantOmsCommunicationTester;
use Spryker\Zed\MerchantOms\Dependency\Facade\MerchantOmsToMerchantSalesOrderFacadeInterface;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group MerchantOms
 * @group Communication
 * @group Plugin
 * @group Oms
 * @group RefundMarketplaceOrderCommandPluginTest
 * Add your own group annotations below this line
 */
class RefundMarketplaceOrderCommandPluginTest extends Unit
{
    protected const string EVENT_REFUND = 'refund';

    /**
     * @var array<int, int>
     */
    protected const ID_ORDER_ITEMS_BY_ID_MERCHANT_ORDER_ITEM = [
        111 => 11,
        112 => 12,
        113 => 13,
        114 => 14,
        115 => 15,
    ];

    protected MerchantOmsCommunicationTester $tester;

    public function testRunTriggersRefundOnlyForTheGivenItems(): void
    {
        // Arrange
        $omsFacadeMock = $this->createMock(OmsFacadeInterface::class);

        $omsFacadeMock->expects($this->never())->method('triggerEventForOneOrderItem');
        $omsFacadeMock->expects($this->once())
            ->method('triggerEventForOrderItems')
            ->with(static::EVENT_REFUND, [11])
            ->willReturn([]);

        $refundMarketplaceOrderCommandPlugin = new RefundMarketplaceOrderCommandPlugin();
        $refundMarketplaceOrderCommandPlugin->setFactory($this->createFactoryMock($omsFacadeMock));

        // Act
        $refundMarketplaceOrderCommandPlugin->run([$this->createStateMachineItemTransfer(111)]);
    }

    public function testRunTriggersRefundOnceForAllGivenItems(): void
    {
        // Arrange
        $omsFacadeMock = $this->createMock(OmsFacadeInterface::class);

        $omsFacadeMock->expects($this->once())
            ->method('triggerEventForOrderItems')
            ->with(static::EVENT_REFUND, [11, 12, 13, 14, 15])
            ->willReturn([]);

        $refundMarketplaceOrderCommandPlugin = new RefundMarketplaceOrderCommandPlugin();
        $refundMarketplaceOrderCommandPlugin->setFactory($this->createFactoryMock($omsFacadeMock));

        $stateMachineItemTransfers = [];
        foreach (array_keys(static::ID_ORDER_ITEMS_BY_ID_MERCHANT_ORDER_ITEM) as $idMerchantOrderItem) {
            $stateMachineItemTransfers[] = $this->createStateMachineItemTransfer($idMerchantOrderItem);
        }

        // Act
        $refundMarketplaceOrderCommandPlugin->run($stateMachineItemTransfers);
    }

    public function testRunDoesNotTriggerRefundWhenNoMerchantOrderItemIsFound(): void
    {
        // Arrange
        $omsFacadeMock = $this->createMock(OmsFacadeInterface::class);

        $omsFacadeMock->expects($this->never())->method('triggerEventForOrderItems');

        $merchantSalesOrderFacadeMock = $this->createMock(MerchantOmsToMerchantSalesOrderFacadeInterface::class);
        $merchantSalesOrderFacadeMock->method('findMerchantOrderItem')->willReturn(null);

        $factoryMock = $this->createMock(MerchantOmsCommunicationFactory::class);
        $factoryMock->method('getOmsFacade')->willReturn($omsFacadeMock);
        $factoryMock->method('getMerchantSalesOrderFacade')->willReturn($merchantSalesOrderFacadeMock);

        $refundMarketplaceOrderCommandPlugin = new RefundMarketplaceOrderCommandPlugin();
        $refundMarketplaceOrderCommandPlugin->setFactory($factoryMock);

        // Act
        $refundMarketplaceOrderCommandPlugin->run([$this->createStateMachineItemTransfer(111)]);
    }

    protected function createStateMachineItemTransfer(int $idMerchantOrderItem): StateMachineItemTransfer
    {
        return (new StateMachineItemTransfer())->setIdentifier($idMerchantOrderItem);
    }

    protected function createFactoryMock(OmsFacadeInterface $omsFacadeMock): MerchantOmsCommunicationFactory
    {
        $merchantSalesOrderFacadeMock = $this->createMock(MerchantOmsToMerchantSalesOrderFacadeInterface::class);
        $merchantSalesOrderFacadeMock->method('findMerchantOrderItem')->willReturnCallback(
            function (MerchantOrderItemCriteriaTransfer $merchantOrderItemCriteriaTransfer): ?MerchantOrderItemTransfer {
                $idMerchantOrderItem = $merchantOrderItemCriteriaTransfer->getIdMerchantOrderItem();

                if (!isset(static::ID_ORDER_ITEMS_BY_ID_MERCHANT_ORDER_ITEM[$idMerchantOrderItem])) {
                    return null;
                }

                return (new MerchantOrderItemTransfer())
                    ->setIdMerchantOrderItem($idMerchantOrderItem)
                    ->setIdOrderItem(static::ID_ORDER_ITEMS_BY_ID_MERCHANT_ORDER_ITEM[$idMerchantOrderItem]);
            },
        );

        $factoryMock = $this->createMock(MerchantOmsCommunicationFactory::class);
        $factoryMock->method('getOmsFacade')->willReturn($omsFacadeMock);
        $factoryMock->method('getMerchantSalesOrderFacade')->willReturn($merchantSalesOrderFacadeMock);

        return $factoryMock;
    }
}
