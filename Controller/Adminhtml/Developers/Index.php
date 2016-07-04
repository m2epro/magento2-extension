<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Developers;

use Ess\M2ePro\Controller\Adminhtml\Developers;

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
        $activeTab = $this->getRequest()->getParam('active_tab', NULL);

        if (is_null($activeTab)) {
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

        if ($referrer == 'ebay') {
            $this->setPageHelpLink(NULL, 'pages/viewpage.action?pageId=19726975');
        } else {
            $this->setPageHelpLink(NULL, 'pages/viewpage.action?pageId=19727452');
        }

        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Help Center'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Developers / Administrators Area'));

        return $this->getResult();
    }

    //########################################
}