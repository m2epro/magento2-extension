<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Unmanaged;

class Removing extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Unmanaged\Index
{
    public function execute()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (!$productIds) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $productArray = explode(',', $productIds);

        if (empty($productArray)) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        foreach ($productArray as $productId) {
            $listingOther = $this->amazonFactory->getObjectLoaded(
                'Listing\Other',
                $productId
            );

            if ($listingOther->getProductId() !== null) {
                $listingOther->unmapProduct();
            }

            $listingOther->delete();
        }

        $this->setAjaxContent('1', false);

        return $this->getResult();
    }
}
