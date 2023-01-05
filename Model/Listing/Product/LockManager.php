<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

class LockManager
{
    private const LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME = 1800; // 30 min

    /** @var \Ess\M2ePro\Model\Listing\Product */
    private $listingProduct;
    /** @var int */
    private $initiator;
    /** @var int */
    private $logsActionId;
    /** @var int */
    private $logsAction;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    private $lockItemManager;
    /** @var \Ess\M2ePro\Model\Listing\Log */
    private $listingLog;

    /** @var \Ess\M2ePro\Model\Lock\Item\ManagerFactory */
    private $lockItemManagerFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\LogFactory */
    private $amazonListingLogFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\LogFactory */
    private $ebayListingLogFactory;
    /** @var \Ess\M2ePro\Model\Walmart\Listing\LogFactory */
    private $walmartListingLogFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\LogFactory $amazonListingLogFactory,
        \Ess\M2ePro\Model\Ebay\Listing\LogFactory $ebayListingLogFactory,
        \Ess\M2ePro\Model\Walmart\Listing\LogFactory $walmartListingLogFactory,
        \Ess\M2ePro\Model\Lock\Item\ManagerFactory $lockItemManagerFactory,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper
    ) {
        $this->lockItemManagerFactory = $lockItemManagerFactory;
        $this->translationHelper = $translationHelper;
        $this->amazonListingLogFactory = $amazonListingLogFactory;
        $this->ebayListingLogFactory = $ebayListingLogFactory;
        $this->walmartListingLogFactory = $walmartListingLogFactory;
    }

    // ----------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct): self
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    /**
     * @param int $initiator
     *
     * @return $this
     */
    public function setInitiator($initiator): self
    {
        $this->initiator = $initiator;

        return $this;
    }

    /**
     * @param int $logsActionId
     *
     * @return $this
     */
    public function setLogsActionId($logsActionId): self
    {
        $this->logsActionId = $logsActionId;

        return $this;
    }

    /**
     * @param int $logsAction
     *
     * @return $this
     */
    public function setLogsAction($logsAction): self
    {
        $this->logsAction = $logsAction;

        return $this;
    }

    // ----------------------------------------

    public function isLocked(): bool
    {
        if ($this->listingProduct->isSetProcessingLock(null)) {
            return true;
        }

        if (!$this->getLockItemManager()->isExist()) {
            return false;
        }

        if ($this->getLockItemManager()->isInactiveMoreThanSeconds(self::LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME)) {
            $this->getLockItemManager()->remove();

            return false;
        }

        return true;
    }

    public function checkLocking(): bool
    {
        if (!$this->isLocked()) {
            return false;
        }

        $this->getListingLog()->addProductMessage(
            $this->listingProduct->getListingId(),
            $this->listingProduct->getProductId(),
            $this->listingProduct->getId(),
            $this->initiator,
            $this->logsActionId,
            $this->logsAction,
            $this->translationHelper->__(
                'Another Action is being processed. Try again when the Action is completed.'
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );

        return true;
    }

    // ----------------------------------------

    public function lock(): void
    {
        $this->getLockItemManager()->create();
    }

    public function unlock(): void
    {
        $this->getLockItemManager()->remove();
    }

    // ----------------------------------------

    private function getLockItemManager(): \Ess\M2ePro\Model\Lock\Item\Manager
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        $this->lockItemManager = $this->lockItemManagerFactory->create(
            $this->listingProduct->getComponentMode() . '_listing_product_' . $this->listingProduct->getId()
        );

        return $this->lockItemManager;
    }

    private function getListingLog(): \Ess\M2ePro\Model\Listing\Log
    {
        if ($this->listingLog !== null) {
            return $this->listingLog;
        }

        if ($this->listingProduct->getComponentMode() === \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $this->listingLog = $this->ebayListingLogFactory->create();
        } elseif ($this->listingProduct->getComponentMode() === \Ess\M2ePro\Helper\Component\Amazon::NICK) {
            $this->listingLog = $this->amazonListingLogFactory->create();
        } else {
            $this->listingLog = $this->walmartListingLogFactory->create();
        }

        return $this->listingLog;
    }
}
