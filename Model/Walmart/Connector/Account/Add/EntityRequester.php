<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Account\Add;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Account\Add\EntityRequester
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
        /** @var $marketplaceObject \Ess\M2ePro\Model\Marketplace */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $this->params['marketplace_id']
        );

        if ($this->params['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA) {
            $requestData = [
                'title'          => $this->account->getTitle(),
                'consumer_id'    => $this->params['consumer_id'],
                'private_key'    => $this->params['private_key'],
                'marketplace_id' => $marketplaceObject->getNativeId(),
            ];
        } else {
            $requestData = [
                'title'          => $this->account->getTitle(),
                'client_id'      => $this->params['client_id'],
                'client_secret'  => $this->params['client_secret'],
                'marketplace_id' => $marketplaceObject->getNativeId(),
            ];
        }

        return $requestData;
    }

    /**
     * @return array
     */
    protected function getCommand()
    {
        return ['account', 'add', 'entity'];
    }

    //########################################

    /**
     * @return bool
     */
    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if ((empty($responseData['hash']) || !isset($responseData['info'])) &&
            !$this->getResponse()->getMessages()->hasErrorEntities()
        ) {
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
