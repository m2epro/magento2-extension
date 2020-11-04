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
class EntityRequester extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Requester
{
    //########################################

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

    protected function getCommand()
    {
        return ['account', 'update', 'entity'];
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart_Connector_Account_Update_ProcessingRunner';
    }

    //########################################
}
