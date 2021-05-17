<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Account\Update;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Account\Update\EntityRequester
 */
class EntityRequester extends \Ess\M2ePro\Model\Walmart\Connector\Command\RealTime
{
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        $account,
        array $params
    ) {
        parent::__construct($helperFactory, $modelFactory, $account, $params);

        $this->walmartFactory = $walmartFactory;
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        /** @var \Ess\M2ePro\Model\Marketplace $marketplaceObject */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $this->params['marketplace_id']
        );

        $this->params['marketplace_id'] = $marketplaceObject->getNativeId();

        return $this->params;
    }

    /**
     * @return array
     */
    protected function getCommand()
    {
        return ['account', 'update', 'entity'];
    }

    //########################################

    /**
     * @return bool
     */
    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['info']) && !$this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function processResponseData()
    {
        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            throw new \Exception($message->getText());
        }

        $this->responseData = $this->getResponse()->getResponseData();
    }

    //########################################
}
