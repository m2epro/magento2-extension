<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    //########################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_State')->getResource()->markAsInProcessing(
            $params['pickup_store_state_ids']
        );
    }

    protected function eventAfter()
    {
        parent::eventAfter();

        $params = $this->getParams();

        $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_State')->getResource()->unmarkAsInProcessing(
            $params['pickup_store_state_ids']
        );
    }

    //########################################
}
