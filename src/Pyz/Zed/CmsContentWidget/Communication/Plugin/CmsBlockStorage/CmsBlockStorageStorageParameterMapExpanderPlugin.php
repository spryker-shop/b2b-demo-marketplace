<?php

namespace Pyz\Zed\CmsContentWidget\Communication\Plugin\CmsBlockStorage;

use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\SpyCmsBlockEntityTransfer;
use Spryker\Shared\CmsContentWidget\CmsContentWidgetConfig;
use Spryker\Zed\CmsBlockStorage\Dependency\Plugin\CmsBlockStorageDataExpanderPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\CmsContentWidget\Business\CmsContentWidgetFacadeInterface getFacade()
 * @method \Spryker\Zed\CmsContentWidget\Communication\CmsContentWidgetCommunicationFactory getFactory()
 * @method \Spryker\Zed\CmsContentWidget\CmsContentWidgetConfig getConfig()
 */
class CmsBlockStorageStorageParameterMapExpanderPlugin extends AbstractPlugin implements CmsBlockStorageDataExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array $collectedData
     * @param string $localeName
     *
     * @return array
     */
    public function expand(array $collectedData, $localeName)
    {
        $placeholders = $this->gePlaceholders($collectedData);

        $cmsBlockData = [
            'placeholders' => $placeholders,
        ];
        $localeTransfer = (new LocaleTransfer())->setLocaleName($localeName);
        $data = $this->getFacade()->expandCmsBlockCollectorData($cmsBlockData, $localeTransfer);
        $collectedData[CmsContentWidgetConfig::CMS_CONTENT_WIDGET_PARAMETER_MAP] = $data[CmsContentWidgetConfig::CMS_CONTENT_WIDGET_PARAMETER_MAP];

        return $collectedData;
    }

    /**
     * @param array $collectedData
     *
     * @return array<string>
     */
    protected function gePlaceholders(array $collectedData)
    {
        $placeholders = [];
        $spyCmsBlockEntityTransfer = (new SpyCmsBlockEntityTransfer())->fromArray($collectedData, true);
        foreach ($spyCmsBlockEntityTransfer->getSpyCmsBlockGlossaryKeyMappings() as $keyMapping) {
            $placeholders[$keyMapping->getPlaceholder()] = $keyMapping->getGlossaryKey()->getKey();
        }

        return $placeholders;
    }
}
