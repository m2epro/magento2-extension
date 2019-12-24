<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
{
    //########################################

    public function execute()
    {
        $listing = $this->initListing();

        if ($this->isAjax()) {
            $this->setAjaxContent($this->createBlock('Ebay_Listing_PickupStore_Grid'));
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay_Listing_PickupStore'));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('In-Store Pickup "%s%"', $listing->getTitle())
        );
        return $this->getResult();
    }

    //########################################
}
