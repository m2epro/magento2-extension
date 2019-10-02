<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore;

/**
 * Class GetProductsVariationsGrid
 * @package Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
 */
class GetProductsVariationsGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent($this->createBlock('Ebay_Listing_PickupStore_Variation_Product_View_Grid'));
        return $this->getResult();
    }

    //########################################
}
