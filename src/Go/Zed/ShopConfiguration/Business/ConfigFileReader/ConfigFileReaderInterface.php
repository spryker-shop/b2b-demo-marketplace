<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\ConfigFileReader;

use Generated\Shared\Transfer\ShopConfigurationFileDataTransfer;

interface ConfigFileReaderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\ShopConfigurationFileDataTransfer>
     */
    public function readAllConfigurationFiles(): array;

    /**
     * @param string $module
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationFileDataTransfer>
     */
    public function readConfigurationFilesForModule(string $module): array;
}
