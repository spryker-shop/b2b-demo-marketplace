<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\ConfigFileReader\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserInterface
{
    /**
     * @param string $content
     *
     * @return array
     */
    public function parse(string $content): array
    {
        try {
            $data = Yaml::parse($content);
            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Invalid YAML content: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function supports(string $extension): bool
    {
        return in_array($extension, ['yml', 'yaml']);
    }
}
