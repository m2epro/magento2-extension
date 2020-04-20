<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\GetListingProductBids
 */
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
        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'item',
            'get',
            'bids',
            ['item_id' => $listingProduct->getChildObject()->getEbayItem()->getItemId()],
            null,
            null,
            $listingProduct->getAccount()->getId()
        );

        $dispatcherObject->process($connectorObj);
        $bidsData = $connectorObj->getResponseData();

        if (empty($bidsData['items'])) {
            return $this->getResponse()->setBody($this->__('Bids not found.'));
        }

        $grid = $this->createBlock('Ebay_Listing_View_Ebay_Bids_Grid');
        $grid->setBidsData($bidsData['items']);
        $grid->setListingProduct($listingProduct);

        $this->setAjaxContent($grid);
        return $this->getResult();
    }

    //########################################
}
