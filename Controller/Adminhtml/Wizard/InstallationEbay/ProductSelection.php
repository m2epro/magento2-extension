<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\ProductSelection
 */
class ProductSelection extends InstallationEbay
{
    public function execute()
    {
        $listingId = $this->ebayFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

        $productAddSessionData = $this->getHelper('Data\Session')->getValue('ebay_listing_product_add');
        $source = isset($productAddSessionData['source']) ? $productAddSessionData['source'] : null;

        $this->getHelper('Data\Session')->setValue('ebay_listing_product_add', $productAddSessionData);
        return $this->_redirect(
            '*/ebay_listing_product_add/index',
            [
                'clear' => true,
                'step'  => 1,
                'wizard' => true,
                'id' => $listingId,
                'listing_creation' => true,
                'source' => $source
            ]
        );
    }
}
