<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Listing getResource()
 */

namespace Ess\M2ePro\Model\Walmart;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing
 */
class Listing extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    //########################################

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Listing');
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');

        $temp = parent::delete();
        $temp && $this->descriptionTemplateModel = null;
        $temp && $this->sellingFormatTemplateModel = null;
        $temp && $this->synchronizationTemplateModel = null;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    public function getWalmartAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    public function getWalmartMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        if ($this->descriptionTemplateModel === null) {
            $this->descriptionTemplateModel = $this->walmartFactory->getCachedObjectLoaded(
                'Template\Description',
                $this->getData('template_description_id')
            );
        }

        return $this->descriptionTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if ($this->sellingFormatTemplateModel === null) {
            $this->sellingFormatTemplateModel = $this->walmartFactory->getCachedObjectLoaded(
                'Template\SellingFormat',
                $this->getData('template_selling_format_id')
            );
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
        $this->sellingFormatTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        if ($this->synchronizationTemplateModel === null) {
            $this->synchronizationTemplateModel = $this->walmartFactory->getCachedObjectLoaded(
                'Template\Synchronization',
                $this->getData('template_synchronization_id')
            );
        }

        return $this->synchronizationTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Synchronization $instance
     */
    public function setSynchronizationTemplate(\Ess\M2ePro\Model\Template\Synchronization $instance)
    {
        $this->synchronizationTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getWalmartDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getWalmartSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Synchronization
     */
    public function getWalmartSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     */
    public function getProducts($asObjects = false, array $filters = [])
    {
        return $this->getParentObject()->getProducts($asObjects, $filters);
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoGlobalAddingCategoryTemplateId()
    {
        return (int)$this->getData('auto_global_adding_category_template_id');
    }

    /**
     * @return int
     */
    public function getAutoWebsiteAddingCategoryTemplateId()
    {
        return (int)$this->getData('auto_website_adding_category_template_id');
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $listingOtherProduct
     * @param int $initiator
     * @param bool $checkingMode
     * @param bool $checkHasProduct
     * @return bool|\Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductFromOther(
        \Ess\M2ePro\Model\Listing\Other $listingOtherProduct,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
        $checkingMode = false,
        $checkHasProduct = true
    ) {
        if (!$listingOtherProduct->getProductId()) {
            return false;
        }

        $productId = $listingOtherProduct->getProductId();
        $result = $this->getParentObject()->addProduct($productId, $initiator, $checkingMode, $checkHasProduct);

        if ($checkingMode) {
            return $result;
        }

        if (!($result instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return false;
        }

        $listingProduct = $result;

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $variationManager->switchModeToAnother();
        }

        $walmartListingProduct->getWalmartItem()
            ->setData('store_id', $this->getParentObject()->getStoreId())
            ->save();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Other $walmartListingOther */
        $walmartListingOther = $listingOtherProduct->getChildObject();

        $dataForUpdate = [
            'sku'  => $walmartListingOther->getSku(),
            'gtin' => $walmartListingOther->getGtin(),
            'upc'  => $walmartListingOther->getUpc(),
            'ean'  => $walmartListingOther->getEan(),
            'wpid' => $walmartListingOther->getWpid(),

            'item_id'     => $walmartListingOther->getItemId(),
            'channel_url' => $walmartListingOther->getChannelUrl(),

            'online_price'            => $walmartListingOther->getOnlinePrice(),
            'online_qty'              => $walmartListingOther->getOnlineQty(),
            'is_online_price_invalid' => $walmartListingOther->isOnlinePriceInvalid(),

            'status'         => $listingOtherProduct->getStatus(),
            'status_changer' => $listingOtherProduct->getStatusChanger(),

            'publish_status'   => $walmartListingOther->getPublishStatus(),
            'lifecycle_status' => $walmartListingOther->getLifecycleStatus(),

            'status_change_reasons' => $walmartListingOther->getData('status_change_reasons'),
        ];

        $listingProduct->addData($dataForUpdate);
        $walmartListingProduct->addData($dataForUpdate);
        $listingProduct->save();

        return $listingProduct;
    }

    public function addProductFromListing(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Listing $sourceListing
    ) {
        if (!$this->getParentObject()->addProductFromListing($listingProduct, $sourceListing, false)) {
            return false;
        }

        if ($this->getParentObject()->getStoreId() != $sourceListing->getStoreId()) {
            if (!$listingProduct->isNotListed()) {
                if ($item = $listingProduct->getChildObject()->getWalmartItem()) {
                    $item->setData('store_id', $this->getParentObject()->getStoreId());
                    $item->save();
                }
            }
        }

        return true;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return [];
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @param bool $onlyPhysicalUnits
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*', $onlyPhysicalUnits = false)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')
                                                        ->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id', $this->getId());

        if ($onlyPhysicalUnits) {
            $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        }

        if (is_array($columns) && !empty($columns)) {
            $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingProductCollection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$listingProductCollection->getData() : (array)$listingProductCollection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(
            true,
            ['id', 'synch_status', 'synch_reasons'],
            true
        );
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData, $oldData, $listingsProducts);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
