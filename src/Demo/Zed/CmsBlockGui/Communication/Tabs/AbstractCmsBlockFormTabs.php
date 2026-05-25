<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\CmsBlockGui\Communication\Tabs;

use Generated\Shared\Transfer\TabItemTransfer;
use Generated\Shared\Transfer\TabsViewTransfer;
use Spryker\Zed\Gui\Communication\Tabs\AbstractTabs;

abstract class AbstractCmsBlockFormTabs extends AbstractTabs
{
    protected const string TAB_NAME_GENERAL = 'general';

    protected const string TAB_NAME_PERSONALIZATION = 'personalization';

    protected const string TAB_TITLE_GENERAL = 'General';

    protected const string TAB_TITLE_PERSONALIZATION = 'Personalization';

    protected const string TAB_TEMPLATE_GENERAL = '@CmsBlockGui/_partial/general-tab.twig';

    protected const string TAB_TEMPLATE_PERSONALIZATION = '@CmsBlockGui/_partial/personalization-tab.twig';

    protected const string FOOTER_TEMPLATE = '@CmsBlockGui/_partial/footer.twig';

    protected function build(TabsViewTransfer $tabsViewTransfer): TabsViewTransfer
    {
        $this->addGeneralTab($tabsViewTransfer)
            ->addPersonalizationTab($tabsViewTransfer)
            ->setFooter($tabsViewTransfer);

        return $tabsViewTransfer;
    }

    protected function addGeneralTab(TabsViewTransfer $tabsViewTransfer): static
    {
        $tabsViewTransfer->addTab((new TabItemTransfer())
            ->setName(static::TAB_NAME_GENERAL)
            ->setTitle(static::TAB_TITLE_GENERAL)
            ->setTemplate(static::TAB_TEMPLATE_GENERAL));

        return $this;
    }

    protected function addPersonalizationTab(TabsViewTransfer $tabsViewTransfer): static
    {
        $tabsViewTransfer->addTab((new TabItemTransfer())
            ->setName(static::TAB_NAME_PERSONALIZATION)
            ->setTitle(static::TAB_TITLE_PERSONALIZATION)
            ->setTemplate(static::TAB_TEMPLATE_PERSONALIZATION));

        return $this;
    }

    protected function setFooter(TabsViewTransfer $tabsViewTransfer): static
    {
        $tabsViewTransfer
            ->setFooterTemplate(static::FOOTER_TEMPLATE)
            ->setIsNavigable(true);

        return $this;
    }
}
