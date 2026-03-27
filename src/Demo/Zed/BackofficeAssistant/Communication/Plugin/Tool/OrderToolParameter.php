<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Plugin\Tool;

use Spryker\Zed\AiFoundation\Dependency\Tools\ToolParameterInterface;

class OrderToolParameter implements ToolParameterInterface
{
    public function getName(): string
    {
        return 'order_reference';
    }

    public function getType(): string
    {
        return 'string';
    }

    public function getDescription(): string
    {
        return 'The order reference identifier, e.g. DE--1';
    }

    public function isRequired(): bool
    {
        return true;
    }
}
