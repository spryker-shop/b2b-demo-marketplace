<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\HealthCheck;

use SprykerTest\Glue\Testify\Tester\ApiEndToEndTester;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class HealthCheckApiTester extends ApiEndToEndTester
{
    use _generated\HealthCheckApiTesterActions;

    /**
     * @param string $attributeKey
     *
     * @return mixed
     */
    public function getHealthCheckAttribute(string $attributeKey)
    {
        $matches = $this->getDataFromResponseByJsonPath(sprintf('$..attributes.%s', $attributeKey));

        if (is_array($matches)) {
            return $matches[0] ?? null;
        }

        return $matches;
    }

    /**
     * Returns the full `attributes` block as an associative array, regardless of whether `data`
     * is wrapped in an array (legacy) or a single object (API Platform).
     *
     * @return array<string, mixed>
     */
    public function getHealthCheckAttributes(): array
    {
        $matches = $this->getDataFromResponseByJsonPath('$..attributes');

        if (!is_array($matches) || $matches === []) {
            return [];
        }

        $first = $matches[0] ?? null;

        return is_array($first) ? $first : [];
    }
}
