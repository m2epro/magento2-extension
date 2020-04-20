<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\NewAsin
 */
class NewAsin extends InstallationAmazon
{
    public function execute()
    {
        $listingId = $this->amazonFactory->getObject('Listing')->getCollection()->getLastItem()->getId();

        $productAddSessionData = $this->getHelper('Data\Session')->getValue('amazon_listing_product_add');
        $source = isset($productAddSessionData['source']) ? $productAddSessionData['source'] : null;

        $this->getHelper('Data\Session')->setValue('amazon_listing_product_add', $productAddSessionData);
        return $this->_redirect(
            '*/amazon_listing_product_add/index',
            [
                'step' => 2,
                'source' => $source,
                'id' => $listingId,
                'new_listing' => true,
                'wizard' => true,
            ]
        );
    }
}
