<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetEstimatedFees extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    public function execute()
    {
        session_write_close();

        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('listing_id');
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        // ---------------------------------------

        if (empty($listingProductId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $listingProductCollection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id', $listingId);
        $listingProductCollection->addFieldToFilter('status', array('in' => array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
        )));
        $listingProductCollection->setPageSize(3);
        $listingProductCollection->addFieldToFilter('id', $listingProductId);

        $listingProductCollection->load();
        // ---------------------------------------

        // ---------------------------------------
        if ($listingProductCollection->count() == 0) {
            $this->setJsonContent(['error' => true]);
            return $this->getResult();
        }
        // ---------------------------------------

        $fees = $errors = array();
        $sourceProduct = NULL;

        foreach ($listingProductCollection as $product) {

            $fees = array();

            $params = array(
                'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER,
                'logs_action_id' => $this->activeRecordFactory->getObject('Listing\Log')->getNextActionId()
            );

            $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\Verify\SingleRequester $connector */
            $connector = $dispatcher->getCustomConnector('Ebay\Connector\Item\Verify\SingleRequester', $params);
            $connector->setListingProduct($product);

            try {
                $connector->process();
                $fees = $connector->getPreparedResponseData();
            } catch (\Exception $exception) {
                $this->getHelper('Module\Exception')->process($exception);
            }

            if (!empty($fees)) {
                $sourceProduct = $product;
                break;
            }

            $currentErrors = $connector->getLogger()->getStoredMessages();

            if (count($currentErrors) > 0) {
                $errors = $currentErrors;
            }
        }

        // ---------------------------------------
        if (empty($fees)) {
            if (empty($errors)) {
                $this->setJsonContent(['error' => true]);
            } else {
                $errorsBlock = $this->createBlock('Ebay\Listing\View\Ebay\Fee\Errors');
                $errorsBlock->setData('errors', $errors);

                $this->setJsonContent([
                    'title' => $this->__(
                        'Estimated Fee Details For Product: "%title%"', $sourceProduct->getMagentoProduct()->getName()
                    ),
                    'html' => $errorsBlock->toHtml()
                ]);
            }
            return $this->getResult();
        }
        // ---------------------------------------

        $details = $this->createBlock('Ebay\Listing\View\Ebay\Fee\Details');
        $details->setData('fees', $fees);
        $details->setData('product_name', $sourceProduct->getMagentoProduct()->getName());

        $this->setJsonContent([
            'title' => $this->__(
                'Estimated Fee Details For Product: "%title%"', $sourceProduct->getMagentoProduct()->getName()
            ),
            'html' => $details->toHtml()
        ]);
        return $this->getResult();
    }
}