<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Other;

use \Ess\M2ePro\Model\Amazon\Template;
use \Ess\M2ePro\Helper\Component\Amazon;

class Moving extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Account|null
     */
    protected $account = NULL;

    protected $tempObjectsCache = array();

    protected $activeRecordFactory;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function initialize(\Ess\M2ePro\Model\Account $account = NULL)
    {
        $this->account = $account;
        $this->tempObjectsCache = array();
    }

    //########################################

    /**
     * @param array $otherListings
     * @return bool
     */
    public function autoMoveOtherListingsProducts(array $otherListings)
    {
        $otherListingsFiltered = array();

        foreach ($otherListings as $otherListing) {

            if (!($otherListing instanceof \Ess\M2ePro\Model\Listing\Other)) {
                continue;
            }

            /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */

            $otherListingsFiltered[] = $otherListing;
        }

        if (count($otherListingsFiltered) <= 0) {
            return false;
        }

        $sortedItems = array();

        /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */
        foreach ($otherListingsFiltered as $otherListing) {
            $sortedItems[$otherListing->getAccountId()][] = $otherListing;
        }

        $result = true;

        foreach ($sortedItems as $otherListings) {
            foreach ($otherListings as $otherListing) {
                /** @var $otherListing \Ess\M2ePro\Model\Listing\Other */
                $temp = $this->autoMoveOtherListingProduct($otherListing);
                $temp === false && $result = false;
            }
        }

        return $result;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $otherListing
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function autoMoveOtherListingProduct(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        $this->setAccountByOtherListingProduct($otherListing);

        if (!$this->getAmazonAccount()->isOtherListingsMoveToListingsEnabled()) {
            return false;
        }

        $listing = $this->getDefaultListing($otherListing);

        if (!($listing instanceof \Ess\M2ePro\Model\Listing)) {
            return false;
        }

        $listingProduct = $listing->addProduct(
            $otherListing->getProductId(), \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION
        );

        if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $variationManager->switchModeToAnother();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Other $amazonOtherListing */
        $amazonOtherListing = $otherListing->getChildObject();

        $dataForUpdate = array(
            'general_id'         => $amazonOtherListing->getGeneralId(),
            'sku'                => $amazonOtherListing->getSku(),
            'online_price'       => $amazonOtherListing->getOnlinePrice(),
            'online_qty'         => $amazonOtherListing->getOnlineQty(),
            'is_afn_channel'     => (int)$amazonOtherListing->isAfnChannel(),
            'is_isbn_general_id' => (int)$amazonOtherListing->isIsbnGeneralId(),
            'status'             => $otherListing->getStatus(),
            'status_changer'     => $otherListing->getStatusChanger()
        );

        $listingProduct->addData($dataForUpdate)->save();
        $amazonListingProduct->addData(array_merge(
            [$listingProduct->getResource()->getChildPrimary(Amazon::NICK) => $listingProduct->getId()],
            $dataForUpdate
        ))->save();

        if ($amazonOtherListing->isRepricing()) {
            $listingProductRepricing = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing');
            $listingProductRepricing->setData(array(
                'listing_product_id' => $listingProduct->getId(),
                'is_online_disabled' => $amazonOtherListing->isRepricingDisabled(),
                'update_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
                'create_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
            ));
            $listingProductRepricing->save();
        }

        // Set listing store id to Amazon Item
        // ---------------------------------------
        $itemsCollection = $this->activeRecordFactory->getObject('Amazon\Item')->getCollection();

        $itemsCollection->addFieldToFilter('account_id', $otherListing->getAccountId());
        $itemsCollection->addFieldToFilter('marketplace_id', $otherListing->getMarketplaceId());
        $itemsCollection->addFieldToFilter('sku', $amazonOtherListing->getSku());
        $itemsCollection->addFieldToFilter('product_id', $otherListing->getProductId());

        if ($itemsCollection->getSize() > 0) {
            $itemsCollection->getFirstItem()->setData('store_id', $listing->getStoreId())->save();
        } else {
            $dataForAdd = array(
                'account_id'     => $otherListing->getAccountId(),
                'marketplace_id' => $otherListing->getMarketplaceId(),
                'sku'            => $amazonOtherListing->getSku(),
                'product_id'     => $otherListing->getProductId(),
                'store_id'       => $listing->getStoreId()
            );
            $this->activeRecordFactory->getObject('Amazon\Item')->setData($dataForAdd)->save();
        }
        // ---------------------------------------

        $logModel = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $logModel->addProductMessage(
            $otherListing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            NULL,
            \Ess\M2ePro\Model\Listing\Other\Log::ACTION_MOVE_ITEM,
            // M2ePro\TRANSLATIONS
            // Item was successfully Moved
            'Item was successfully Moved',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
        );

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $tempLog->addProductMessage(
            $listingProduct->getListingId(),
            $otherListing->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            NULL,
            \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_FROM_OTHER_LISTING,
            // M2ePro\TRANSLATIONS
            // Product was successfully Moved
            'Product was successfully Moved',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
        );

        if (!$this->getAmazonAccount()->isOtherListingsMoveToListingsSynchModeNone()) {
            $this->activeRecordFactory->getObject('ProductChange')
                ->addUpdateAction($otherListing->getProductId(),
                                  \Ess\M2ePro\Model\ProductChange::INITIATOR_UNKNOWN);
        }

        $otherListing->delete();

        return true;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $otherListing
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getDefaultListing(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        $accountId = $this->getAccount()->getId();

        if (isset($this->tempObjectsCache['listing_'.$accountId])) {
            return $this->tempObjectsCache['listing_'.$accountId];
        }

        $tempCollection = $this->amazonFactory->getObject('Listing')->getCollection();
        $tempCollection->addFieldToFilter('main_table.title',
                                          'Default ('.$this->getAccount()
                                                           ->getTitle().' - '.$this->getMarketplace()
                                                                                   ->getTitle().')');
        $tempItem = $tempCollection->getFirstItem();

        if (!is_null($tempItem->getId())) {
            $this->tempObjectsCache['listing_'.$accountId] = $tempItem;
            return $tempItem;
        }

        $tempModel = $this->amazonFactory->getObject('Listing');

        $dataForAdd = array(
            'title' => 'Default ('.$this->getAccount()->getTitle().' - '.$this->getMarketplace()->getTitle().')',
            'store_id' => $otherListing->getChildObject()->getRelatedStoreId(),
            'marketplace_id' => $this->getMarketplace()->getId(),
            'account_id' => $accountId,

            'template_selling_format_id'  => $this->getDefaultSellingFormatTemplate()->getId(),
            'template_synchronization_id' => $this->getDefaultSynchronizationTemplate()->getId(),

            'source_products' => \Ess\M2ePro\Model\Listing::SOURCE_PRODUCTS_CUSTOM,

            'sku_mode' => \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_DEFAULT,
            'generate_sku_mode' => \Ess\M2ePro\Model\Amazon\Listing::GENERATE_SKU_MODE_NO,
            'general_id_mode' => \Ess\M2ePro\Model\Amazon\Listing::GENERAL_ID_MODE_NOT_SET,
            'worldwide_id_mode' => \Ess\M2ePro\Model\Amazon\Listing::WORLDWIDE_ID_MODE_NOT_SET,
            'search_by_magento_title_mode' =>
                \Ess\M2ePro\Model\Amazon\Listing::SEARCH_BY_MAGENTO_TITLE_MODE_NONE,
            'handling_time_mode' => \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_NONE,
            'restock_date_mode' => \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_NONE,
            'condition_mode' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT,
            'condition_value' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NEW,
            'condition_note_mode' => \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NOTE_MODE_NONE
        );

        $tempModel->addData($dataForAdd)->save();

        $childModel = $tempModel->getChildObject();
        $childModel->addData(array_merge(
            [$tempModel->getResource()->getChildPrimary(Amazon::NICK) => $tempModel->getId()],
            $dataForAdd
        ))->save();

        $this->tempObjectsCache['listing_'.$accountId] = $tempModel;

        return $tempModel;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    protected function getDefaultSynchronizationTemplate()
    {
        $marketplaceId = $this->getMarketplace()->getId();

        if (isset($this->tempObjectsCache['synchronization_'.$marketplaceId])) {
            return $this->tempObjectsCache['synchronization_'.$marketplaceId];
        }

        $tempCollection = $this->amazonFactory->getObject('Template\Synchronization')->getCollection();
        $tempCollection->addFieldToFilter('main_table.title','Default ('.$this->getMarketplace()->getTitle().')');
        $tempItem = $tempCollection->getFirstItem();

        if (!is_null($tempItem->getId())) {
            $this->tempObjectsCache['synchronization_'.$marketplaceId] = $tempItem;
            return $tempItem;
        }

        $tempModel = $this->amazonFactory->getObject('Template\Synchronization');

        $dataForAdd = array(
            'title' => 'Default ('.$this->getMarketplace()->getTitle().')',
            'list_mode' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_MODE_NONE,
            'list_status_enabled' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_STATUS_ENABLED_YES,
            'list_is_in_stock' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_IS_IN_STOCK_YES,
            'list_qty' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::LIST_QTY_NONE,
            'list_qty_value' => 1,
            'list_qty_value_max' => 10,
            'relist_mode' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_MODE_NONE,
            'relist_filter_user_lock' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_FILTER_USER_LOCK_YES,
            'relist_send_data' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_SEND_DATA_NONE,
            'relist_status_enabled' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_STATUS_ENABLED_YES,
            'relist_is_in_stock' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_IS_IN_STOCK_YES,
            'relist_qty' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_QTY_NONE,
            'relist_qty_value' => 1,
            'relist_qty_value_max' => 10,
            'revise_update_qty' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_UPDATE_QTY_NONE,
            'revise_update_price' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_UPDATE_PRICE_NONE,
            'revise_update_details' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_UPDATE_DETAILS_NONE,
            'revise_update_images' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_UPDATE_IMAGES_NONE,
            'revise_change_selling_format_template' =>
                                \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_NONE,
            'revise_change_description_template' =>
                            \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_CHANGE_DESCRIPTION_TEMPLATE_NONE,
            'revise_change_shipping_template' =>
                \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_CHANGE_SHIPPING_TEMPLATE_NONE,
            'revise_change_listing' =>
                                \Ess\M2ePro\Model\Template\Synchronization::REVISE_CHANGE_LISTING_NONE,
            'stop_status_disabled' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_STATUS_DISABLED_NONE,
            'stop_out_off_stock' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_OUT_OFF_STOCK_NONE,
            'stop_qty' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_QTY_NONE,
            'stop_qty_value' => 0,
            'stop_qty_value_max' => 10
        );

        if ($this->getAmazonAccount()->isOtherListingsMoveToListingsSynchModePrice() ||
            $this->getAmazonAccount()->isOtherListingsMoveToListingsSynchModeAll()
        ) {
            $additionalPriceSettings = array(
                'revise_update_price' => Template\Synchronization::REVISE_UPDATE_PRICE_YES,
                'revise_update_price_max_allowed_deviation_mode' =>
                    Template\Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON,
                'revise_update_price_max_allowed_deviation'      =>
                    Template\Synchronization::REVISE_UPDATE_PRICE_MAX_ALLOWED_DEVIATION_DEFAULT,
            );

            $dataForAdd = array_merge($dataForAdd, $additionalPriceSettings);
        }

        if ($this->getAmazonAccount()->isOtherListingsMoveToListingsSynchModeQty() ||
            $this->getAmazonAccount()->isOtherListingsMoveToListingsSynchModeAll()
        ) {
            $additionalQtySettings = array(
                'revise_update_qty'    => \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_UPDATE_QTY_YES,
                'revise_update_qty_max_applied_value_mode' =>
                    \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON,
                'revise_update_qty_max_applied_value' =>
                    \Ess\M2ePro\Model\Amazon\Template\Synchronization::REVISE_UPDATE_QTY_MAX_APPLIED_VALUE_DEFAULT,
                'relist_mode'          => \Ess\M2ePro\Model\Amazon\Template\Synchronization::RELIST_MODE_YES,
                'stop_status_disabled' => \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_STATUS_DISABLED_YES,
                'stop_out_off_stock'   => \Ess\M2ePro\Model\Amazon\Template\Synchronization::STOP_OUT_OFF_STOCK_YES,
            );

            $dataForAdd = array_merge($dataForAdd, $additionalQtySettings);
        }

        $tempModel->addData($dataForAdd)->save();

        $childModel = $tempModel->getChildObject();
        $childModel->addData(array_merge(
            [$tempModel->getResource()->getChildPrimary(Amazon::NICK) => $tempModel->getId()],
            $dataForAdd
        ))->save();

        $this->tempObjectsCache['synchronization_'.$marketplaceId] = $tempModel;

        return $tempModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    protected function getDefaultSellingFormatTemplate()
    {
        $marketplaceId = $this->getMarketplace()->getId();

        if (isset($this->tempObjectsCache['selling_format_'.$marketplaceId])) {
            return $this->tempObjectsCache['selling_format_'.$marketplaceId];
        }

        $tempCollection = $this->amazonFactory->getObject('Template\SellingFormat')->getCollection();
        $tempCollection->addFieldToFilter('main_table.title','Default ('.$this->getMarketplace()->getTitle().')');
        $tempItem = $tempCollection->getFirstItem();

        if (!is_null($tempItem->getId())) {
            $this->tempObjectsCache['selling_format_'.$marketplaceId] = $tempItem;
            return $tempItem;
        }

        $tempModel = $this->amazonFactory->getObject('Template\SellingFormat');

        $dataForAdd = array(
            'title' => 'Default ('.$this->getMarketplace()->getTitle().')',

            'qty_mode' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
            'qty_custom_value' => 1,

            'currency' => $this->getMarketplace()->getChildObject()->getDefaultCurrency(),
            'regular_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
            'regular_price_variation_mode' =>
                \Ess\M2ePro\Model\Amazon\Template\SellingFormat::PRICE_VARIATION_MODE_PARENT,

            'regular_map_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,

            'regular_sale_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'regular_sale_price_start_date_mode' => \Ess\M2ePro\Model\Amazon\Template\SellingFormat::DATE_VALUE,
            'regular_sale_price_start_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),
            'regular_sale_price_end_date_mode' => \Ess\M2ePro\Model\Amazon\Template\SellingFormat::DATE_VALUE,
            'regular_sale_price_end_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d')
        );

        $tempModel->addData($dataForAdd)->save();

        $childModel = $tempModel->getChildObject();
        $childModel->addData(array_merge(
            [$tempModel->getResource()->getChildPrimary(Amazon::NICK) => $tempModel->getId()],
            $dataForAdd
        ))->save();

        $this->tempObjectsCache['selling_format_'.$marketplaceId] = $tempModel;

        return $tempModel;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    protected function getAmazonAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getAmazonAccount()->getMarketplace();
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $otherListing
     */
    protected function setAccountByOtherListingProduct(\Ess\M2ePro\Model\Listing\Other $otherListing)
    {
        if (!is_null($this->account) && $this->account->getId() == $otherListing->getAccountId()) {
            return;
        }

        $this->account = $this->amazonFactory->getCachedObjectLoaded(
            'Account',$otherListing->getAccountId()
        );
    }

    //########################################
}