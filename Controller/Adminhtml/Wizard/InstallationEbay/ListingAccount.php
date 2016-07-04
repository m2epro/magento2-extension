<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class ListingAccount extends InstallationEbay
{
     public function execute()
     {
         return $this->_redirect('*/ebay_listing_create',array('step' => 1,'wizard' => true,'clear' => true));
     }
}