<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Vertex;

use SprykerEco\Glue\Vertex\VertexConfig;
use SprykerTest\Glue\Testify\Tester\ApiEndToEndTester;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(\PyzTest\Glue\Vertex\PHPMD)
 */
class VertexApiTester extends ApiEndToEndTester
{
    use _generated\VertexApiTesterActions;

    /**
     * @param string|null $taxId
     * @param string|null $countryCode
     *
     * @return array<string, mixed>
     */
    public function buildTaxIdValidationRequestBody(?string $taxId, ?string $countryCode): array
    {
        $attributes = [];

        if ($taxId !== null) {
            $attributes['taxId'] = $taxId;
        }

        if ($countryCode !== null) {
            $attributes['countryCode'] = $countryCode;
        }

        return [
            'data' => [
                'type' => VertexConfig::RESOURCE_TAX_VALIATE_ID,
                'attributes' => $attributes,
            ],
        ];
    }
}
