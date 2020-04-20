<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Action\ProcessingListSku
 */
class ProcessingListSku extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product_action_processing_list_sku', 'id');
    }

    //########################################
}
