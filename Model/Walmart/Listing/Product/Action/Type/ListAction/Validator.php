<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\Validator
 */
class Validator extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Validator
{
    //########################################

    /**
     * @return bool
     */
    public function validate()
    {
        if (!$this->validateMagentoProductType()) {
            return false;
        }

        $sku = $this->getSku();
        if (empty($sku)) {
            // M2ePro\TRANSLATIONS
            // SKU is not provided. Please, check Listing Settings.
            $this->addMessage('SKU is not provided. Please, check Listing Settings.');
            return false;
        }

        if (strlen($sku) > \Ess\M2ePro\Helper\Component\Walmart::SKU_MAX_LENGTH) {
            // M2ePro\TRANSLATIONS
            // The length of SKU must be less than 50 characters.
            $this->addMessage('The length of SKU must be less than 50 characters.');
            return false;
        }

        if (!$this->validateCategory()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType() && !$this->validateParentListingProductFlags()) {
            return false;
        }

        if (!$this->getListingProduct()->isNotListed() || !$this->getListingProduct()->isListable()) {
            // M2ePro\TRANSLATIONS
            // Item is already on Walmart, or not available.
            $this->addMessage('Item is already on Walmart, or not available.');

            return false;
        }

        if ($this->getVariationManager()->isLogicalUnit()) {
            return true;
        }

        if (!$this->validateProductIds()) {
            return false;
        }

        if (!$this->validateStartEndDates()) {
            return false;
        }

        if (!$this->validatePrice()) {
            return false;
        }

        if ($this->getVariationManager()->isPhysicalUnit() && !$this->validatePhysicalUnitMatching()) {
            return false;
        }

        return true;
    }

    //########################################

    private function getSku()
    {
        if (isset($this->data['sku'])) {
            return $this->data['sku'];
        }

        $params = $this->getParams();
        if (!isset($params['sku'])) {
            return null;
        }

        return $params['sku'];
    }

    //########################################

    protected function getGtin()
    {
        $gtin = parent::getGtin();
        if ($gtin !== null) {
            return $gtin;
        }

        $helper = $this->getHelper('Component_Walmart_Configuration');

        if ($helper->isProductIdOverrideModeAll()) {
            return \Ess\M2ePro\Helper\Component\Walmart\Configuration::PRODUCT_ID_OVERRIDE_CUSTOM_CODE;
        }

        if ($helper->isGtinModeNotSet()) {
            return null;
        }

        return $this->getWalmartListingProduct()->getActualMagentoProduct()->getAttributeValue(
            $helper->getGtinCustomAttribute()
        );
    }

    protected function getUpc()
    {
        $upc = parent::getUpc();
        if ($upc !== null) {
            return $upc;
        }

        $helper = $this->getHelper('Component_Walmart_Configuration');

        if ($helper->isProductIdOverrideModeAll()) {
            return \Ess\M2ePro\Helper\Component\Walmart\Configuration::PRODUCT_ID_OVERRIDE_CUSTOM_CODE;
        }

        if ($helper->isUpcModeNotSet()) {
            return null;
        }

        return $this->getWalmartListingProduct()->getActualMagentoProduct()->getAttributeValue(
            $helper->getUpcCustomAttribute()
        );
    }

    protected function getEan()
    {
        $ean = parent::getEan();
        if ($ean !== null) {
            return $ean;
        }

        $helper = $this->getHelper('Component_Walmart_Configuration');

        if ($helper->isProductIdOverrideModeAll()) {
            return \Ess\M2ePro\Helper\Component\Walmart\Configuration::PRODUCT_ID_OVERRIDE_CUSTOM_CODE;
        }

        if ($helper->isEanModeNotSet()) {
            return null;
        }

        return $this->getWalmartListingProduct()->getActualMagentoProduct()->getAttributeValue(
            $helper->getEanCustomAttribute()
        );
    }

    protected function getIsbn()
    {
        $isbn = parent::getIsbn();
        if ($isbn !== null) {
            return $isbn;
        }

        $helper = $this->getHelper('Component_Walmart_Configuration');

        if ($helper->isProductIdOverrideModeAll()) {
            return \Ess\M2ePro\Helper\Component\Walmart\Configuration::PRODUCT_ID_OVERRIDE_CUSTOM_CODE;
        }

        if ($helper->isIsbnModeNotSet()) {
            return null;
        }

        return $this->getWalmartListingProduct()->getActualMagentoProduct()->getAttributeValue(
            $helper->getIsbnCustomAttribute()
        );
    }

    //########################################
}
