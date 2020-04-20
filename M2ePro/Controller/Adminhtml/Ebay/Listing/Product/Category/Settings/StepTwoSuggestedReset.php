<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoSuggestedReset
 */
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
