<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

use Ess\M2ePro\Model\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add\DeleteAll
 */
class DeleteAll extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listing = $this->getListing();

        $ids = array_map('intval', $listing->getChildObject()->getAddedListingProductsIds());

        if (empty($ids)) {
            return $this->_redirect('*/*/', ['_current' => true]);
        }

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id', ['in' => $ids]);

        foreach ($collection->getItems() as $listingProduct) {
            $listingProduct->delete();
        }

        $listing->getChildObject()->setData(
            'product_add_ids',
            $this->getHelper('Data')->jsonEncode([])
        )->save();

        return $this->_redirect('*/*/', ['_current' => true]);
    }

    //########################################
}
