<?php

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;
use Ess\M2ePro\Model\Cron\Runner\Service;

class Reset extends \Magento\Framework\App\Action\Action
{
    /** @var Service $serviceCronRunner */
    private $serviceCronRunner = NULL;

    //########################################

    public function __construct(Context $context, Service $serviceCronRunner)
    {
        parent::__construct($context);
        $this->serviceCronRunner = $serviceCronRunner;
    }

    //########################################

    public function execute()
    {
        $this->serviceCronRunner->resetTasksStartFrom();
    }

    //########################################
}