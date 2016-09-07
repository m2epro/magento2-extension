<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Account\PickupStore;

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
            array(
                'is_in_processing' => 1,
            ),
            array('id IN (?)' => $itemIds)
        );
    }

    public function unmarkAsInProcessing(array $itemIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array(
                'is_in_processing' => 0,
            ),
            array('id IN (?)' => $itemIds)
        );
    }

    //########################################
}