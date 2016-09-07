<?php

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\Controller\ResultFactory;

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