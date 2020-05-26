<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization\Save
 */
class Save extends Settings
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/cron/task/walmart/listing/product/process_instructions/',
            'mode',
            (int)$this->getRequest()->getParam('instructions_mode')
        );

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}
