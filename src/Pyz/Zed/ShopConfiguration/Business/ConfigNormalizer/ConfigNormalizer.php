<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\ConfigNormalizer;

use Generated\Shared\Transfer\ShopConfigurationFileDataTransfer;
use Generated\Shared\Transfer\ShopConfigurationOptionTransfer;
use Generated\Shared\Transfer\ShopConfigurationSectionTransfer;

class ConfigNormalizer implements ConfigNormalizerInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationFileDataTransfer> $fileDataTransfers
     *
     * @return array<\Generated\Shared\Transfer\ShopConfigurationSectionTransfer>
     */
    public function normalizeConfigurationData(array $fileDataTransfers): array
    {
        $sections = [];

        foreach ($fileDataTransfers as $fileDataTransfer) {
            $normalizedData = $this->normalizeFileData($fileDataTransfer);

            // Process sections with their nested options
            foreach ($normalizedData['sections'] as $sectionKey => $sectionData) {
                if (!isset($sections[$sectionKey])) {
                    $sections[$sectionKey] = (new ShopConfigurationSectionTransfer())
                        ->setKey($sectionKey)
                        ->setLabel($sectionData['label'] ?? '')
                        ->setDescription($sectionData['description'] ?? '')
                        ->setOrder($sectionData['order'] ?? 0);
                }

                // Add options for this section
                if (isset($sectionData['options']) && is_array($sectionData['options'])) {
                    foreach ($sectionData['options'] as $optionData) {
                        // Ensure optionData is an array and has required fields
                        if (!is_array($optionData) || !isset($optionData['key'])) {
                            continue;
                        }
                        
                        $optionData['section'] = $sectionKey; // Set the section for this option
                        $optionTransfer = $this->createOptionTransfer($optionData);
                        $sections[$sectionKey]->addOption($optionTransfer);
                    }
                }
            }
        }

        // Sort sections by order
        uasort($sections, function (ShopConfigurationSectionTransfer $a, ShopConfigurationSectionTransfer $b) {
            return $a->getOrder() <=> $b->getOrder();
        });

        return array_values($sections);
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationFileDataTransfer $fileDataTransfer
     *
     * @return array
     */
    protected function normalizeFileData(ShopConfigurationFileDataTransfer $fileDataTransfer): array
    {
        $data = $fileDataTransfer->getData();

        return [
            'sections' => $data['sections'] ?? [],
        ];
    }

    /**
     * @param array $optionData
     *
     * @return \Generated\Shared\Transfer\ShopConfigurationOptionTransfer
     */
    protected function createOptionTransfer(array $optionData): ShopConfigurationOptionTransfer
    {
        return (new ShopConfigurationOptionTransfer())
            ->setKey($optionData['key'] ?? '')
            ->setModule($optionData['module'] ?? '')
            ->setSection($optionData['section'] ?? '')
            ->setLabel($optionData['label'] ?? '')
            ->setDescription($optionData['description'] ?? '')
            ->setDataType($optionData['dataType'] ?? 'string')
            ->setDefault($this->normalizeValue($optionData['default'] ?? null))
            ->setRequired($optionData['required'] ?? false)
            ->setOverridable($optionData['overridable'] ?? true)
            ->setEnumValues($optionData['enumValues'] ?? [])
            ->setValidation($optionData['validation'] ?? []);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function normalizeValue($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value) ?: '';
    }
}
