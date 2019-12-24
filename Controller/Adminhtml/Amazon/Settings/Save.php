<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Settings\Save
 */
class Save extends Settings
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/amazon/business/',
            'mode',
            (int)$this->getRequest()->getParam('business_mode')
        );

        $this->setJsonContent([
            'success' => true
        ]);
        return $this->getResult();
    }

    //########################################
}
