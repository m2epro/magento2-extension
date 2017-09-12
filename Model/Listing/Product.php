<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Product getResource()
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product|\Ess\M2ePro\Model\Amazon\Listing\Product getChildObject()
 * @method \Ess\M2ePro\Model\Listing\Product\Action\Configurator|NULL getActionConfigurator()
 *
 * @method setActionConfigurator(\Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
 */
class Product extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    const ACTION_LIST    = 1;
    const ACTION_RELIST  = 2;
    const ACTION_REVISE  = 3;
    const ACTION_STOP    = 4;
    const ACTION_DELETE  = 5;

    const STATUS_NOT_LISTED = 0;
    const STATUS_SOLD = 1;
    const STATUS_LISTED = 2;
    const STATUS_STOPPED = 3;
    const STATUS_FINISHED = 4;
    const STATUS_UNKNOWN = 5;
    const STATUS_BLOCKED = 6;
    const STATUS_HIDDEN = 7;

    const STATUS_CHANGER_UNKNOWN = 0;
    const STATUS_CHANGER_SYNCH = 1;
    const STATUS_CHANGER_USER = 2;
    const STATUS_CHANGER_COMPONENT = 3;
    const STATUS_CHANGER_OBSERVER = 4;

    const SYNCH_STATUS_OK    = 0;
    const SYNCH_STATUS_NEED  = 1;
    const SYNCH_STATUS_SKIP  = 2;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Listing
     */
    protected $listingModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Magento\Product\Cache
     */
    protected $magentoProductModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Product');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if ($this->isComponentModeEbay() && $this->getAccount()->getChildObject()->isModeSandbox()) {
            return false;
        }

        if (parent::isLocked()) {
            return true;
        }

        if ($this->getStatus() == self::STATUS_LISTED) {
            return true;
        }

        return false;
    }

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $variations = $this->getVariations(true);
        foreach ($variations as $variation) {
            $variation->delete();
        }

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getComponentMode());
        $tempLog->addProductMessage($this->getListingId(),
                                    $this->getProductId(),
                                    $this->getId(),
                                    \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                    NULL,
                                    \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_LISTING,
                                    // M2ePro\TRANSLATIONS
                                    // Product was successfully Deleted
                                    'Product was successfully Deleted',
                                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM);

        $this->listingModel = NULL;
        $this->magentoProductModel = NULL;

        $this->deleteChildInstance();

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        if (is_null($this->listingModel)) {
            $this->listingModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),'Listing',$this->getData('listing_id')
            );
        }

        return $this->listingModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing $instance
     */
    public function setListing(\Ess\M2ePro\Model\Listing $instance)
    {
         $this->listingModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        if (is_null($this->magentoProductModel)) {
            $this->magentoProductModel = $this->modelFactory->getObject('Magento\Product\Cache');
            $this->magentoProductModel->setProductId($this->getProductId());
        }

        return $this->prepareMagentoProduct($this->magentoProductModel);
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Cache $instance
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product\Cache $instance)
    {
        $this->magentoProductModel = $this->prepareMagentoProduct($instance);
    }

    protected function prepareMagentoProduct(\Ess\M2ePro\Model\Magento\Product\Cache $instance)
    {
        $instance->setStoreId($this->getListing()->getStoreId());
        $instance->setStatisticId($this->getId());

        if (method_exists($this->getChildObject(), 'prepareMagentoProduct')) {
            $instance = $this->getChildObject()->prepareMagentoProduct($instance);
        }

        return $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getListing()->getMarketplace();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @return \Ess\M2ePro\Model\Listing\Product\Variation[]
     */
    public function getVariations($asObjects = false, array $filters = array(), $tryToGetFromStorage = true)
    {
        $storageKey = "listing_product_{$this->getId()}_variations_" .
            md5((string)$asObjects . $this->getHelper('Data')->jsonEncode($filters));

        if ($tryToGetFromStorage && ($cacheData = $this->getHelper('Data\Cache\Runtime')->getValue($storageKey))) {
            return $cacheData;
        }

        $variations = $this->getRelatedComponentItems(
            'Listing\Product\Variation','listing_product_id',$asObjects,$filters
        );

        if ($asObjects) {
            foreach ($variations as $variation) {
                /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
                $variation->setListingProduct($this);
            }
        }

        $this->getHelper('Data\Cache\Runtime')->setValue($storageKey, $variations, array(
            'listing_product',
            "listing_product_{$this->getId()}",
            "listing_product_{$this->getId()}_variations"
        ));

        return $variations;
    }

    //########################################

    /**
     * @return int
     */
    public function getListingId()
    {
        return (int)$this->getData('listing_id');
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return (int)$this->getData('product_id');
    }

    /**
     * @return bool
     */
    public function isTriedToList()
    {
        return (bool)$this->getData('tried_to_list');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData('status');
    }

    /**
     * @return int
     */
    public function getStatusChanger()
    {
        return (int)$this->getData('status_changer');
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAdditionalData()
    {
        return $this->getSettings('additional_data');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function needSynchRulesCheck()
    {
        return (bool)$this->getData('need_synch_rules_check');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSynchStatus()
    {
        return (int)$this->getData('synch_status');
    }

    /**
     * @return bool
     */
    public function isSynchStatusOk()
    {
        return $this->getSynchStatus() == self::SYNCH_STATUS_OK;
    }

    /**
     * @return bool
     */
    public function isSynchStatusNeed()
    {
        return $this->getSynchStatus() == self::SYNCH_STATUS_NEED;
    }

    /**
     * @return bool
     */
    public function isSynchStatusSkip()
    {
        return $this->getSynchStatus() == self::SYNCH_STATUS_SKIP;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getSynchReasons()
    {
        $reasons = $this->getData('synch_reasons');
        $reasons = explode(',',$reasons);

        return array_unique(array_filter($reasons));
    }

    //########################################

    /**
     * @return bool
     */
    public function isNotListed()
    {
        return $this->getStatus() == self::STATUS_NOT_LISTED;
    }

    /**
     * @return bool
     */
    public function isUnknown()
    {
        return $this->getStatus() == self::STATUS_UNKNOWN;
    }

    /**
     * @return bool
     */
    public function isBlocked()
    {
        return $this->getStatus() == self::STATUS_BLOCKED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isListed()
    {
        return $this->getStatus() == self::STATUS_LISTED;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->getStatus() == self::STATUS_HIDDEN;
    }

    /**
     * @return bool
     */
    public function isSold()
    {
        return $this->getStatus() == self::STATUS_SOLD;
    }

    /**
     * @return bool
     */
    public function isStopped()
    {
        return $this->getStatus() == self::STATUS_STOPPED;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->getStatus() == self::STATUS_FINISHED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isListable()
    {
        return ($this->isNotListed() || $this->isSold() ||
                $this->isStopped() || $this->isFinished() ||
                $this->isHidden() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    /**
     * @return bool
     */
    public function isRelistable()
    {
        return ($this->isSold() || $this->isStopped() ||
                $this->isFinished() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    /**
     * @return bool
     */
    public function isRevisable()
    {
        return ($this->isListed() || $this->isHidden() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    /**
     * @return bool
     */
    public function isStoppable()
    {
        return ($this->isListed() || $this->isHidden() || $this->isUnknown()) &&
                !$this->isBlocked();
    }

    //########################################

    public function listAction(array $params = array())
    {
        return $this->getChildObject()->listAction($params);
    }

    public function relistAction(array $params = array())
    {
        return $this->getChildObject()->relistAction($params);
    }

    public function reviseAction(array $params = array())
    {
        return $this->getChildObject()->reviseAction($params);
    }

    public function stopAction(array $params = array())
    {
        return $this->getChildObject()->stopAction($params);
    }

    public function deleteAction(array $params = array())
    {
        return $this->getChildObject()->deleteAction($params);
    }

    //########################################

    public function getTrackingAttributes()
    {
        return $this->getChildObject()->getTrackingAttributes();
    }

    //########################################

    public function afterSave()
    {
        if ($this->isComponentModeEbay()) {
            $this->processEbayItemUUID();
        }

        return parent::afterSave();
    }

    // ---------------------------------------

    private function processEbayItemUUID()
    {
        if ($this->isObjectCreatingState()) {
            return;
        }

        $oldStatus = (int)$this->getOrigData('status');
        $newStatus = (int)$this->getData('status');

        $trackedStatuses = array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED,
            \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
        );

        if ($oldStatus == $newStatus || !in_array($newStatus, $trackedStatuses)){
            return;
        }

        // the child object will be saved on parent side, so we just set needed data
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $this->getChildObject();
        $ebayListingProduct->setData('item_uuid', $ebayListingProduct->generateItemUUID());
    }

    //########################################
}