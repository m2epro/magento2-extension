<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class ListingSelling extends InstallationAmazon
{
     public function execute()
     {
         return $this->_redirect('*/amazon_listing_create', array('step' => 2, 'wizard' => true));
     }
}