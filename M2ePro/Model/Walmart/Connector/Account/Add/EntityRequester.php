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
class EntityRequester extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Requester
{
    //########################################

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
                'consumer_id'    => $this->params['consumer_id'],
                'client_id'      => $this->params['client_id'],
                'client_secret'  => $this->params['client_secret'],
                'marketplace_id' => $marketplaceObject->getNativeId(),
            ];
        }

        return $requestData;
    }

    protected function getCommand()
    {
        return ['account', 'add', 'entity'];
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart_Connector_Account_Add_ProcessingRunner';
    }

    //########################################
}
