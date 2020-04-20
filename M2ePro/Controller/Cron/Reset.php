<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;

/**
 * Class \Ess\M2ePro\Controller\Cron\Reset
 */
class Reset extends \Magento\Framework\App\Action\Action
{
    /** @var \Ess\M2ePro\Model\Cron\Runner\Service\Controller */
    private $cronRunner;

    //########################################

    public function __construct(
        Context $context,
        \Ess\M2ePro\Model\Cron\Runner\Service\Controller $cronRunner
    ) {
        parent::__construct($context);
        $this->cronRunner = $cronRunner;
    }

    //########################################

    public function execute()
    {
        $this->cronRunner->resetTasksStartFrom();
    }

    //########################################
}
