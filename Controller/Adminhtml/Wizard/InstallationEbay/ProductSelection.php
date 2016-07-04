<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class ProductSelection extends InstallationEbay
{
     public function execute()
     {
         $listingId = $this->ebayFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

         $productAddSessionData = $this->getHelper('Data\Session')->getValue('ebay_listing_product_add');
         $source = isset($productAddSessionData['source']) ? $productAddSessionData['source'] : NULL;

         $this->getHelper('Data\Session')->setValue('ebay_listing_product_add', $productAddSessionData);
         return $this->_redirect(
             '*/ebay_listing_product_add/index',
             array(
                 'clear' => true,
                 'step'  => 1,
                 'wizard' => true,
                 'id' => $listingId,
                 'listing_creation' => true,
                 'source' => $source
             )
         );
     }
}