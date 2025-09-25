<?php

namespace Go\Glue\DocumentationGeneratorApi\Plugin\Console;

use Generated\Shared\Transfer\DocumentationInvalidationVoterRequestTransfer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiGenerateDocumentationConsole extends \Spryker\Glue\DocumentationGeneratorApi\Plugin\Console\ApiGenerateDocumentationConsole
{
    use \Go\Glue\TenantBehavior\Console\TenantIterationTrait;
    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $input->getOption('application');
        $applicationOptionValue = $application && is_string($application) ? [$application] : [];
        $invalidateAfterInterval = $input->getOption(static::OPTION_INVALIDATED_AFTER_INTERVAL);

        $this->forEachTenant(function () use ($invalidateAfterInterval, $output, $applicationOptionValue, $application): void {
            if ($invalidateAfterInterval && is_string($invalidateAfterInterval)) {
                $documentationInvalidationVoterRequestTransfer = (new DocumentationInvalidationVoterRequestTransfer())->setInterval($invalidateAfterInterval);

                $isInvalidated = $this->getFactory()->createInvalidationVerifier()->isInvalidated($documentationInvalidationVoterRequestTransfer, $application);

                if (!$isInvalidated) {
                    $output->writeln(static::SKIP_MESSAGE);

                    return;
                }
            }

            $this->getFactory()->createDocumentationGenerator()->generateDocumentation($applicationOptionValue);

        });
        return static::CODE_SUCCESS;
    }
}
