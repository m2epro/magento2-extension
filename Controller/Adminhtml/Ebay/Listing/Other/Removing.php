<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

class Removing extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other
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
            /* @var $listingOther \Ess\M2ePro\Model\Listing\Other */
            $listingOther = $this->ebayFactory->getObjectLoaded(
                'Listing\Other', $productId
            );

            if (!is_null($listingOther->getProductId())) {
                $listingOther->unmapProduct(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            }

            $listingOther->delete();
        }

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }
}