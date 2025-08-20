<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Communication\Controller;

use Generated\Shared\Transfer\ShopConfigurationSaveRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Pyz\Zed\ShopConfiguration\Business\ShopConfigurationFacadeInterface getFacade()
 * @method \Pyz\Zed\ShopConfiguration\Communication\ShopConfigurationCommunicationFactory getFactory()
 */
class IndexController extends AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function indexAction(Request $request): array
    {
        $store = $request->query->get('store', 'DE');
        $locale = $request->query->get('locale');

        $defaultConfiguration = $this->getFacade()->getDefaultConfiguration();
        $effectiveConfiguration = $this->getFacade()->getEffectiveConfiguration($store, $locale);

        return [
            'defaultConfiguration' => $defaultConfiguration,
            'effectiveConfiguration' => $effectiveConfiguration,
            'currentStore' => $store,
            'currentLocale' => $locale,
            'availableStores' => $this->getAvailableStores(),
            'availableLocales' => $this->getAvailableLocales(),
        ];
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveAction(Request $request): JsonResponse
    {
        $store = $request->request->get('store', 'DE');
        $locale = $request->request->get('locale');
        $values = $request->request->all('values') ?: [];

        $saveRequestTransfer = (new ShopConfigurationSaveRequestTransfer())
            ->setStore($store)
            ->setLocale($locale)
            ->setValues($values);

        try {
            $this->getFacade()->saveAndPublishConfiguration($saveRequestTransfer);

            return new JsonResponse([
                'success' => true,
                'message' => 'Configuration saved and published successfully',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to save configuration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function publishAction(Request $request): JsonResponse
    {
        $store = $request->request->get('store', 'DE');
        $locale = $request->request->get('locale');

        try {
            $this->getFacade()->publishConfiguration($store, $locale);

            return new JsonResponse([
                'success' => true,
                'message' => 'Configuration published successfully',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to publish configuration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function rebuildAction(Request $request): JsonResponse
    {
        try {
            $this->getFacade()->rebuildConfigurationFromFiles();

            return new JsonResponse([
                'success' => true,
                'message' => 'Configuration rebuilt from files successfully',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to rebuild configuration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array<string>
     */
    protected function getAvailableStores(): array
    {
        // In a real implementation, this would come from Store facade
        return ['DE', 'US', 'AT'];
    }

    /**
     * @return array<string>
     */
    protected function getAvailableLocales(): array
    {
        // In a real implementation, this would come from Locale facade
        return ['de_DE', 'en_US', 'en_GB'];
    }
}
