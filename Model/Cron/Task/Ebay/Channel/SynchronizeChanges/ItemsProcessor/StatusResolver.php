<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges\ItemsProcessor;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges\ItemsProcessor\StatusResolver
 */
class StatusResolver extends \Ess\M2ePro\Model\AbstractModel
{
    const EBAY_STATUS_ACTIVE = 'Active';
    const EBAY_STATUS_ENDED = 'Ended';
    const EBAY_STATUS_COMPLETED = 'Completed';

    const SKIP_FLAG_KEY = 'skip_first_completed_status_on_sync';

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;
    protected $channelQty = 0;
    protected $channelQtySold = 0;

    protected $productStatus = null;
    protected $onlineDuration = null;
    protected $productAdditionalData = null;

    //########################################

    public function resolveStatus(
        $channelQty,
        $channelQtySold,
        $ebayStatus,
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ) {
        $this->channelQty = $channelQty;
        $this->channelQtySold = $channelQtySold;
        $this->listingProduct = $listingProduct;

        $isBehaviorOfGtc = $ebayStatus == self::EBAY_STATUS_ACTIVE &&
            $this->channelQty - $this->channelQtySold > 0 &&
            $this->listingProduct->isStopped();

        // Listing product isn't listed and it child must have another item_id
        $isAllowedProductStatus = $this->listingProduct->isListed() || $this->listingProduct->isHidden();

        if (!$isBehaviorOfGtc && !$isAllowedProductStatus) {
            return false;
        }

        switch ($ebayStatus) {
            case self::EBAY_STATUS_ACTIVE:
                $this->handleActiveStatus();
                break;
            case self::EBAY_STATUS_COMPLETED:
                $this->handleCompletedStatus();
                break;
            case self::EBAY_STATUS_ENDED:
                $this->handleEndedStatus();
                break;
            default:
                throw new \Ess\M2ePro\Model\Exception('Unknown eBay listing status');
        }

        return true;
    }

    //########################################

    protected function handleActiveStatus()
    {
        if ($this->channelQty - $this->channelQtySold <= 0) {
            // Listed Hidden Status can be only for GTC items
            if ($this->listingProduct->getChildObject()->getOnlineDuration() === null) {
                $this->onlineDuration = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;;
            }

            $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;
            return;
        }

        if ($this->channelQty - $this->channelQtySold > 0 && $this->statusCompletedIsAlreadySkipped()) {
            $this->unsetSkipFlag();
        }

        $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
    }

    protected function handleCompletedStatus()
    {
        if ($this->setProductStatusSold()) {
            return;
        }

        if ($this->channelQty - $this->channelQtySold > 0) {
            if ($this->statusCompletedIsAlreadySkipped()) {
                $this->unsetSkipFlag();
                $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
            } else {
                $this->setSkipFlag();
                $this->productStatus = $this->listingProduct->getStatus();
            }

            return;
        }

        $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
    }

    protected function handleEndedStatus()
    {
        if (!$this->setProductStatusSold()) {
            $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED;
        }
    }

    // ---------------------------------------

    protected function setProductStatusSold()
    {
        if ($this->listingProduct->isHidden() && $this->channelQty == $this->channelQtySold) {
            $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD;
            return true;
        }

        return false;
    }

    //########################################

    public function statusCompletedIsAlreadySkipped()
    {
        $additionalData = $this->listingProduct->getAdditionalData();
        return isset($additionalData[self::SKIP_FLAG_KEY]);
    }

    protected function setSkipFlag()
    {
        $additionalData = $this->listingProduct->getAdditionalData();
        $additionalData[self::SKIP_FLAG_KEY] = true;
        $this->productAdditionalData = $this->getHelper('Data')->jsonEncode($additionalData);
    }

    protected function unsetSkipFlag()
    {
        $additionalData = $this->listingProduct->getAdditionalData();
        unset($additionalData[self::SKIP_FLAG_KEY]);
        $this->productAdditionalData = $this->getHelper('Data')->jsonEncode($additionalData);
    }

    //########################################

    public function getProductStatus()
    {
        return $this->productStatus;
    }

    public function getOnlineDuration()
    {
        return $this->onlineDuration;
    }

    public function getProductAdditionalData()
    {
        return $this->productAdditionalData;
    }

    //########################################
}
