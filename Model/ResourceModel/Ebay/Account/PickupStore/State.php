<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Account\PickupStore\State
 */
class State extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_account_pickup_store_state', 'id');
    }

    //########################################

    public function markAsInProcessing(array $itemIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'is_in_processing' => 1,
            ],
            ['id IN (?)' => $itemIds]
        );
    }

    public function unmarkAsInProcessing(array $itemIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'is_in_processing' => 0,
            ],
            ['id IN (?)' => $itemIds]
        );
    }

    //########################################
}
