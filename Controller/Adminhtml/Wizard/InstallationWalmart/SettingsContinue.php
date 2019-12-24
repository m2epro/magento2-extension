<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\SettingsContinue
 */
class SettingsContinue extends InstallationWalmart
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        $this->getHelper('Component_Walmart_Configuration')
            ->setConfigValues($params);

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }
}
