<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class NewAsin extends InstallationAmazon
{
     public function execute()
     {
         $listingId = $this->amazonFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

         $productAddSessionData = $this->getHelper('Data\Session')->getValue('amazon_listing_product_add');
         $source = isset($productAddSessionData['source']) ? $productAddSessionData['source'] : NULL;

         $this->getHelper('Data\Session')->setValue('amazon_listing_product_add', $productAddSessionData);
         return $this->_redirect(
             '*/amazon_listing_product_add/index',
             array(
                 'step' => 2,
                 'source' => $source,
                 'id' => $listingId,
                 'new_listing' => true,
                 'wizard' => true,
             )
         );
     }
}