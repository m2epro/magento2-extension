<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Cron;

use Magento\Framework\App\Action\Context;
use Ess\M2ePro\Model\Cron\Runner\Service;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var Service $serviceCronRunner */
    private $serviceCronRunner = NULL;

    /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory */
    private $responseFactory;

    //########################################

    public function __construct(
        Context $context,
        Service $serviceCronRunner,
        \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory $responseFactory
    )
    {
        parent::__construct($context);
        $this->serviceCronRunner = $serviceCronRunner;
        $this->responseFactory = $responseFactory;
    }

    //########################################

    public function execute()
    {
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
        $response->setContent('processing...');

        return $response;
    }

    //########################################
}