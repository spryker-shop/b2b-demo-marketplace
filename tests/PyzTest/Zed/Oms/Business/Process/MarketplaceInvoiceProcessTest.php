<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\Oms\Business\Process;

use Codeception\Test\Unit;
use PyzTest\Zed\Oms\OmsBusinessTester;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\Business\Process\TransitionInterface;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group Oms
 * @group Business
 * @group Process
 * @group MarketplaceInvoiceProcessTest
 * Add your own group annotations below this line
 */
class MarketplaceInvoiceProcessTest extends Unit
{
    protected const string PROCESS_NAME = 'MarketplacePayment01';

    protected const string STATE_SHIPPED_BY_MERCHANT = 'shipped by merchant';

    protected const string STATE_INVOICE_GENERATED = 'invoice generated';

    protected const string STATE_DELIVERED = 'delivered';

    protected const string EVENT_INVOICE_GENERATE = 'invoice-generate';

    protected const string EVENT_DELIVER = 'deliver';

    protected const string COMMAND_MERCHANT_INVOICE_GENERATE = 'MerchantInvoice/Generate';

    protected OmsBusinessTester $tester;

    public function testMarketplacePaymentProcessGeneratesMerchantInvoicesOnEnteringShippedByMerchantAndContinuesToDelivered(): void
    {
        // Act
        $processes = $this->tester->getFacade()->getProcesses();

        // Assert
        $this->assertArrayHasKey(static::PROCESS_NAME, $processes);
        $process = $processes[static::PROCESS_NAME];

        $invoiceGenerateTransition = $this->findTransition($process, static::STATE_SHIPPED_BY_MERCHANT, static::STATE_INVOICE_GENERATED, static::EVENT_INVOICE_GENERATE);
        $this->assertNotNull($invoiceGenerateTransition);
        $this->assertTrue($invoiceGenerateTransition->getEvent()->isOnEnter());
        $this->assertSame(static::COMMAND_MERCHANT_INVOICE_GENERATE, $invoiceGenerateTransition->getEvent()->getCommand());

        $this->assertNotNull($this->findTransition($process, static::STATE_INVOICE_GENERATED, static::STATE_DELIVERED, static::EVENT_DELIVER));
        $this->assertTrue($process->getStateFromAllProcesses(static::STATE_INVOICE_GENERATED)->isReserved());
    }

    public function testMarketplacePaymentProcessDoesNotAllowDeliveryBeforeInvoiceIsGenerated(): void
    {
        // Act
        $process = $this->tester->getFacade()->getProcesses()[static::PROCESS_NAME];

        // Assert
        $this->assertNull($this->findTransition($process, static::STATE_SHIPPED_BY_MERCHANT, static::STATE_DELIVERED, static::EVENT_DELIVER));
    }

    public function testMarketplacePaymentProcessGeneratesInvoicesOnlyWhenEnteringShippedByMerchant(): void
    {
        // Act
        $process = $this->tester->getFacade()->getProcesses()[static::PROCESS_NAME];

        // Assert
        foreach ($process->getAllTransitions() as $transition) {
            if (!$transition->hasEvent() || $transition->getEvent()->getName() !== static::EVENT_INVOICE_GENERATE) {
                continue;
            }

            $this->assertSame(static::STATE_SHIPPED_BY_MERCHANT, $transition->getSource()->getName());
            $this->assertSame(static::STATE_INVOICE_GENERATED, $transition->getTarget()->getName());
        }

        $this->assertFalse($this->findTransition($process, static::STATE_SHIPPED_BY_MERCHANT, static::STATE_INVOICE_GENERATED, static::EVENT_INVOICE_GENERATE)->getEvent()->isManual());
    }

    protected function findTransition(ProcessInterface $process, string $sourceStateName, string $targetStateName, string $eventName): ?TransitionInterface
    {
        foreach ($process->getAllTransitions() as $transition) {
            if (
                $transition->hasEvent()
                && $transition->getEvent()->getName() === $eventName
                && $transition->getSource()->getName() === $sourceStateName
                && $transition->getTarget()->getName() === $targetStateName
            ) {
                return $transition;
            }
        }

        return null;
    }
}
