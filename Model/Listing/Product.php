<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing;

use \Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;
use \Ess\M2ePro\Model\Ebay\Listing\Product as EbayListingProduct;
use \Ess\M2ePro\Model\Walmart\Listing\Product as WalmartListingProduct;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processing as AmazonActionProcessing;
use \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Processing as EbayActionProcessing;
use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Processing as WalmartActionProcessing;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Product
 *
 * @method \Ess\M2ePro\Model\ResourceModel\Listing\Product getResource()
 * @method AmazonListingProduct|EbayListingProduct|WalmartListingProduct getChildObject()
 *
 * @method \Ess\M2ePro\Model\Listing\Product\Action\Configurator getActionConfigurator()
 * @method setActionConfigurator(\Ess\M2ePro\Model\Listing\Product\Action\Configurator $configurator)
 *
 * @method AmazonActionProcessing|EbayActionProcessing|WalmartActionProcessing getProcessingAction()
 * @method setProcessingAction(AmazonActionProcessing|EbayActionProcessing|WalmartActionProcessing $action)
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

    const MOVING_LISTING_OTHER_SOURCE_KEY = 'moved_from_listing_other_id';

    const GROUPED_PRODUCT_MODE_OPTIONS = 0;
    const GROUPED_PRODUCT_MODE_SET     = 1;

    /**
     * It allows to delete an object without checking if it is isLocked()
     * @var bool
     */
    protected $canBeForceDeleted = false;

    /**
     * @var \Ess\M2ePro\Model\Listing
     */
    protected $listingModel = null;

    /**
     * @var \Ess\M2ePro\Model\Magento\Product\Cache
     */
    protected $magentoProductModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Product');
    }

    //########################################

    public function afterSave()
    {
        $this->_eventManager->dispatch('ess_listing_product_save_after', [
            'object' => $this,
        ]);

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $this->_eventManager->dispatch('ess_listing_product_delete_before', [
            'object' => $this,
        ]);

        return parent::beforeDelete();
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if ($this->canBeForceDeleted()) {
            return false;
        }

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

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $scheduledActions */
        $scheduledActions = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActions->addFieldToFilter('listing_product_id', $this->getId());
        foreach ($scheduledActions->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $item */
            $item->delete();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection $instructionCollection */
        $instructionCollection = $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getCollection();
        $instructionCollection->addFieldToFilter('listing_product_id', $this->getId());
        foreach ($instructionCollection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Listing\Product\Instruction $item */
            $item->delete();
        }

        $this->logProductMessage(
            'Product was Deleted',
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_LISTING,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE
        );

        $this->listingModel = null;
        $this->magentoProductModel = null;

        $this->deleteChildInstance();

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        if ($this->listingModel === null) {
            $this->listingModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
                'Listing',
                $this->getData('listing_id')
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
        if ($this->magentoProductModel === null) {
            $this->magentoProductModel = $this->modelFactory->getObject('Magento_Product_Cache');
            $this->magentoProductModel->setProductId($this->getProductId());

            if ($this->magentoProductModel->isGroupedType()) {
                $this->magentoProductModel->setGroupedProductMode($this->getGroupedProductMode());
            }
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
    public function getVariations($asObjects = false, array $filters = [], $tryToGetFromStorage = true)
    {
        $storageKey = "listing_product_{$this->getId()}_variations_" .
                       sha1((string)$asObjects . $this->getHelper('Data')->jsonEncode($filters));

        if ($tryToGetFromStorage && ($cacheData = $this->getHelper('Data_Cache_Runtime')->getValue($storageKey))) {
            return $cacheData;
        }

        $variations = $this->getRelatedComponentItems(
            'Listing_Product_Variation',
            'listing_product_id',
            $asObjects,
            $filters
        );

        if ($asObjects) {
            foreach ($variations as $variation) {
                /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
                $variation->setListingProduct($this);
            }
        }

        $this->getHelper('Data_Cache_Runtime')->setValue($storageKey, $variations, [
            'listing_product',
            "listing_product_{$this->getId()}",
            "listing_product_{$this->getId()}_variations"
        ]);

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

    //########################################

    /**
     * @return null|int
     */
    public function getGroupedProductMode()
    {
        if (!$this->getMagentoProduct()->isGroupedType()) {
            return null;
        }

        if ($this->isListable()) {
            return $this->getHelper('Module_Configuration')->getGroupedProductMode();
        }

        return (int)$this->getSetting('additional_data', 'grouped_product_mode', self::GROUPED_PRODUCT_MODE_OPTIONS);
    }

    /**
     * @return bool
     */
    public function isGroupedProductModeSet()
    {
        return $this->getGroupedProductMode() === self::GROUPED_PRODUCT_MODE_SET;
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

    public function remapProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $exMagentoProductId = $this->getProductId();
        $newMagentoProductId = $magentoProduct->getProductId();
        $data = ['product_id' => $newMagentoProductId];

        if ($this->getMagentoProduct()->isStrictVariationProduct()
            && $magentoProduct->isSimpleTypeWithoutCustomOptions()) {
            $data['is_variation_product'] = 0;
            $data['is_variation_parent'] = 0;
            $data['variation_parent_id'] = null;
        }

        $this->addData($data)->save();
        $this->getChildObject()->addData($data)->save();
        $this->getChildObject()->mapChannelItemProduct();

        $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
        $instruction->setData(
            [
                'listing_product_id' => $this->getId(),
                'component'          => $this->getComponentMode(),
                'type'               => \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
                'initiator'          => \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_REMAPING_PRODUCT_FROM_LISTING,
                'priority'           => 50,
            ]
        );

        $instruction->save();

        $this->logProductMessage(
            sprintf(
                "Item was relinked from Magento Product ID [%s] to ID [%s]",
                $exMagentoProductId,
                $newMagentoProductId
            ),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            \Ess\M2ePro\Model\Listing\Log::ACTION_REMAP_LISTING_PRODUCT,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
        );
    }

    //########################################

    public function canBeForceDeleted($value = null)
    {
        if ($value === null) {
            return $this->canBeForceDeleted;
        }

        $this->canBeForceDeleted = $value;
        return $this;
    }

    //########################################

    public function logProductMessage($text, $initiator, $action, $type)
    {
        /** @var \Ess\M2ePro\Model\Listing\Log $log */
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($this->getComponentMode());
        $log->addProductMessage(
            $this->getListingId(),
            $this->getProductId(),
            $this->getId(),
            $initiator,
            null,
            $action,
            $text,
            $type
        );
    }

    //########################################
}
