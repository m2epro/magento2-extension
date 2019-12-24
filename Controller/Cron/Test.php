<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class \Ess\M2ePro\Controller\Cron\Test
 */
class Test extends \Magento\Framework\App\Action\Action
{
    //########################################

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setContents('ok');

        return $result;
    }

    //########################################
}
