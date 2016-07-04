<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class ProductSettings extends InstallationEbay
{
     public function execute()
     {
         $listingId = $this->ebayFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

         return $this->_redirect(
             '*/ebay_listing_product_add/index',
             array(
                 'step' => 2,
                 'wizard' => true,
                 'id' => $listingId,
                 'listing_creation' => true,
             )
         );
     }
}