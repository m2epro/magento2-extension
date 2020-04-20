<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList
 */
class ProcessingList extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const STAGE_LIST_DETAILS                    = 1;
    const STAGE_RELIST_INVENTORY_READY          = 2;
    const STAGE_RELIST_INVENTORY_WAITING_RESULT = 3;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Action\ProcessingList');
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
     * @return int
     */
    public function getListingProductId()
    {
        return (int)$this->getData('listing_product_id');
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return (string)$this->getData('sku');
    }

    /**
     * @return string
     */
    public function getStage()
    {
        return (string)$this->getData('stage');
    }

    /**
     * @return string
     */
    public function getRelistRequestPendingId()
    {
        return (int)$this->getData('relist_request_pending_single_id');
    }

    /**
     * @return array
     */
    public function getRelistRequestData()
    {
        return $this->getHelper('Data')->jsonDecode($this->getData('relist_request_data'));
    }

    /**
     * @return array
     */
    public function getRelistConfiguratorData()
    {
        return $this->getHelper('Data')->jsonDecode($this->getData('relist_configurator_data'));
    }

    //####################################
}
