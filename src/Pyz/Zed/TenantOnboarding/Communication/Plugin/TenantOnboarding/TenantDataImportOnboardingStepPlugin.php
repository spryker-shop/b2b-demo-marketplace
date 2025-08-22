<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Communication\Plugin\TenantOnboarding;

use Generated\Shared\Transfer\TenantOnboardingStepResultTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Pyz\Zed\TenantOnboarding\Business\Plugin\OnboardingStepPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\Process\Process;

/**
 * @method \Pyz\Zed\TenantOnboarding\TenantOnboardingConfig getConfig()
 * @method \Pyz\Zed\TenantOnboarding\Business\TenantOnboardingFacadeInterface getFacade()
 * @method \Pyz\Zed\TenantOnboarding\Communication\TenantOnboardingCommunicationFactory getFactory()
 */
class TenantDataImportOnboardingStepPlugin extends AbstractPlugin implements OnboardingStepPluginInterface
{
    protected const DATA_IMPORT_CONFIG_PATH = 'data/import/tenant/tenant_import_config_EU.yml';
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
        $result->setIsSuccessful(false);

        try {
            $tenantIdentifier = $tenantRegistrationTransfer->getTenantName();
            $command = $this->buildDataImportCommand($tenantIdentifier);
            
            $process = new Process($command);
            $process->setTimeout(static::COMMAND_TIMEOUT);
            
            // Set environment variables
            $env = $process->getEnv();
            $env['SPRYKER_TENANT_IDENTIFIER'] = $tenantIdentifier;
            $process->setEnv($env);
            
            // Execute the command
            $process->run();
            
            if ($process->isSuccessful()) {
                $result->setIsSuccessful(true);
                $result->addContextItem('tenant_identifier', $tenantIdentifier);
                $result->addContextItem('command_output', $process->getOutput());
                $result->addContextItem('message', 'Tenant data import completed successfully');
            } else {
                $result->addError('Data import command failed: ' . $process->getErrorOutput());
                $result->addContextItem('command_output', $process->getOutput());
                $result->addContextItem('error_output', $process->getErrorOutput());
            }
            
        } catch (\Exception $e) {
            $result->setIsSuccessful(false);
            $result->addError('Failed to execute tenant data import: ' . $e->getMessage());
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
     * @param string $tenantIdentifier
     *
     * @return array<string>
     */
    protected function buildDataImportCommand(string $tenantIdentifier): array
    {
        return [
            'vendor/bin/console',
            'data:import',
            '--config=' . static::DATA_IMPORT_CONFIG_PATH,
        ];
    }
}