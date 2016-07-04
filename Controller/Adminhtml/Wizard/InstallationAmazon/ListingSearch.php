<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class ListingSearch extends InstallationAmazon
{
     public function execute()
     {
         return $this->_redirect('*/amazon_listing_create', array('step' => 3, 'wizard' => true));
     }
}