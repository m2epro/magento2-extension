<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Product\Variation\Option getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Option
 */
class Option extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Variation\Option');
    }

    //########################################

    protected function _afterSave()
    {
        $listingProductId = $this->getListingProduct()->getId();
        $variationId = $this->getListingProductVariation()->getId();

        $this->getHelper('Data_Cache_Permanent')->removeTagValues(
            "listing_product_{$listingProductId}_variation_{$variationId}_options"
        );

        return parent::_afterSave();
    }

    protected function _beforeDelete()
    {
        $listingProductId = $this->getListingProduct()->getId();
        $variationId = $this->getListingProductVariation()->getId();

        $this->getHelper('Data_Cache_Permanent')->removeTagValues(
            "listing_product_{$listingProductId}_variation_{$variationId}_options"
        );

        return parent::_beforeDelete();
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
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getParentObject()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    public function getWalmartListing()
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
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    public function getWalmartListingProduct()
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
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation
     */
    public function getWalmartListingProductVariation()
    {
        return $this->getListingProductVariation()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getWalmartListingProductVariation()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getWalmartSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getWalmartListingProductVariation()->getSynchronizationTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Synchronization
     */
    public function getWalmartSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->getWalmartListingProductVariation()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Description
     */
    public function getWalmartDescriptionTemplate()
    {
        if (!$templateModel = $this->getDescriptionTemplate()) {
            return null;
        }

        return $templateModel->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    //########################################

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getSku()
    {
        $helper = $this->getHelper('Component_Walmart_Configuration');

        if ($helper->isSkuModeProductId()) {
            return (string)$this->getParentObject()->getProductId();
        }

        if ($helper->isSkuModeCustomAttribute()) {
            return trim($this->getMagentoProduct()->getAttributeValue($helper->getSkuCustomAttribute()));
        }

        if (!$this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {
            return trim($this->getMagentoProduct()->getSku());
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

            $attribute = strtolower($this->getParentObject()->getAttribute());

            if (strtolower($tempAttribute->getData('default_title')) != $attribute &&
                strtolower($tempAttribute->getData('store_title')) != $attribute &&
                strtolower($tempAttribute->getData('title')) != $attribute) {
                continue;
            }

            foreach ($tempAttribute->getValues() as $tempOption) {
                $option = strtolower($this->getParentObject()->getOption());

                if (strtolower($tempOption->getData('default_title')) != $option &&
                    strtolower($tempOption->getData('store_title')) != $option &&
                    strtolower($tempOption->getData('title')) != $option) {
                    continue;
                }

                if ($tempOption->getData('sku') !== null &&
                    $tempOption->getData('sku') !== false) {
                    $tempSku = $tempOption->getData('sku');
                }

                break 2;
            }
        }

        return trim($tempSku);
    }

    //########################################
}
