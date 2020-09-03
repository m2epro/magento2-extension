<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\MigrationFromMagento1\Finish
 */
class Finish extends Base
{
    //########################################

    public function execute()
    {
        $this->helperFactory->getObject('Module')->getConfig()->setGroupValue(
            '/cron/',
            'mode',
            $this->getRequest()->getParam('enable_synchronization') ? 1 : 0
        );

        if ($components = $this->getHelper('Component')->getEnabledComponents()) {
            $component = reset($components);
            return $this->_redirect($this->getUrl("*/{$component}_listing/index"));
        } else {
            return $this->_redirect($this->getHelper('Module\Support')->getPageRoute());
        }
    }

    //########################################
}
