<?php

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;
use Ess\M2ePro\Model\Cron\Runner\Service;
use Magento\Framework\App\State;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var Service $serviceCronRunner */
    private $serviceCronRunner = NULL;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $config;

    //########################################

    public function __construct(
        Context $context,
        Service $serviceCronRunner,
        \Magento\PageCache\Model\Config $config
    )
    {
        parent::__construct($context);
        $this->serviceCronRunner = $serviceCronRunner;
        $this->config = $config;
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

        // Magento is going to set a special cookie for Varnish to prevent caching of POST requests.
        // An error "Headers already sent" will be thrown as we've already closed connection with server.
        if ($this->config->getType() == \Magento\PageCache\Model\Config::VARNISH && $this->config->isEnabled()) {
            die;
        }
    }

    //########################################

    private function closeConnection()
    {
        @ob_end_clean();
        ob_start();

        ignore_user_abort(true);

        $this->getResponse()->setContent('processing...');
        $this->getResponse()->sendContent();

        header('Connection: Close');
        header('Content-Length: '.ob_get_length());

        while (ob_get_level()) {
            if (!$result = @ob_end_flush()) {
                break;
            }
        }

        flush();
    }

    //########################################
}