<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\ProcessingListSku
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\ProcessingListSku getResource()
 */
class ProcessingListSku extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\ProcessingListSku');
    }

    //####################################

    /**
     * @return int
     */
    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return (string)$this->getData('sku');
    }

    //####################################
}
