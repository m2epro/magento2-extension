<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\CategoryStepThree
 */
class CategoryStepThree extends InstallationEbay
{
    public function execute()
    {
        $listingId = $this->ebayFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

        return $this->_redirect(
            '*/ebay_listing_product_category_settings/index',
            [
                'step' => 3,
                'wizard' => true,
                'id' => $listingId,
                'listing_creation' => true,
            ]
        );
    }
}
