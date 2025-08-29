<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\ConfigValidator;

interface ConfigValidatorInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationSectionTransfer> $sections
     *
     * @return array<string> Array of validation error messages
     */
    public function validateConfiguration(array $sections): array;

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationOptionTransfer $option
     * @param mixed $value
     *
     * @return array<string> Array of validation error messages
     */
    public function validateOptionValue(\Generated\Shared\Transfer\ShopConfigurationOptionTransfer $option, $value): array;
}
