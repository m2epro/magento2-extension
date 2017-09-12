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

    /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory */
    private $responseFactory;

    //########################################

    public function __construct(
        Context $context,
        Service $serviceCronRunner,
        \Magento\PageCache\Model\Config $config,
        \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory $responseFactory
    )
    {
        parent::__construct($context);
        $this->serviceCronRunner = $serviceCronRunner;
        $this->config = $config;
        $this->responseFactory = $responseFactory;
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

        /*
         * Magento is going to set a special cookie for Varnish to prevent caching of POST requests.
         * An error "Headers already sent" will be thrown as we've already closed connection with server.
         *
         * vendor\magento\module-page-cache\Model\App\FrontController\VarnishPlugin.php
         */
        if ($this->config->getType() == \Magento\PageCache\Model\Config::VARNISH && $this->config->isEnabled()) {
            die;
        }

        /*
         * Magento is going to set a special cookie for Caching Systems to mark page content.
         *
         * http://devdocs.magento.com/guides/v2.0/config-guide/cache/cache-priv-context.html
         * vendor\magento\module-page-cache\Model\App\Response\HttpPlugin.php
         */
        return $this->responseFactory->create();
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