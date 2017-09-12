<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetListingProductBids extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');

        if (empty($productId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $productId);

        /** @var $dispatcherObject \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('item','get','bids',
            array('item_id' => $listingProduct->getChildObject()->getEbayItem()->getItemId()),
            null,
            null,
            $listingProduct->getAccount()->getId());

        $dispatcherObject->process($connectorObj);
        $bidsData = $connectorObj->getResponseData();

        if (empty($bidsData['items'])) {
            return $this->getResponse()->setBody($this->__('Bids not found.'));
        }

        $grid = $this->createBlock('Ebay\Listing\View\Ebay\Bids\Grid');
        $grid->setBidsData($bidsData['items']);
        $grid->setListingProduct($listingProduct);

        $this->setAjaxContent($grid);
        return $this->getResult();
    }

    //########################################
}