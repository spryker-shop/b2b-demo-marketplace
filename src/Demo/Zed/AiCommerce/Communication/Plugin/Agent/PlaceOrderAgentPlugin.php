<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Communication\Plugin\Agent;

use Exception;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer;
use Generated\Shared\Transfer\PlaceOrderAgentResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface;

/**
 * @method \SprykerFeature\Zed\AiCommerce\Communication\AiCommerceCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\AiCommerce\Business\AiCommerceFacadeInterface getFacade()
 * @method \Pyz\Zed\AiCommerce\AiCommerceConfig getConfig()
 */
class PlaceOrderAgentPlugin extends AbstractPlugin implements BackofficeAssistantAgentPluginInterface
{
    use LoggerTrait;

    protected const string NAME = 'Place Order';

    /**
     * @uses \Demo\Zed\AiCommerce\Communication\Plugin\AiFoundation\PlaceOrderToolSetPlugin::TOOL_SET_PLACE_ORDER
     */
    protected const string TOOL_SET_PLACE_ORDER = 'place_order_tools';

    /**
     * @uses \SprykerFeature\Zed\AiCommerce\Communication\Plugin\AiFoundation\OrderDetailsToolSetPlugin::TOOL_SET_ORDER_DETAILS
     */
    protected const string TOOL_SET_ORDER_DETAILS = 'order_details_tools';

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return 'Manages the complete order creation workflow including cart initialization, product management, customer and address configuration, shipping method selection, and payment setup. Use when customers need to create or process new orders through the cart system.';
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     *
     * @param \Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest
     *
     * @return bool
     */
    public function isApplicable(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): bool {
        /**
         * @var \Demo\Zed\AiCommerce\AiCommerceConfig $config
         */
        $config = $this->getConfig();

        return $config->isPlaceOrderAgentEnabled();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest
     *
     * @return \Generated\Shared\Transfer\BackofficeAssistantPromptResponseTransfer
     */
    public function executeAgent(
        BackofficeAssistantPromptRequestTransfer $backofficeAssistantPromptRequest,
    ): BackofficeAssistantPromptResponseTransfer {
        /** @var \Demo\Zed\AiCommerce\AiCommerceConfig $config */
        $config = $this->getConfig();

        $promptRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName($config->getPlaceOrderAgentAiConfigurationName())
            ->setConversationReference($backofficeAssistantPromptRequest->getConversationReference())
            ->setStructuredMessage(new PlaceOrderAgentResponseTransfer())
            ->addToolSetName(static::TOOL_SET_PLACE_ORDER)
            ->addToolSetName(static::TOOL_SET_ORDER_DETAILS)
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($backofficeAssistantPromptRequest->getPrompt())
                    ->setAttachments($backofficeAssistantPromptRequest->getAttachments()),
            );

        $backofficeAssistantPromptResponse = new BackofficeAssistantPromptResponseTransfer();

        try {
            $promptResponse = $this->getFactory()->getAiFoundationFacade()->prompt($promptRequest);
        } catch (Exception $e) {
            $this->getLogger()->error(sprintf('PlaceOrderAgent prompt failed: %s', $e->getMessage()), $e->getTrace());

            return $backofficeAssistantPromptResponse;
        }

        if (!$promptResponse->getIsSuccessful()) {
            $this->getLogger()->error(sprintf(
                'PlaceOrderAgent prompt response is not successful: %s',
                implode(', ', array_map(static fn ($error) => $error->getMessage(), $promptResponse->getErrors()->getArrayCopy())),
            ));

            return $backofficeAssistantPromptResponse;
        }

        /** @var \Generated\Shared\Transfer\PlaceOrderAgentResponseTransfer $placeOrderAgentResponse */
        $placeOrderAgentResponse = $promptResponse->getStructuredMessage();

        $backofficeAssistantPromptResponse->setAgent($placeOrderAgentResponse->getAgent());
        $backofficeAssistantPromptResponse->setMessage($placeOrderAgentResponse->getMessage());
        $backofficeAssistantPromptResponse->setReasoningMessage($placeOrderAgentResponse->getReasoningMessage());

        return $backofficeAssistantPromptResponse;
    }
}
