<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\ConfigValidator;

use Generated\Shared\Transfer\ShopConfigurationOptionTransfer;
use Generated\Shared\Transfer\ShopConfigurationSectionTransfer;

class ConfigValidator implements ConfigValidatorInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ShopConfigurationSectionTransfer> $sections
     *
     * @return array<string>
     */
    public function validateConfiguration(array $sections): array
    {
        $errors = [];
        $usedKeys = [];

        foreach ($sections as $section) {
            $errors = array_merge($errors, $this->validateSection($section, $usedKeys));
        }

        return $errors;
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationOptionTransfer $option
     * @param mixed $value
     *
     * @return array<string>
     */
    public function validateOptionValue(ShopConfigurationOptionTransfer $option, $value): array
    {
        $errors = [];

        // Required field validation
        if ($option->getRequired() && ($value === null || $value === '')) {
            $errors[] = sprintf('Option "%s" is required', $option->getKey());
            return $errors;
        }

        if ($value === null || $value === '') {
            return $errors; // No further validation for empty optional fields
        }

        // Data type validation
        $errors = array_merge($errors, $this->validateDataType($option, $value));

        // Enum validation
        if ($option->getDataType() === 'enum' && !empty($option->getEnumValues())) {
            if (!in_array($value, $option->getEnumValues())) {
                $errors[] = sprintf(
                    'Option "%s" must be one of: %s',
                    $option->getKey(),
                    implode(', ', $option->getEnumValues())
                );
            }
        }

        // Custom validation rules
        $validationRules = $option->getValidation();
        if (!empty($validationRules)) {
            $errors = array_merge($errors, $this->validateCustomRules($option, $value, $validationRules));
        }

        return $errors;
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationSectionTransfer $section
     * @param array<string> &$usedKeys
     *
     * @return array<string>
     */
    protected function validateSection(ShopConfigurationSectionTransfer $section, array &$usedKeys): array
    {
        $errors = [];

        foreach ($section->getOptions() as $option) {
            $fullKey = $option->getModule() . '.' . $option->getKey();

            // Check for duplicate keys
            if (in_array($fullKey, $usedKeys)) {
                $errors[] = sprintf('Duplicate option key: %s', $fullKey);
            } else {
                $usedKeys[] = $fullKey;
            }

            // Validate option structure
            if (!$option->getKey()) {
                $errors[] = 'Option key is required';
            }

            if (!$option->getModule()) {
                $errors[] = sprintf('Module is required for option "%s"', $option->getKey());
            }

            if (!in_array($option->getDataType(), $this->getSupportedDataTypes())) {
                $errors[] = sprintf(
                    'Unsupported data type "%s" for option "%s"',
                    $option->getDataType(),
                    $option->getKey()
                );
            }
        }

        return $errors;
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationOptionTransfer $option
     * @param mixed $value
     *
     * @return array<string>
     */
    protected function validateDataType(ShopConfigurationOptionTransfer $option, $value): array
    {
        $errors = [];
        $dataType = $option->getDataType();

        switch ($dataType) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    $errors[] = sprintf('Option "%s" must be an integer', $option->getKey());
                }
                break;

            case 'float':
                if (!is_numeric($value)) {
                    $errors[] = sprintf('Option "%s" must be a number', $option->getKey());
                }
                break;

            case 'bool':
                if (!is_bool($value) && !in_array(strtolower((string)$value), ['true', 'false', '1', '0'])) {
                    $errors[] = sprintf('Option "%s" must be a boolean value', $option->getKey());
                }
                break;

            case 'json':
                if (is_string($value)) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $errors[] = sprintf('Option "%s" must be valid JSON', $option->getKey());
                    }
                }
                break;

            case 'array':
                if (!is_array($value) && !is_string($value)) {
                    $errors[] = sprintf('Option "%s" must be an array', $option->getKey());
                } elseif (is_string($value)) {
                    // Try to decode as JSON array
                    $decoded = json_decode($value, true);
                    if (!is_array($decoded)) {
                        $errors[] = sprintf('Option "%s" must be a valid JSON array', $option->getKey());
                    }
                }
                break;
        }

        return $errors;
    }

    /**
     * @param \Generated\Shared\Transfer\ShopConfigurationOptionTransfer $option
     * @param mixed $value
     * @param array $validationRules
     *
     * @return array<string>
     */
    protected function validateCustomRules(ShopConfigurationOptionTransfer $option, $value, array $validationRules): array
    {
        $errors = [];

        foreach ($validationRules as $rule => $ruleValue) {
            switch ($rule) {
                case 'min':
                    if (is_numeric($value) && $value < $ruleValue) {
                        $errors[] = sprintf(
                            'Option "%s" must be at least %s',
                            $option->getKey(),
                            $ruleValue
                        );
                    }
                    break;

                case 'max':
                    if (is_numeric($value) && $value > $ruleValue) {
                        $errors[] = sprintf(
                            'Option "%s" must not exceed %s',
                            $option->getKey(),
                            $ruleValue
                        );
                    }
                    break;

                case 'minLength':
                    if (is_string($value) && strlen($value) < $ruleValue) {
                        $errors[] = sprintf(
                            'Option "%s" must be at least %d characters long',
                            $option->getKey(),
                            $ruleValue
                        );
                    }
                    break;

                case 'maxLength':
                    if (is_string($value) && strlen($value) > $ruleValue) {
                        $errors[] = sprintf(
                            'Option "%s" must not exceed %d characters',
                            $option->getKey(),
                            $ruleValue
                        );
                    }
                    break;

                case 'pattern':
                    if (is_string($value) && !preg_match($ruleValue, $value)) {
                        $errors[] = sprintf(
                            'Option "%s" does not match the required pattern',
                            $option->getKey()
                        );
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * @return array<string>
     */
    protected function getSupportedDataTypes(): array
    {
        return ['string', 'int', 'float', 'bool', 'array', 'json', 'enum'];
    }
}
