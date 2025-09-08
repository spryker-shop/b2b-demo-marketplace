<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser;

class JsonParser implements ParserInterface
{
    /**
     * @param string $content
     *
     * @return array
     */
    public function parse(string $content): array
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(
                sprintf('Invalid JSON content: %s', json_last_error_msg())
            );
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function supports(string $extension): bool
    {
        return $extension === 'json';
    }
}
