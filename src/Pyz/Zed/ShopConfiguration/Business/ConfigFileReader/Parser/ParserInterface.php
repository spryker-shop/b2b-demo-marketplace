<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser;

interface ParserInterface
{
    /**
     * @param string $content
     *
     * @return array
     */
    public function parse(string $content): array;

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function supports(string $extension): bool;
}
