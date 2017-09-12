<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetEstimatedFees extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    public function execute()
    {
        session_write_close();

        // ---------------------------------------
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        // ---------------------------------------

        if (empty($listingProductId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
        // ---------------------------------------

        $params = array(
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER,
            'logs_action_id' => $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId()
        );

        $dispatcher = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\Verify\Requester $connector */
        $connector = $dispatcher->getCustomConnector('Ebay\Connector\Item\Verify\Requester', $params);
        $connector->setListingProduct($listingProduct);

        $fees = [];
        try {
            $connector->process();
            $fees = $connector->getPreparedResponseData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }

        if (!is_null($fees)) {
            foreach ($connector->getResponse()->getMessages()->getErrorEntities() as $errorMessage) {
                $connector->getLogger()->logListingProductMessage(
                    $listingProduct,
                    $errorMessage
                );
            }
        }

        $errors = $connector->getLogger()->getStoredMessages();

        // ---------------------------------------
        if (empty($fees)) {
            if (empty($errors)) {
                $this->setJsonContent(['error' => true]);
            } else {
                $errorsBlock = $this->createBlock('Ebay\Listing\View\Ebay\Fee\Errors');
                $errorsBlock->setData('errors', $errors);

                $this->setJsonContent([
                    'title' => $this->__(
                        'Estimated Fee Details For Product: "%title%"', $listingProduct->getMagentoProduct()->getName()
                    ),
                    'html' => $errorsBlock->toHtml()
                ]);
            }
            return $this->getResult();
        }
        // ---------------------------------------

        $details = $this->createBlock('Ebay\Listing\View\Ebay\Fee\Details');
        $details->setData('fees', $fees);
        $details->setData('product_name', $listingProduct->getMagentoProduct()->getName());

        $this->setJsonContent([
            'title' => $this->__(
                'Estimated Fee Details For Product: "%title%"', $listingProduct->getMagentoProduct()->getName()
            ),
            'html' => $details->toHtml()
        ]);
        return $this->getResult();
    }
}