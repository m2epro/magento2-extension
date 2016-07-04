<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class RunRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    public function execute()
    {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return 'You should select Products';
        }

        $listingsProductsIds = explode(',', $listingsProductsIds);
        $listingProducts = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id', array('in' => $listingsProductsIds));

        foreach ($listingProducts as $listingProduct) {
            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            if ($listingProduct->isListed()) {
                $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
            }

            $listingProduct->delete();
        }

        $this->setJsonContent(array(
            'result' => 'success',
            'action_id' => $this->activeRecordFactory->getObject('Listing\Log')->getNextActionId()
        ));

        return $this->getResult();
    }
}