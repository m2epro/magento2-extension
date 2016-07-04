<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class ListingGeneral extends InstallationEbay
{
     public function execute()
     {
         return $this->_redirect('*/ebay_listing_create',array('step' => 2, 'wizard' => true));
     }
}