<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\HealthStatus;

use Ess\M2ePro\Controller\Adminhtml\HealthStatus;

class Index extends HealthStatus
{
    //########################################

    public function execute()
    {
        $activeTab = $this->getRequest()->getParam('active_tab', NULL);
        is_null($activeTab) && $activeTab = \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs::TAB_ID_DASHBOARD;

        /** @var \Ess\M2ePro\Model\HealthStatus\Manager $healthManager */
        $healthManager = $this->modelFactory->getObject('HealthStatus\Manager');
        $resultSet = $healthManager->doCheck();

        /** @var \Ess\M2ePro\Model\HealthStatus\CurrentStatus $currentStatus */
        $currentStatus = $this->modelFactory->getObject('HealthStatus\CurrentStatus');
        $currentStatus->set($resultSet->getWorstState());

        /** @var \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs $tabsBlock */
        $tabsBlock = $this->createBlock('HealthStatus\Tabs', '', [
            'resultSet' => $resultSet,
            'data' => [
                'active_tab' => $activeTab
            ]
        ]);

        if ($this->isAjax()) {
            $this->setAjaxContent(
                $tabsBlock->getTabContent($tabsBlock->getActiveTabById($activeTab))
            );

            return $this->getResult();
        }

        $this->addLeft($tabsBlock);
        $this->addContent($this->createBlock('HealthStatus'));

        $referrer = $this->getRequest()->getParam('referrer', false);

        if ($referrer == 'ebay') {
            $this->setPageHelpLink('x/fwItAQ');
        } else {
            $this->setPageHelpLink('x/XAQtAQ');
        }

        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Help Center'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Health Status'));

        return $this->getResult();
    }

    //########################################
}