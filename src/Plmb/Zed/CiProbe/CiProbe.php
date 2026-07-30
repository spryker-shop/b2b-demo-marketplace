<?php

namespace Plmb\Zed\CiProbe;

use Plmb\Zed\Stock\StockConfig;
use Spryker\Zed\Kernel\AbstractBundleConfig;

/**
 * ============================================================================
 * DELIBERATELY BROKEN - CI PROBE. DELETE THIS FILE.
 * ============================================================================
 *
 * This class exists ONLY to prove that the static-analysis jobs in
 * .github/workflows/ci.yml actually fail the pipeline. It is not real code and
 * nothing references it.
 *
 * Expected CI result while this file is present - the "Static analysis" job FAILS at:
 *
 *   Run CodeStyle checks   vendor/bin/console code:sniff:style
 *                          -> 41 errors (tabs, missing declare(strict_types),
 *                             no file doc block, unused import, snake_case method)
 *
 *   Run PHPStan            vendor/bin/phpstan analyze -l 6 -c phpstan.neon src/
 *                          -> 5 errors (missingType.return, missingType.parameter,
 *                             method.notFound, return.type, missingType.iterableValue)
 *
 * The job runs its steps in order and stops at the first failure, so the CS step
 * fails first; PHPStan is reached only once the CS errors are removed.
 *
 * `php -l` still passes - the file is syntactically valid on purpose, so the failure
 * comes from the analysers rather than a parse error.
 *
 * Once the red run is observed: delete src/Plmb/Zed/CiProbe/ and confirm the job
 * goes green again.
 */
class CiProbe
{
	/**
	 * CS: tab indentation, no declare(strict_types), snake_case method name.
	 * PHPStan L6: no return type, no param type.
	 */
	public function do_the_thing($input)
	{
		$unused = 'never read';

		return $input;
	}

	/**
	 * PHPStan L6: calls a method that does not exist on StockConfig.
	 */
	public function callsAMethodThatDoesNotExist(): string
	{
		$config = new StockConfig();

		return $config->thisMethodDoesNotExistAnywhere();
	}

	/**
	 * PHPStan L6: declared to return int, actually returns a string.
	 */
	public function returnsTheWrongType(): int
	{
		return 'definitely not an int';
	}

	/**
	 * PHPStan L6: array return type with no value type specified.
	 *
	 * @return array
	 */
	public function untypedArray(): array
	{
		return [1, 'two', 3.0];
	}
}
