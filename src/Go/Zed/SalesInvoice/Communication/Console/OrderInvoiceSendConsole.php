<?php

namespace Go\Zed\SalesInvoice\Communication\Console;

use Generated\Shared\Transfer\OrderInvoiceSendRequestTransfer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderInvoiceSendConsole extends \Spryker\Zed\SalesInvoice\Communication\Console\OrderInvoiceSendConsole
{
    use \Go\Zed\TenantBehavior\Communication\Console\TenantIterationTrait;
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderIds = $this->getArgumentOrderIdsValue($input);
        $force = (bool)$input->getOption(static::ARGUMENT_FORCE_EMAIL_SEND);

        $orderInvoiceSendRequestTransfer = (new OrderInvoiceSendRequestTransfer())
            ->setBatch(static::BATCH);

        if ($orderIds) {
            $orderInvoiceSendRequestTransfer->setSalesOrderIds((array)$orderIds)
                ->setBatch(count($orderIds));
        }

        if ($force) {
            $orderInvoiceSendRequestTransfer->setForce($force);
        }

        $this->forEachTenant(function () use ($orderIds, $orderInvoiceSendRequestTransfer): void {
            do {
                $orderInvoiceSendResponseTransfer = $this->getFacade()
                    ->sendOrderInvoices($orderInvoiceSendRequestTransfer);
            } while (!$orderIds && $orderInvoiceSendResponseTransfer->getCount() === $orderInvoiceSendRequestTransfer->getBatch());
        });

        return static::CODE_SUCCESS;
    }
}
