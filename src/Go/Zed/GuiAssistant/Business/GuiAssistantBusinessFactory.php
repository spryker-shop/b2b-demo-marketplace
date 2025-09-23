<?php

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Business;

use Go\Zed\GuiAssistant\Business\Builder\OrderTransferBuilder;
use Go\Zed\GuiAssistant\Business\Builder\ProductTransferBuilder;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

class GuiAssistantBusinessFactory extends AbstractBusinessFactory
{
    public function createProductTransferBuilder(): ProductTransferBuilder
    {
        return new ProductTransferBuilder();
    }

    public function createOrderTransferBuilder(): OrderTransferBuilder
    {
        return new OrderTransferBuilder();
    }
}
