<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\PickupStore
{
    //########################################

    public function execute()
    {
        $listing = $this->initListing();

        if ($this->isAjax()) {
            $this->setAjaxContent($this->createBlock('Ebay\Listing\PickupStore\Grid'));
            return $this->getResult();
        }

        $this->addContent($this->createBlock('Ebay\Listing\PickupStore'));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('In-Store Pickup "%s%"', $listing->getTitle())
        );
        return $this->getResult();
    }

    //########################################
}