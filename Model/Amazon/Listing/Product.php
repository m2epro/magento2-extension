<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

/**
 * @method \Ess\M2ePro\Model\Listing\Product getParentObject()
 */
class Product extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    const IS_AFN_CHANNEL_NO  = 0;
    const IS_AFN_CHANNEL_YES = 1;

    const IS_ISBN_GENERAL_ID_NO  = 0;
    const IS_ISBN_GENERAL_ID_YES = 1;

    const IS_GENERAL_ID_OWNER_NO  = 0;
    const IS_GENERAL_ID_OWNER_YES = 1;

    const SEARCH_SETTINGS_STATUS_IN_PROGRESS     = 1;
    const SEARCH_SETTINGS_STATUS_NOT_FOUND       = 2;
    const SEARCH_SETTINGS_STATUS_ACTION_REQUIRED = 3;

    const GENERAL_ID_STATE_SET = 0;
    const GENERAL_ID_STATE_NOT_SET = 1;
    const GENERAL_ID_STATE_ACTION_REQUIRED = 2;
    const GENERAL_ID_STATE_READY_FOR_NEW_ASIN = 3;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager
     */
    protected $variationManager = NULL;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing
     */
    protected $repricingModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            foreach ($this->getVariationManager()->getTypeModel()->getChildListingsProducts() as $child) {
                /** @var $child \Ess\M2ePro\Model\Listing\Product */
                if ($child->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            foreach ($this->getVariationManager()->getTypeModel()->getChildListingsProducts() as $child) {
                /** @var $child \Ess\M2ePro\Model\Listing\Product */
                $child->delete();
            }
        }

        $this->variationManager = NULL;

        return parent::delete();
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
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
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
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    public function getAmazonMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getParentObject()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    public function getAmazonListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Source
     */
    public function getListingSource()
    {
        return $this->getAmazonListing()->getSource($this->getActualMagentoProduct());
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getAmazonListing()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\SellingFormat
     */
    public function getAmazonSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getAmazonListing()->getSynchronizationTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Synchronization
     */
    public function getAmazonSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isExistShippingOverrideTemplate()
    {
        return $this->getTemplateShippingOverrideId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingOverride | null
     */
    public function getShippingOverrideTemplate()
    {
        if (!$this->isExistShippingOverrideTemplate()) {
            return null;
        }

        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Amazon\Template\ShippingOverride', $this->getTemplateShippingOverrideId()
        );
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isExistDescriptionTemplate()
    {
        return $this->getTemplateDescriptionId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\Description | null
     */
    public function getDescriptionTemplate()
    {
        if (!$this->isExistDescriptionTemplate()) {
            return null;
        }

        return $this->parentFactory->getCachedObjectLoaded(
            $this->getComponentMode(), 'Template\Description', $this->getTemplateDescriptionId()
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description | null
     */
    public function getAmazonDescriptionTemplate()
    {
        if (!$this->isExistDescriptionTemplate()) {
            return null;
        }

        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Source
     */
    public function getDescriptionTemplateSource()
    {
        if (!$this->isExistDescriptionTemplate()) {
            return null;
        }

        return $this->getAmazonDescriptionTemplate()->getSource($this->getActualMagentoProduct());
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getActualMagentoProduct()
    {
        if (!$this->getVariationManager()->isPhysicalUnit() ||
            !$this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            return $this->getMagentoProduct();
        }

        if ($this->getMagentoProduct()->isConfigurableType() ||
            $this->getMagentoProduct()->isGroupedType()) {

            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception('There are no variations for a variation product.',
                                                     array(
                                                         'listing_product_id' => $this->getId()
                                                     ));
            }
            $variation  = reset($variations);
            $options    = $variation->getOptions(true);
            $option     = reset($options);

            return $option->getMagentoProduct();
        }

        return $this->getMagentoProduct();
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Cache $instance
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function prepareMagentoProduct(\Ess\M2ePro\Model\Magento\Product\Cache $instance)
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return $instance;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            $parentTypeModel = $this->getVariationManager()->getTypeModel();
        } else {
            $parentAmazonListingProduct = $this->getVariationManager()->getTypeModel()->getAmazonParentListingProduct();
            $parentTypeModel = $parentAmazonListingProduct->getVariationManager()->getTypeModel();
        }

        $instance->setVariationVirtualAttributes($parentTypeModel->getVirtualProductAttributes());
        $instance->setVariationFilterAttributes($parentTypeModel->getVirtualChannelAttributes());

        return $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Item
    */
    public function getAmazonItem()
    {
        return $this->activeRecordFactory->getObject('Amazon\Item')->getCollection()
                        ->addFieldToFilter('account_id', $this->getListing()->getAccountId())
                        ->addFieldToFilter('marketplace_id', $this->getListing()->getMarketplaceId())
                        ->addFieldToFilter('sku', $this->getSku())
                        ->setOrder('create_date', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                        ->getFirstItem();
    }

    public function getVariationManager()
    {
        if (is_null($this->variationManager)) {
            $this->variationManager = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Manager');
            $this->variationManager->setListingProduct($this->getParentObject());
        }

        return $this->variationManager;
    }

    public function getVariations($asObjects = false, array $filters = array())
    {
        return $this->getParentObject()->getVariations($asObjects,$filters);
    }

    //########################################

    /**
     * @return bool
     */
    public function isRepricing()
    {
        return (bool)$this->getRepricing()->getId();
    }

    /**
     * @return bool
     */
    public function isRepricingDisabled()
    {
        return $this->isRepricing() && $this->getRepricing()->isOnlineDisabled();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing
     */
    public function getRepricing()
    {
        if (is_null($this->repricingModel)) {
            $this->repricingModel = $this->activeRecordFactory->getObjectLoaded(
                'Amazon\Listing\Product\Repricing', $this->getId(), NULL, false
            );
        }

        return $this->repricingModel;
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateDescriptionId()
    {
        return (int)($this->getData('template_description_id'));
    }

    /**
     * @return int
     */
    public function getTemplateShippingOverrideId()
    {
        return (int)($this->getData('template_shipping_override_id'));
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * @return string
     */
    public function getGeneralId()
    {
        return $this->getData('general_id');
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    /**
     * @return int
     */
    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    public function getOnlineSalePrice()
    {
        return $this->getData('online_sale_price');
    }

    public function getOnlineSalePriceStartDate()
    {
        return $this->getData('online_sale_price_start_date');
    }

    public function getOnlineSalePriceEndDate()
    {
        return $this->getData('online_sale_price_end_date');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAfnChannel()
    {
        return (int)$this->getData('is_afn_channel') == self::IS_AFN_CHANNEL_YES;
    }

    /**
     * @return bool
     */
    public function isIsbnGeneralId()
    {
        return (int)$this->getData('is_isbn_general_id') == self::IS_ISBN_GENERAL_ID_YES;
    }

    /**
     * @return bool
     */
    public function isGeneralIdOwner()
    {
        return (int)$this->getData('is_general_id_owner') == self::IS_GENERAL_ID_OWNER_YES;
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDefectedMessages()
    {
        return $this->getSettings('defected_messages');
    }

    //########################################

    public function getSearchSettingsStatus()
    {
        return $this->getData('search_settings_status');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSearchSettingsData()
    {
        return $this->getSettings('search_settings_data');
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getGeneralIdSearchInfo()
    {
        return $this->getSettings('general_id_search_info');
    }

    //########################################

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPrice()
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getVariations(true);
            /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getPrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setCoefficient($this->getAmazonSellingFormatTemplate()->getPriceCoefficient());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getPriceVatPercent());

        return $calculator->getProductValue();
    }

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getMapPrice()
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception('There are no variations for a variation product.',
                                                     array(
                                                         'listing_product_id' => $this->getId()
                                                     ));
            }
            /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getMapPrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getMapPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getParentObject());

        return $calculator->getProductValue();
    }

    // ---------------------------------------

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSalePrice()
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception('There are no variations for a variation product.',
                                                     array(
                                                         'listing_product_id' => $this->getId()
                                                     ));
            }
            /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getSalePrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getSalePriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setIsSalePrice(true);
        $calculator->setCoefficient($this->getAmazonSellingFormatTemplate()->getSalePriceCoefficient());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getPriceVatPercent());

        return $calculator->getProductValue();
    }

    /**
     * @return array|bool
     */
    public function getSalePriceInfo()
    {
        $price = $this->getPrice();
        $salePrice = $this->getSalePrice();

        if ($salePrice <= 0 || $salePrice >= $price) {
            return false;
        }

        $startDate = $this->getSalePriceStartDate();
        $endDate = $this->getSalePriceEndDate();

        if (!$startDate || !$endDate) {
            return false;
        }

        $startDateTimestamp = strtotime($startDate);
        $endDateTimestamp = strtotime($endDate);

        $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d 00:00:00'));

        if ($currentTimestamp > $endDateTimestamp ||
            $startDateTimestamp >= $endDateTimestamp
        ) {
            return false;
        }

        return array(
            'price'      => $salePrice,
            'start_date' => $startDate,
            'end_date'   => $endDate
        );
    }

    // ---------------------------------------

    private function getSalePriceStartDate()
    {
        if ($this->getAmazonSellingFormatTemplate()->isSalePriceModeSpecial() &&
            $this->getMagentoProduct()->isGroupedType()) {
            $magentoProduct = $this->getActualMagentoProduct();
        } else if ($this->getAmazonSellingFormatTemplate()->isPriceVariationModeParent()) {
            $magentoProduct = $this->getMagentoProduct();
        } else {
            $magentoProduct = $this->getActualMagentoProduct();
        }

        $date = null;

        if ($this->getAmazonSellingFormatTemplate()->isSalePriceModeSpecial()) {
            $date = $magentoProduct->getSpecialPriceFromDate();
        } else {
            $src = $this->getAmazonSellingFormatTemplate()->getSalePriceStartDateSource();

            $date = $src['value'];

            if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Template\SellingFormat::DATE_ATTRIBUTE) {
                $date = $magentoProduct->getAttributeValue($src['attribute']);
            }
        }

        if (strtotime($date) === false) {
            return false;
        }

        return $this->getHelper('Data')->getDate($date,false,'Y-m-d 00:00:00');
    }

    private function getSalePriceEndDate()
    {
        if ($this->getAmazonSellingFormatTemplate()->isSalePriceModeSpecial() &&
            $this->getMagentoProduct()->isGroupedType()) {
            $magentoProduct = $this->getActualMagentoProduct();
        } else if ($this->getAmazonSellingFormatTemplate()->isPriceVariationModeParent()) {
            $magentoProduct = $this->getMagentoProduct();
        } else {
            $magentoProduct = $this->getActualMagentoProduct();
        }

        $date = null;

        if ($this->getAmazonSellingFormatTemplate()->isSalePriceModeSpecial()) {

            $date = $magentoProduct->getSpecialPriceToDate();

            $tempDate = new \DateTime($date, new \DateTimeZone('UTC'));
            $tempDate->modify('-1 day');
            $date = $this->getHelper('Data')->getDate($tempDate->format('U'));

        } else {
            $src = $this->getAmazonSellingFormatTemplate()->getSalePriceEndDateSource();

            $date = $src['value'];

            if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Template\SellingFormat::DATE_ATTRIBUTE) {
                $date = $magentoProduct->getAttributeValue($src['attribute']);
            }
        }

        if (strtotime($date) === false) {
            return false;
        }

        return $this->getHelper('Data')->getDate($date,false,'Y-m-d 00:00:00');
    }

    //########################################

    /**
     * @param bool $magentoMode
     * @return int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getQty($magentoMode = false)
    {
        if ($this->getVariationManager()->isPhysicalUnit() &&
            $this->getVariationManager()->getTypeModel()->isVariationProductMatched()) {

            $variations = $this->getVariations(true);
            if (count($variations) <= 0) {
                throw new \Ess\M2ePro\Model\Exception('There are no variations for a variation product.',
                                                     array(
                                                         'listing_product_id' => $this->getId()
                                                     ));
            }
            /* @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getQty($magentoMode);
        }

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\QtyCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\QtyCalculator');
        $calculator->setProduct($this->getParentObject());
        $calculator->setIsMagentoMode($magentoMode);

        return $calculator->getProductValue();
    }

    //########################################

    public function listAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $params);
    }

    public function relistAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $params);
    }

    public function reviseAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $params);
    }

    public function stopAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_STOP, $params);
    }

    public function deleteAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_DELETE, $params);
    }

    // ---------------------------------------

    protected function processDispatcher($action, array $params = array())
    {
        $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Product\Dispatcher');
        return $dispatcherObject->process($action, $this->getId(), $params);
    }

    //########################################

    public function getTrackingAttributes()
    {
        $attributes = $this->getListing()->getTrackingAttributes();

        $descriptionTemplate = $this->getDescriptionTemplate();
        if (!is_null($descriptionTemplate)) {
            $attributes = array_merge($attributes, $descriptionTemplate->getTrackingAttributes());
        }

        return array_unique($attributes);
    }

    //########################################
}