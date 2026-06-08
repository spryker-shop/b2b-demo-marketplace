<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CatalogSearch;

use Spryker\Glue\CatalogSearchRestApi\CatalogSearchRestApiConfig;
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
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(\PyzTest\Glue\CatalogSearch\PHPMD)
 */
class CatalogSearchApiTester extends ApiEndToEndTester
{
    use _generated\CatalogSearchApiTesterActions;

    public function buildCatalogSearchUrl(): string
    {
        return $this->formatUrl(CatalogSearchRestApiConfig::RESOURCE_CATALOG_SEARCH);
    }
}
