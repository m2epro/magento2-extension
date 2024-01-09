<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Mapping;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Mapping
{
    protected function getLayoutType()
    {
        return self::LAYOUT_TWO_COLUMNS;
    }

    public function execute()
    {
        $activeTab = $this->getRequest()->getParam('active_tab', null);

        if ($activeTab === null) {
            $activeTab = \Ess\M2ePro\Block\Adminhtml\Amazon\Mapping\Tabs::TAB_ID_SHIPPING_MAPPING;
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Mapping\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Amazon\Mapping\Tabs::class,
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
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Mapping::class));

        $this->getResult()->getConfig()->getTitle()->prepend(__('Mapping'));

        return $this->getResult();
    }
}
