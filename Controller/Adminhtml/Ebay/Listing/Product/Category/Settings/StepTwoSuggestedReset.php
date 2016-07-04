<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

class StepTwoSuggestedReset extends Settings
{

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $listingProductIds = $this->getRequestIds('products_id');
        // ---------------------------------------

        $this->initSessionData($listingProductIds, true);

        return $this->getResult();
    }

    //########################################
}