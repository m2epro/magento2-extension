<?php

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;
use Ess\M2ePro\Model\Cron\Runner\Service;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var Service $serviceCronRunner */
    private $serviceCronRunner = NULL;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $config;

    /** @var \Ess\M2ePro\Helper\Factory */
    private $helperFactory;

    /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory */
    private $responseFactory;

    //########################################

    public function __construct(
        Context $context,
        Service $serviceCronRunner,
        \Magento\PageCache\Model\Config $config,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory $responseFactory
    )
    {
        parent::__construct($context);
        $this->serviceCronRunner = $serviceCronRunner;
        $this->config = $config;
        $this->helperFactory = $helperFactory;
        $this->responseFactory = $responseFactory;
    }

    //########################################

    public function execute()
    {
        $this->initErrorHandler();
        if ($connectionClosed = $this->isConnectionMustBeClosed()) {
            $this->closeConnection();
        }

        $authKey = $this->getRequest()->getParam('auth_key', false);
        $authKey && $this->serviceCronRunner->setRequestAuthKey($authKey);

        $connectionId = $this->getRequest()->getParam('connection_id', false);
        $connectionId && $this->serviceCronRunner->setRequestConnectionId($connectionId);

        $this->serviceCronRunner->process();

        /*
         * Magento is going to set a special cookie for Caching Systems to mark page content.
         *
         * http://devdocs.magento.com/guides/v2.0/config-guide/cache/cache-priv-context.html
         * vendor\magento\module-page-cache\Model\App\Response\HttpPlugin.php
         */
        $response = $this->responseFactory->create();
        !$connectionClosed && $response->setContent('processing...');

        return $response;
    }

    //########################################

    private function initErrorHandler()
    {
        $handler = new Service\ErrorHandler(
            $this->helperFactory->getObject('Module\Cron\Service')
        );
        set_error_handler([$handler, 'handler']);
    }

    //########################################

    private function isConnectionMustBeClosed()
    {
        return $this->helperFactory->getObject('Module\Cron\Service')->isConnectionMustBeClosed();
    }

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