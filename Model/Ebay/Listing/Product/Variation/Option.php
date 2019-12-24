<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Product\Variation\Option getParentObject()
 */
namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Option
 */
class Option extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Variation\Option');
    }

    //########################################

    public function afterSave()
    {
        $listingProductId = $this->getListingProduct()->getId();
        $variationId      = $this->getListingProductVariation()->getId();

        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$listingProductId}_variation_{$variationId}_options"
        );

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $listingProductId = $this->getListingProduct()->getId();
        $variationId      = $this->getListingProductVariation()->getId();

        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$listingProductId}_variation_{$variationId}_options"
        );

        return parent::beforeDelete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getParentObject()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing
     */
    public function getEbayListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->getParentObject()->getListingProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product
     */
    public function getEbayListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Variation
     */
    public function getListingProductVariation()
    {
        return $this->getParentObject()->getListingProductVariation();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Variation
     */
    public function getEbayListingProductVariation()
    {
        return $this->getListingProductVariation()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    public function getEbayAccount()
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
     * @return \Ess\M2ePro\Model\Ebay\Marketplace
     */
    public function getEbayMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getEbayListingProductVariation()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\SellingFormat
     */
    public function getEbaySellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getEbayListingProductVariation()->getSynchronizationTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Synchronization
     */
    public function getEbaySynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->getEbayListingProductVariation()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description
     */
    public function getEbayDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    public function getPaymentTemplate()
    {
        return $this->getEbayListingProductVariation()->getPaymentTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    public function getReturnTemplate()
    {
        return $this->getEbayListingProductVariation()->getReturnTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    public function getShippingTemplate()
    {
        return $this->getEbayListingProductVariation()->getShippingTemplate();
    }

    //########################################

    public function getSku()
    {
        if (!$this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {
            return $this->getMagentoProduct()->getSku();
        }

        $tempSku = '';

        $simpleAttributes = $this->getListingProduct()->getMagentoProduct()->getProduct()->getOptions();

        foreach ($simpleAttributes as $tempAttribute) {
            if (!(bool)(int)$tempAttribute->getData('is_require')) {
                continue;
            }

            if (!in_array($tempAttribute->getType(), ['drop_down', 'radio', 'multiple', 'checkbox'])) {
                continue;
            }

            $attributeName = strtolower($this->getParentObject()->getAttribute());

            $tempAttributeTitles = [
                $tempAttribute->getData('default_title'),
                $tempAttribute->getData('store_title'),
                $tempAttribute->getData('title')
            ];
            $tempAttributeTitles = array_map('strtolower', array_filter($tempAttributeTitles));
            $tempAttributeTitles = $this->prepareAttributeTitles($tempAttributeTitles);

            if (!in_array($attributeName, $tempAttributeTitles)) {
                continue;
            }

            foreach ($tempAttribute->getValues() as $tempOption) {
                $optionName = strtolower($this->getParentObject()->getOption());

                $tempOptionTitles = [
                    $tempOption->getData('default_title'),
                    $tempOption->getData('store_title'),
                    $tempOption->getData('title')
                ];
                $tempOptionTitles = array_map('strtolower', array_filter($tempOptionTitles));
                $tempOptionTitles = $this->prepareOptionTitles($tempOptionTitles);

                if (!in_array($optionName, $tempOptionTitles)) {
                    continue;
                }

                if ($tempOption->getData('sku') !== null && $tempOption->getData('sku') !== false) {
                    $tempSku = $tempOption->getData('sku');
                }

                break 2;
            }
        }

        return trim($tempSku);
    }

    //########################################

    protected function prepareOptionTitles($optionTitles)
    {
        foreach ($optionTitles as &$optionTitle) {
            $optionTitle = trim($this->getHelper('Data')->reduceWordsInString(
                $optionTitle,
                \Ess\M2ePro\Helper\Component\Ebay::MAX_LENGTH_FOR_OPTION_VALUE
            ));
        }

        return $optionTitles;
    }

    protected function prepareAttributeTitles($attributeTitles)
    {
        foreach ($attributeTitles as &$attributeTitle) {
            $attributeTitle = trim($attributeTitle);
        }

        return $attributeTitles;
    }

    //########################################
}
