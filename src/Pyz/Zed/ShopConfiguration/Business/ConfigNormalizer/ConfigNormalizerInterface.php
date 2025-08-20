<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\ConfigNormalizer;

use Generated\Shared\Transfer\ShopConfigurationSectionTransfer;

interface ConfigNormalizerInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationFileDataTransfer> $fileDataTransfers
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationSectionTransfer>
     */
    public function normalizeConfigurationData(array $fileDataTransfers): array;
}
