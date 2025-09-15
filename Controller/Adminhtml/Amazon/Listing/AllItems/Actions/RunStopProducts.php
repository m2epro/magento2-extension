<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AllItems\Actions;

class RunStopProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
{
    public function execute()
    {
        return $this->scheduleAction(\Ess\M2ePro\Model\Listing\Product::ACTION_STOP);
    }
}
