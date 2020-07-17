<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Support;

use Ess\M2ePro\Controller\Adminhtml\Support;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Support\TestExecutionTimeResult
 */
class TestExecutionTimeResult extends Support
{
    //########################################

    public function execute()
    {
        $this->setJsonContent(['result' => $this->getHelper('Client')->getTestedExecutionTime()]);
        return $this->getResult();
    }

    //########################################
}
