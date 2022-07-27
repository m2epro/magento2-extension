<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\HealthStatus;

class Index extends \Ess\M2ePro\Controller\Adminhtml\HealthStatus
{
    public function execute()
    {
        $activeTab = $this->getRequest()->getParam('active_tab', null);
        $activeTab === null && $activeTab = \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs::TAB_ID_DASHBOARD;

        /** @var \Ess\M2ePro\Model\HealthStatus\Manager $healthManager */
        $healthManager = $this->modelFactory->getObject('HealthStatus\Manager');
        $resultSet = $healthManager->doCheck();

        /** @var \Ess\M2ePro\Model\HealthStatus\CurrentStatus $currentStatus */
        $currentStatus = $this->modelFactory->getObject('HealthStatus\CurrentStatus');
        $currentStatus->set($resultSet);

        /** @var \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs $tabsBlock */
        $tabsBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\HealthStatus\Tabs::class,
            '',
            [
                'resultSet' => $resultSet,
                'data'      => [
                    'active_tab' => $activeTab
                ]
            ]
        );

        if ($this->isAjax()) {
            $this->setAjaxContent(
                $tabsBlock->getTabContent($tabsBlock->getActiveTabById($activeTab))
            );

            return $this->getResult();
        }

        $this->addLeft($tabsBlock);
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HealthStatus::class));

        $this->setPageHelpLink('x/2gY3B');

        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Help Center'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Health Status'));

        return $this->getResult();
    }
}
