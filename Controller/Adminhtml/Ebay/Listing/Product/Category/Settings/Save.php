<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class Save extends Settings
{

    //########################################

    public function execute()
    {
        $this->save($this->getSessionValue($this->getSessionDataKey()));

        return $this->getResult();
    }

    //########################################
}