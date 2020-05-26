<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

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
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function add(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        if (!$listingProduct->isStoppable()) {
            return false;
        }

        $requestData = $this->getRequestData($listingProduct);
        if (empty($requestData)) {
            return false;
        }

        $additionalData = [
            'request_data' => $requestData,
        ];

        $addedData = [
            'component_mode'  => $listingProduct->getComponentMode(),
            'is_processed'    => 0,
            'additional_data' => $this->getHelper('Data')->jsonEncode($additionalData),
        ];

        $this->activeRecordFactory->getObject('StopQueue')->setData($addedData)->save();

        return true;
    }

    // ---------------------------------------

    private function getRequestData(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $data = [];

        if ($listingProduct->isComponentModeEbay()) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();
            $ebayAccount        = $ebayListingProduct->getEbayAccount();

            $data = [
                'account'     => $ebayAccount->getServerHash(),
                'marketplace' => $ebayListingProduct->getMarketplace()->getNativeId(),
                'item_id'     => $ebayListingProduct->getEbayItem()->getItemId(),
            ];
        }

        if ($listingProduct->isComponentModeAmazon()) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();
            $amazonAccount        = $amazonListingProduct->getAmazonAccount();

            $data = [
                'account' => $amazonAccount->getServerHash(),
                'sku'     => $amazonListingProduct->getSku(),
            ];
        }

        if ($listingProduct->isComponentModeWalmart()) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();
            $walmartAccount        = $walmartListingProduct->getWalmartAccount();

            $data = [
                'account' => $walmartAccount->getServerHash(),
                'sku'     => $walmartListingProduct->getSku(),
                'wpid'    => $walmartListingProduct->getWpid()
            ];
        }

        return $data;
    }

    //########################################
}
