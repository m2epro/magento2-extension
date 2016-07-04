<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    //########################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')->getResource()->markAsInProcessing(
            $params['pickup_store_state_ids']
        );
    }

    protected function eventAfter()
    {
        parent::eventAfter();

        $params = $this->getParams();

        $this->activeRecordFactory->getObject('Ebay\Account\PickupStore\State')->getResource()->unmarkAsInProcessing(
            $params['pickup_store_state_ids']
        );
    }

    //########################################
}