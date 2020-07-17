<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\LockManager
 */
class LockManager extends \Ess\M2ePro\Model\AbstractModel
{
    const LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME = 1800; // 30 min

    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    protected $lockItemManager = null;

    /** @var \Ess\M2ePro\Model\Listing\Log */
    protected $listingLog = null;

    protected $initiator = null;

    protected $logsActionId = null;

    protected $logsAction = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;
        return $this;
    }

    public function setLogsActionId($logsActionId)
    {
        $this->logsActionId = $logsActionId;
        return $this;
    }

    public function setLogsAction($logsAction)
    {
        $this->logsAction = $logsAction;
        return $this;
    }

    //########################################

    public function isLocked()
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

    public function checkLocking()
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
            $this->getHelper('Module\Translation')->__(
                'Another Action is being processed. Try again when the Action is completed.'
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );

        return true;
    }

    // ----------------------------------------

    public function lock()
    {
        $this->getLockItemManager()->create();
    }

    public function unlock()
    {
        $this->getLockItemManager()->remove();
    }

    //########################################

    protected function getLockItemManager()
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        $this->lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => $this->listingProduct->getComponentMode() . '_listing_product_' . $this->listingProduct->getId()
        ]);

        return $this->lockItemManager;
    }

    protected function getListingLog()
    {
        if ($this->listingLog !== null) {
            return $this->listingLog;
        }

        $this->listingLog = $this->activeRecordFactory->getObject(
            ucfirst($this->listingProduct->getComponentMode()) . '_Listing_Log'
        );

        return $this->listingLog;
    }

    //########################################
}
