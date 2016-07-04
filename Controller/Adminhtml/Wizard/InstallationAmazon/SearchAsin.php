<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class SearchAsin extends InstallationAmazon
{
     public function execute()
     {
         $listingId = $this->amazonFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

         return $this->_redirect(
             '*/amazon_listing_product_add/index',
             array(
                 'step' => 3,
                 'id' => $listingId,
                 'wizard' => true,
             )
         );
     }
}