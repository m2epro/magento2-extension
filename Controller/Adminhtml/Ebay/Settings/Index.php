<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    protected function getLayoutType(): string
    {
        return self::LAYOUT_TWO_COLUMNS;
    }

    public function execute()
    {
        $activeTab = $this->getRequest()->getParam('active_tab', null);

        if ($activeTab === null) {
            $activeTab = \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_GENERAL;
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::class,
            '',
            [
                'data' => [
                    'active_tab' => $activeTab,
                ],
            ]
        );

        if ($this->isAjax()) {
            $this->setAjaxContent(
                $tabsBlock->getTabContent($tabsBlock->getActiveTabById($activeTab))
            );

            return $this->getResult();
        }

        $this->addLeft($tabsBlock);
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings::class));

        $this->setPageHelpLink('ebay-global-set-up');

        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Settings'));

        return $this->getResult();
    }
}
