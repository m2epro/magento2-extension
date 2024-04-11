<?php

namespace Ess\M2ePro\Model\Amazon\Listing;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product $magentoProduct
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Listing $listing
     */
    private $listing = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     *
     * @return $this
     */
    public function setListing(\Ess\M2ePro\Model\Listing $listing)
    {
        $this->listing = $listing;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->listing;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    public function getAmazonListing()
    {
        return $this->getListing()->getChildObject();
    }

    //########################################

    /**
     * @return string
     */
    public function getSku()
    {
        $result = '';
        $src = $this->getAmazonListing()->getSkuSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_DEFAULT) {
            $result = $this->getMagentoProduct()->getSku();
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_PRODUCT_ID) {
            $result = $this->getMagentoProduct()->getProductId();
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);

        if (!empty($result)) {
            return $this->applySkuModification($result);
        }

        return $result;
    }

    // ---------------------------------------

    protected function applySkuModification($sku)
    {
        if ($this->getAmazonListing()->isSkuModificationModeNone()) {
            return $sku;
        }

        $source = $this->getAmazonListing()->getSkuModificationSource();

        if ($this->getAmazonListing()->isSkuModificationModePrefix()) {
            $sku = $source['value'] . $sku;
        } elseif ($this->getAmazonListing()->isSkuModificationModePostfix()) {
            $sku = $sku . $source['value'];
        } elseif ($this->getAmazonListing()->isSkuModificationModeTemplate()) {
            $sku = str_replace('%value%', $sku, $source['value']);
        }

        return $sku;
    }

    //########################################

    public function getHandlingTime(): int
    {
        $result = 0;
        $src = $this->getAmazonListing()->getHandlingTimeSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_RECOMMENDED) {
            $result = $src['value'];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        $result = (int)$result;

        if ($result <= 0) {
            return 0;
        }

        if ($result >= 30) {
            return 30;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getRestockDate()
    {
        $result = '';
        $src = $this->getAmazonListing()->getRestockDateSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_VALUE) {
            $result = $src['value'];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return trim($result);
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getCondition()
    {
        $result = '';
        $src = $this->getAmazonListing()->getConditionSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT) {
            $result = $src['value'];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return trim($result);
    }

    /**
     * @return string
     */
    public function getConditionNote()
    {
        $result = '';
        $src = $this->getAmazonListing()->getConditionNoteSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NOTE_MODE_CUSTOM_VALUE) {
            $renderer = $this->getHelper('Module_Renderer_Description');
            $result = $renderer->parseTemplate($src['value'], $this->getMagentoProduct());
        }

        return trim($result);
    }

    /**
     * @return mixed
     */
    public function getGiftWrap()
    {
        $result = null;
        $src = $this->getAmazonListing()->getGiftWrapSource();

        if ($this->getAmazonListing()->isGiftWrapModeYes()) {
            $result = true;
        }

        if ($this->getAmazonListing()->isGiftWrapModeNo()) {
            $result = false;
        }

        if ($this->getAmazonListing()->isGiftWrapModeAttribute()) {
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($src['attribute'], false);

            if ($attributeValue == $this->getHelper('Module\Translation')->__('Yes')) {
                $result = true;
            }

            if ($attributeValue == $this->getHelper('Module\Translation')->__('No')) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @return null|bool
     */
    public function getGiftMessage()
    {
        $result = null;
        $src = $this->getAmazonListing()->getGiftMessageSource();

        if ($this->getAmazonListing()->isGiftMessageModeYes()) {
            $result = true;
        }

        if ($this->getAmazonListing()->isGiftMessageModeNo()) {
            $result = false;
        }

        if ($this->getAmazonListing()->isGiftMessageModeAttribute()) {
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($src['attribute'], false);

            if ($attributeValue == $this->getHelper('Module\Translation')->__('Yes')) {
                $result = true;
            }

            if ($attributeValue == $this->getHelper('Module\Translation')->__('No')) {
                $result = false;
            }
        }

        return $result;
    }

    //########################################
}
