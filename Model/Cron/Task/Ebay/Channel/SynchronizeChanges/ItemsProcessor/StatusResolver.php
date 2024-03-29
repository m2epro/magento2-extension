<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges\ItemsProcessor;

class StatusResolver extends \Ess\M2ePro\Model\AbstractModel
{
    public const EBAY_STATUS_ACTIVE = 'Active';
    public const EBAY_STATUS_ENDED = 'Ended';
    public const EBAY_STATUS_COMPLETED = 'Completed';

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;
    /** @var int  */
    protected $channelQty = 0;
    /** @var int  */
    protected $channelQtySold = 0;
    /** @var null  */
    protected $productStatus = null;
    /** @var null  */
    protected $onlineDuration = null;
    /** @var null  */
    protected $productAdditionalData = null;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Listing\Product\ScheduledStopAction */
    private $scheduledStopActionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Listing\Product\ScheduledStopAction $scheduledStopActionHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->scheduledStopActionHelper = $scheduledStopActionHelper;
    }

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
            $this->listingProduct->isInactive();

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

    protected function handleActiveStatus()
    {
        if ($this->channelQty - $this->channelQtySold <= 0) {
            // Listed Hidden Status can be only for GTC items
            if ($this->listingProduct->getChildObject()->getOnlineDuration() === null) {
                $this->onlineDuration = \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
            }

            $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;

            return;
        }

        $listingProductId = (int)$this->listingProduct->getId();
        if (
            $this->channelQty - $this->channelQtySold > 0
            && $this->scheduledStopActionHelper->isStopActionScheduled($listingProductId)
        ) {
            $this->scheduledStopActionHelper->removeScheduledStopAction($listingProductId);
        }

        $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
    }

    protected function handleCompletedStatus()
    {
        if ($this->setProductStatusInactive()) {
            return;
        }

        if (
            $this->channelQty - $this->channelQtySold > 0
            && $this->listingProduct->getChildObject()->isOnlineDurationGtc()
        ) {
            $listingProductId = (int)$this->listingProduct->getId();
            if ($this->scheduledStopActionHelper->isStopActionScheduled($listingProductId)) {
                $this->scheduledStopActionHelper->removeScheduledStopAction($listingProductId);
                $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;
            } else {
                $this->scheduledStopActionHelper->scheduleStopAction($listingProductId);
                $this->productStatus = $this->listingProduct->getStatus();
            }

            return;
        }

        $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;
    }

    protected function handleEndedStatus()
    {
        $listingProductId = (int)$this->listingProduct->getId();
        if (
            $this->listingProduct->getChildObject()->isOnlineDurationGtc()
            && $this->scheduledStopActionHelper->isStopActionScheduled($listingProductId)
        ) {
            $this->scheduledStopActionHelper->removeScheduledStopAction($listingProductId);
        }

        if (!$this->setProductStatusInactive()) {
            $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;
        }
    }

    protected function setProductStatusInactive(): bool
    {
        if (!$this->listingProduct->isHidden() && $this->channelQty == $this->channelQtySold) {
            $this->productStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;

            return true;
        }

        return false;
    }

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
}
