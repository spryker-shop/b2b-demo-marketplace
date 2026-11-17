<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\Configuration\Communication\Plugin\Configuration;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Zed\ConfigurationExtension\Dependency\Plugin\ConfigurationValuePreSavePluginInterface;

/**
 * Stores global-scope values with `scope_identifier = NULL` instead of an empty string.
 *
 * WHY THIS EXISTS
 * Every read of a global-scope value queries `WHERE scope_identifier IS NULL`:
 *
 *   ConfigurationRepository::findConfigurationValueByKeyAndScope()
 *   ConfigurationRepository::findConfigurationValuesByKeysAndScope()   <- Back Office
 *   ConfigurationRepository::findAllConfigurationValuesByScope()       <- publisher
 *   ConfigurationEntityManager (x2, the upsert lookups)
 *
 * The data importer is the only writer that disagrees. It resolves an empty CSV
 * column to null correctly and then discards that:
 *
 *   ConfigurationValueDataImportStep:52  $scopeIdentifier = $dataSet[...] ?: null;
 *   ConfigurationValueDataImportStep:58  ->setScopeIdentifier($scopeIdentifier ?? '')
 *
 * '' never matches IS NULL, so an imported global value is invisible to all five
 * lookups: the publisher finds nothing, concludes the scope is empty and deletes
 * the storage row, and the Back Office renders the field blank. Both fail
 * silently. Re-importing compounds it - the upsert lookup cannot find the ''
 * row either, so it inserts a duplicate that nothing ever reads.
 *
 * Normalising here catches every write path, not just the importer, and needs no
 * override of the module's factory - which is not viable anyway, because
 * DataImportFactoryTrait::getModuleDataImportDirectory() resolves the import
 * source by reflecting on the concrete factory's file location.
 *
 * Remove this plugin once the upstream `?? ''` is fixed.
 */
class GlobalScopeIdentifierNormalizerPreSavePlugin implements ConfigurationValuePreSavePluginInterface
{
    /**
     * {@inheritDoc}
     * - Sets `scopeIdentifier` to null for every value saved with `global` scope.
     * - Leaves values of any other scope untouched.
     *
     * @api
     */
    public function preSave(
        ConfigurationValueCollectionRequestTransfer $requestTransfer,
    ): ConfigurationValueCollectionRequestTransfer {
        foreach ($requestTransfer->getConfigurationValues() as $configurationValueTransfer) {
            if ($configurationValueTransfer->getScope() !== ConfigurationConstants::SCOPE_GLOBAL) {
                continue;
            }

            if ($configurationValueTransfer->getScopeIdentifier() === null) {
                continue;
            }

            $configurationValueTransfer->setScopeIdentifier(null);
        }

        return $requestTransfer;
    }
}
