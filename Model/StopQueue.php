<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use \Ess\M2ePro\Model\Listing\Product as Listing_Product;

/**
 * Class \Ess\M2ePro\Model\StopQueue
 */
class StopQueue extends ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\StopQueue');
    }

    //########################################

    public function getComponentMode()
    {
        return $this->getData('component_mode');
    }

    public function isProcessed()
    {
        return (bool)$this->getData('is_processed');
    }

    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param int $actionType
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function add(\Ess\M2ePro\Model\Listing\Product $listingProduct, $actionType = Listing_Product::ACTION_STOP)
    {
        if (!$listingProduct->isStoppable()) {
            return false;
        }

        try {
            $requestData = $this->getRequestData($listingProduct, $actionType);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Logger')->process(
                sprintf(
                    'Product [Listing Product ID: %s, SKU %s] was not added to stop queue because of the error: %s',
                    $listingProduct->getId(),
                    $listingProduct->getChildObject()->getSku(),
                    $exception->getMessage()
                ),
                'Product was not added to stop queue',
                false
            );

            $this->getHelper('Module\Exception')->process($exception);

            return false;
        }

        $addedData = [
            'component_mode'  => $listingProduct->getComponentMode(),
            'is_processed'    => 0,
            'additional_data' => $this->getHelper('Data')->jsonEncode(['request_data' => $requestData]),
        ];

        $this->activeRecordFactory->getObject('StopQueue')->setData($addedData)->save();

        return true;
    }

    // ---------------------------------------

    private function getRequestData(Listing_Product $listingProduct, $actionType = Listing_Product::ACTION_STOP)
    {
        $data = [];

        if ($listingProduct->isComponentModeEbay()) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();
            $ebayAccount = $ebayListingProduct->getEbayAccount();

            $data = [
                'account'     => $ebayAccount->getServerHash(),
                'marketplace' => $ebayListingProduct->getMarketplace()->getNativeId(),
                'item_id'     => $ebayListingProduct->getEbayItem()->getItemId(),
                'action_type' => $actionType
            ];
        }

        if ($listingProduct->isComponentModeAmazon()) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonAccount = $amazonListingProduct->getAmazonAccount();

            $data = [
                'account'     => $amazonAccount->getServerHash(),
                'sku'         => $amazonListingProduct->getSku(),
                'action_type' => $actionType
            ];
        }

        if ($listingProduct->isComponentModeWalmart()) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();
            $walmartAccount = $walmartListingProduct->getWalmartAccount();

            $data = [
                'account'     => $walmartAccount->getServerHash(),
                'sku'         => $walmartListingProduct->getSku(),
                'wpid'        => $walmartListingProduct->getWpid(),
                'action_type' => $actionType
            ];
        }

        return $data;
    }

    //########################################
}
