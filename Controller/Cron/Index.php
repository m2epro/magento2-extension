<?php

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;
use Ess\M2ePro\Model\Cron\Runner\Service;
use Magento\Framework\App\State;

class Index extends \Magento\Framework\App\Action\Action
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
        $this->closeConnection();

        $authKey = $this->getRequest()->getParam('auth_key', false);
        $authKey && $this->serviceCronRunner->setRequestAuthKey($authKey);

        $connectionId = $this->getRequest()->getParam('connection_id', false);
        $connectionId && $this->serviceCronRunner->setRequestConnectionId($connectionId);

        $this->serviceCronRunner->process();

        exit();
    }

    //########################################

    private function closeConnection()
    {
        @ob_end_clean();
        ob_start();

        ignore_user_abort(true);
        echo 'processing...';

        header('Connection: Close');
        header('Content-Length: '.ob_get_length());

        while (ob_get_level()) {
            if (!$result = @ob_end_flush()) {
                break;
            }
        }

        @flush();
    }

    //########################################
}