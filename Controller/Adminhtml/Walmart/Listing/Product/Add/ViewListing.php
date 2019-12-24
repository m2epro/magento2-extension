<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\ViewListing
 */
class ViewListing extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');

        if (empty($listingId)) {
            return $this->_redirect('*/walmart_listing/index');
        }

        $this->getHelper('Data\Session')->setValue('temp_products', []);

        return $this->_redirect('*/walmart_listing/view', [
            'id' => $listingId
        ]);
    }

    //########################################
}
