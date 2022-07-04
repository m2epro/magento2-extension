<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;
use Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Individual\Manage;
/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Individual\GetManagePopup
 */
class GetManagePopup extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');

        if (!$listingProductId) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Listing Product must be specified.')
            ]);

            return $this->getResult();
        }

        $variationManageBlock = $this->getLayout()->createBlock(Manage::class)
                                                  ->setData('listing_product_id', $listingProductId);

        $this->setJsonContent([
            'type' => 'success',
            'html' => $variationManageBlock->toHtml()
        ]);

        return $this->getResult();
    }
}
