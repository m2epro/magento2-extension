<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Log;

class Grid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Log
{
    //########################################

    public function execute()
    {
        $listingProductId = $this->getRequest()->getParam('listing_product_id', false);
        if ($listingProductId) {
            $listingProduct = $this->activeRecordFactory
                ->getObjectLoaded('Listing\Product', $listingProductId);

            if (!$listingProduct->getId()) {
                return;
            }
        }

        $response = $this->createBlock('Amazon\Listing\Log\Grid')->toHtml();
        $this->setAjaxContent($response);

        return $this->getResult();
    }

    //########################################
}