<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Go\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\Process\Process;

/**
 * @method \Go\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Go\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Go\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantDataImportOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    protected const DATA_IMPORT_CONFIG_PATH = 'data/import/tenant/tenant_import_config_EU.yml';
    protected const DATA_IMPORT_FULL_CONFIG_PATH = 'data/import/local/full_EU.yml';
    protected const COMMAND_TIMEOUT = 300; // 5 minutes

    /**
     * Specification:
     * - Executes data import console command for the tenant
     * - Sets the SPRYKER_TENANT_IDENTIFIER environment variable to the tenant name
     * - Runs data:import command with tenant-specific configuration
     * - Returns success/failure based on command execution
     *
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantOnboardingStepResultTransfer
     */
    public function execute(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantOnboardingStepResultTransfer
    {
        $result = new TenantOnboardingStepResultTransfer();
        $result->setIsSuccessful(false)
            ->setTenantRegistration($tenantRegistrationTransfer);

        $tenantIdentifier = $tenantRegistrationTransfer->getTenantName();

        $commands = [
            $this->buildSetupCommand(),
            $this->buildStoreDataImportCommand($tenantRegistrationTransfer),
            $this->buildSetupESCommand(),
            $this->buildDataImportCommand($tenantRegistrationTransfer),
            $this->buildProductLabelCommand(),
        ];

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->setTimeout(static::COMMAND_TIMEOUT);

            $env = $process->getEnv();
            $env['SPRYKER_TENANT_IDENTIFIER'] = $tenantIdentifier;
            $process->setEnv($env);

            $process->mustRun();
        }

        if ($process->isSuccessful()) {
            $result->setIsSuccessful(true);
            $result->addContextItem('tenant_identifier: ' . $tenantIdentifier);
            $result->addContextItem('command_output: ' . $process->getOutput());
            $result->addContextItem('message: ' . 'Tenant data import completed successfully');
        } else {
            $result->addError('Data import command failed: ' . $process->getErrorOutput());
            $result->addContextItem('command_output: ' .  $process->getOutput());
            $result->addContextItem('error_output: ' . $process->getErrorOutput());
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'TenantDataImport';
    }

    /**
     * @return array<string>
     */
    protected function buildDataImportCommand(TenantRegistrationTransfer $tenantRegistrationTransfer): array
    {
        if ($tenantRegistrationTransfer->getDataSetOrFail() === 'full') {
            return [
                'vendor/bin/console',
                'data:import',
                '--config=' . static::DATA_IMPORT_FULL_CONFIG_PATH,
            ];
        }

        return [
            'vendor/bin/console',
            'data:import',
            '--config=' . static::DATA_IMPORT_CONFIG_PATH,
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildStoreDataImportCommand(TenantRegistrationTransfer $tenantRegistrationTransfer): array
    {
        if ($tenantRegistrationTransfer->getDataSetOrFail() === 'full') {
            return [
                'vendor/bin/console',
                'data:import',
                'store',
                '--config=' . static::DATA_IMPORT_FULL_CONFIG_PATH,
            ];
        }

        return [
            'vendor/bin/console',
            'data:import',
            'store',
            '--config=' . static::DATA_IMPORT_CONFIG_PATH,
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildSetupCommand(): array
    {
        return [
            'vendor/bin/console',
            'setup:init-db',
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildSetupESCommand(): array
    {
        return [
            'vendor/bin/console',
            'search:setup:sources',
        ];
    }

    /**
     * @return array<string>
     */
    protected function buildProductLabelCommand(): array
    {
        return [
            'vendor/bin/console',
            'product-label:relations:update',
        ];
    }
}
