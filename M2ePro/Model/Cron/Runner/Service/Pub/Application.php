<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner\Service\Pub;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\Service\Pub\Application
 */
class Application implements \Magento\Framework\AppInterface
{
    const ACTION_PARAM        = 'action';
    const CONNECTION_ID_PARAM = 'connection_id';
    const AUTH_KEY_PARAM      = 'auth_key';

    const ACTION_PROCESS = 'process';
    const ACTION_TEST    = 'test';
    const ACTION_RESET   = 'reset';

    /** @var \Magento\Framework\App\State */
    private $state;

    /** @var \Magento\Framework\App\Request\Http */
    private $request;

    /** @var \Magento\Framework\App\Response\Http */
    private $response;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Magento\Framework\App\AreaList */
    private $areaList;

    /** @var \Ess\M2ePro\Helper\Factory */
    private $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;

    /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory */
    private $responseFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponseFactory $responseFactory,
        array $parameters = [],
        \Magento\Framework\App\AreaList $areaList = null
    ) {
        $this->state = $state;
        $this->request = $request;
        $this->request->setParams($parameters);
        $this->response = $response;
        $this->objectManager = $objectManager;
        $this->areaList = $areaList ? $areaList : $this->objectManager->get(\Magento\Framework\App\AreaList::class);

        $this->helperFactory   = $helperFactory;
        $this->modelFactory    = $modelFactory;
        $this->responseFactory = $responseFactory;
    }

    //########################################

    /**
     * {@inheritdoc}
     */
    public function launch()
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        $configLoader = $this->objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class);
        $this->objectManager->configure($configLoader->load(\Magento\Framework\App\Area::AREA_CRONTAB));

        $this->areaList->getArea(\Magento\Framework\App\Area::AREA_CRONTAB)->load(
            \Magento\Framework\App\Area::PART_TRANSLATE
        );

        switch ($this->request->getParam(self::ACTION_PARAM)) {
            case self::ACTION_TEST:
                return $this->test();

            case self::ACTION_RESET:
                return $this->reset();

            case self::ACTION_PROCESS:
            default:
                return $this->process();
        }
    }

    //########################################

    protected function process()
    {
        $runner = $this->modelFactory->getObject('Cron_Runner_Service_Pub');

        $authKey = $this->request->getParam(self::AUTH_KEY_PARAM, false);
        $authKey && $runner->setRequestAuthKey($authKey);

        $connectionId = $this->request->getParam(self::CONNECTION_ID_PARAM, false);
        $connectionId && $runner->setRequestConnectionId($connectionId);

        $runner->process();

        /*
         * Magento is going to set a special cookie for Caching Systems to mark page content.
         *
         * http://devdocs.magento.com/guides/v2.0/config-guide/cache/cache-priv-context.html
         * vendor\magento\module-page-cache\Model\App\Response\HttpPlugin.php
         */

        /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponse $response */
        $response = $this->responseFactory->create();
        $response->setContent('processing...');

        return $response;
    }

    protected function reset()
    {
        $runner = $this->modelFactory->getObject('Cron_Runner_Service_Pub');
        $runner->resetTasksStartFrom();

        /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponse $response */
        $response = $this->responseFactory->create();
        $response->setContent('reset');

        return $response;
    }

    protected function test()
    {
        /** @var \Ess\M2ePro\Model\Magento\Framework\Http\NotCacheableResponse $response */
        $response = $this->responseFactory->create();
        $response->setContent('ok');

        return $response;
    }

    //########################################

    /**
     * {@inheritdoc}
     */
    public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception)
    {
        $this->helperFactory->getObject('Module\Exception')->process($exception);
        return true;
    }

    //########################################
}
