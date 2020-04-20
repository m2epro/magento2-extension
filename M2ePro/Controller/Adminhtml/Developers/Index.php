<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Developers;

use Ess\M2ePro\Controller\Adminhtml\Developers;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Developers\Index
 */
class Index extends Developers
{
    //########################################

    protected function getLayoutType()
    {
        return self::LAYOUT_TWO_COLUMNS;
    }

    //########################################

    public function execute()
    {
        $activeTab = $this->getRequest()->getParam('active_tab', null);

        if ($activeTab === null) {
            $activeTab = \Ess\M2ePro\Block\Adminhtml\Developers\Tabs::TAB_ID_INSTALLATION_DETAILS;
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Developers\Tabs $tabsBlock */
        $tabsBlock = $this->createBlock('Developers\Tabs', '', ['data' => [
            'active_tab' => $activeTab
        ]]);

        if ($this->isAjax()) {
            $this->setAjaxContent(
                $tabsBlock->getTabContent($tabsBlock->getActiveTabById($activeTab))
            );

            return $this->getResult();
        }

        $this->addLeft($tabsBlock);
        $this->addContent($this->createBlock('Developers'));

        $referrer = $this->getRequest()->getParam('referrer', false);

        if ($referrer === 'ebay') {
            $this->setPageHelpLink('x/sglPAQ');
        } elseif ($referrer === 'walmart') {
            $this->setPageHelpLink('x/qIFwAQ');
        } else {
            $this->setPageHelpLink('x/nxBPAQ');
        }

        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Help Center'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Developers / Administrators Area'));

        return $this->getResult();
    }

    //########################################
}
