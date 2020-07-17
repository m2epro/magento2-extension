<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Support;

use Ess\M2ePro\Controller\Adminhtml\Support;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Support\TestExecutionTime
 */
class TestExecutionTime extends Support
{
    //########################################

    public function execute()
    {
        $this->getHelper('Client')->testExecutionTime((int)$this->getRequest()->getParam('seconds'));

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}
