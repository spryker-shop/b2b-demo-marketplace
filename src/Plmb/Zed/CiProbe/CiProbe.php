<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\CiProbe;

/**
 * ============================================================================
 * DELIBERATELY BROKEN - CI PROBE. DELETE THIS FILE.
 * ============================================================================
 *
 * This class exists ONLY to prove that the static-analysis jobs in
 * .github/workflows/ci.yml actually fail the pipeline. It is not real code and
 * nothing references it.
 *
 * ---------------------------------------------------------------------------
 * STAGE 2: CODE STYLE CLEAN ON PURPOSE.
 * ---------------------------------------------------------------------------
 * Stage 1 (45 CS errors) already proved the `Run CodeStyle checks` step gates -
 * observed red on GitHub, 45 errors affecting 38 lines, this file only.
 *
 * The job stops at its FIRST failing step, and CS is step 12 of 20. So while CS
 * was red, everything behind it never executed:
 *
 *   13 Run Architecture rules vendor/bin/phpmd ... architecture-sniffer
 *   14 Run Project Architecture rules vendor/bin/phpmd ... phpmd.xml
 *   17 Run PHPStan vendor/bin/phpstan analyze -l 6
 *
 * This file is now CS-clean (and phpmd-clean) so the job walks past those steps
 * and PHPStan can be proven independently.
 *
 * Expected CI result while this file is present - `Static analysis` FAILS at:
 *
 *   Run PHPStan -> 4 errors:
 *                     method.notFound calls a method that does not exist
 *                     return.type declared int, returns string
 *                     argument.type passes string where int is declared
 *                     missingType.iterableValue `@return array` with no value type
 *
 * NOTE ON WHAT CANNOT BE PROVEN HERE: the `missingType.parameter` and
 * `missingType.return` errors from stage 1 are gone by construction. Spryker CS
 * REQUIRES `@param`/`@return` doc blocks on every method, and those annotations
 * are exactly what PHPStan reads to infer the types - so a CS-clean file cannot
 * carry a missing-type error. The two checks genuinely overlap there; CS already
 * covers it.
 *
 * `php -l` still passes - the file is syntactically valid on purpose, so the
 * failure comes from the analyser rather than a parse error.
 *
 * Once the red PHPStan run is observed: delete src/Plmb/Zed/CiProbe/ and confirm
 * the whole job goes green.
 */
class CiProbe
{
    /**
     * PHPStan L6: calls a method that does not exist on this class.
     *
     * Deliberately on `$this` rather than on a collaborator: instantiating a
     * resolvable Spryker class with `new` trips phpmd's InstanceResolvingRule
     * (step 14, "Run Project Architecture rules"), which runs BEFORE PHPStan and
     * would block this probe from ever reaching the step it is meant to test.
     *
     * @return string
     */
    public function callsAMethodThatDoesNotExist(): string
    {
        return $this->thisMethodDoesNotExistAnywhere();
    }

    /**
     * PHPStan L6: declared to return int, actually returns a string.
     *
     * @return int
     */
    public function returnsTheWrongType(): int
    {
        return 'definitely not an int';
    }

    /**
     * PHPStan L6: passes a string into a parameter declared as int.
     *
     * @return string
     */
    public function passesTheWrongArgumentType(): string
    {
        return $this->formatNumber('not an int');
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

    /**
     * @param int $number
     *
     * @return string
     */
    protected function formatNumber(int $number): string
    {
        return (string)$number;
    }
}
