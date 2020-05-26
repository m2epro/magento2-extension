<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization\Save
 */
class Save extends Settings
{
    //########################################

    public function execute()
    {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/cron/task/ebay/listing/product/process_instructions/',
            'mode',
            (int)$this->getRequest()->getParam('instructions_mode')
        );

        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    //########################################
}
