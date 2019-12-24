<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;

/**
 * Class \Ess\M2ePro\Controller\Cron\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Ess\M2ePro\Model\Cron\Runner\Service\Controller */
    private $cronRunner;

    /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory */
    private $responseFactory;

    //########################################

    public function __construct(
        Context $context,
        \Ess\M2ePro\Model\Cron\Runner\Service\Controller $cronRunner,
        \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory $responseFactory
    ) {
        parent::__construct($context);
        $this->cronRunner = $cronRunner;
        $this->responseFactory = $responseFactory;
    }

    //########################################

    public function execute()
    {
        $authKey = $this->getRequest()->getParam('auth_key', false);
        $authKey && $this->cronRunner->setRequestAuthKey($authKey);

        $connectionId = $this->getRequest()->getParam('connection_id', false);
        $connectionId && $this->cronRunner->setRequestConnectionId($connectionId);

        $this->cronRunner->process();

        /*
         * Magento is going to set a special cookie for Caching Systems to mark page content.
         *
         * http://devdocs.magento.com/guides/v2.0/config-guide/cache/cache-priv-context.html
         * vendor\magento\module-page-cache\Model\App\Response\HttpPlugin.php
         */
        $response = $this->responseFactory->create();
        $response->setContent('processing...');

        return $response;
    }

    //########################################
}
