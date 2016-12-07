<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Debug;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;

class Debug extends Command
{
    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ){
        parent::__construct($context);
    }

    /**
     * @title "First Test"
     * @description "Command for quick development"
     */
    public function firstTestAction()
    {

    }

    /**
     * @title "Second Test"
     * @description "Command for quick development"
     */
    public function secondTestAction()
    {

    }
}