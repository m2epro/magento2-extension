<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Support;

use Ess\M2ePro\Controller\Adminhtml\Support;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Support\TestMemoryLimitResult
 */
class TestMemoryLimitResult extends Support
{
    //########################################

    public function execute()
    {
        $this->setJsonContent(
            ['result' => (int)($this->getHelper('Client')->getTestedMemoryLimit() / 1024 / 1024)]
        );
        return $this->getResult();
    }

    //########################################
}
