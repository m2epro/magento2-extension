<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore\GetProductsVariations
 */
class GetProductsVariations extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
{
    //########################################

    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Variation\Product\View::class)
        );
        return $this->getResult();
    }

    //########################################
}
