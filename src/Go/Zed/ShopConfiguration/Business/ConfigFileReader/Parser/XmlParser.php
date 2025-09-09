<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\ConfigFileReader\Parser;

class XmlParser implements ParserInterface
{
    /**
     * @param string $content
     *
     * @return array
     */
    public function parse(string $content): array
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($content);
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMessage = 'Invalid XML content';
            if (!empty($errors)) {
                $errorMessage .= ': ' . $errors[0]->message;
            }
            throw new \InvalidArgumentException($errorMessage);
        }

        return $this->xmlToArray($xml);
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public function supports(string $extension): bool
    {
        return $extension === 'xml';
    }

    /**
     * @param \SimpleXMLElement $xml
     *
     * @return array
     */
    protected function xmlToArray(\SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true) ?: [];
    }
}
