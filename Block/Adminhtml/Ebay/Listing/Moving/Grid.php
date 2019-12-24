<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Moving;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Moving\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Moving\Grid
{
    //########################################

    protected function getNewListingUrl()
    {
        $newListingUrl = $this->getUrl('*/ebay_listing_create/index', [
            'step' => 1,
            'clear' => 1,
            'account_id' => $this->getHelper('Data\GlobalData')->getValue('accountId'),
            'marketplace_id' => $this->getHelper('Data\GlobalData')->getValue('marketplaceId'),
            'creation_mode' => \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY
        ]);

        return $newListingUrl;
    }

    //########################################
}
