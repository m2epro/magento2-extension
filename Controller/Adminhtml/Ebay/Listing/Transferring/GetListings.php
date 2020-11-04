<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Transferring;

class GetListings extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    //########################################

    public function execute()
    {
        $collection = $this->ebayFactory->getObject('Listing')->getCollection()
            ->addFieldToFilter('id', ['neq' => (int)$this->getRequest()->getParam('listing_id')])
            ->addFieldToFilter('account_id', (int)$this->getRequest()->getParam('account_id'))
            ->addFieldToFilter('marketplace_id', (int)$this->getRequest()->getParam('marketplace_id'))
            ->addFieldToFilter('store_id', (int)$this->getRequest()->getParam('store_id'));

        $listings = [];
        foreach ($collection->getItems() as $listing) {
            $listings[] = [
                'id' => $listing->getId(),
                'title' => $this->getHelper('Data')->escapeHtml($listing->getTitle())
            ];
        }

        $this->getResponse()->setBody($this->getHelper('Data')->jsonEncode($listings));
    }

    //########################################
}
