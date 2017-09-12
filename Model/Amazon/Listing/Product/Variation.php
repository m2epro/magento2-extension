<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

/**
 * @method \Ess\M2ePro\Model\Listing\Product\Variation getParentObject()
 */
class Variation extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Variation');
    }

    public function afterSave()
    {
        $this->getHelper('Data\Cache\Runtime')->removeTagValues(
            "listing_product_{$this->getListingProduct()->getId()}_variations"
        );

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $this->getHelper('Data\Cache\Runtime')->removeTagValues(
            "listing_product_{$this->getListingProduct()->getId()}_variations"
        );

        return parent::beforeDelete();
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
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->getParentObject()->getListingProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product
     */
    public function getAmazonListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getAmazonListingProduct()->getSellingFormatTemplate();
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
        return $this->getAmazonListingProduct()->getSynchronizationTemplate();
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
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->getAmazonListingProduct()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description
     */
    public function getAmazonDescriptionTemplate()
    {
        if (!$templateModel = $this->getDescriptionTemplate()) {
            return null;
        }

        return $templateModel->getChildObject();
    }

    //########################################

    public function getOptions($asObjects = false, array $filters = array())
    {
        return $this->getParentObject()->getOptions($asObjects,$filters);
    }

    //########################################

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSku()
    {
        $sku = '';

        // Options Models
        $options = $this->getOptions(true);

        // Configurable, Grouped product
        if ($this->getListingProduct()->getMagentoProduct()->isConfigurableType() ||
            $this->getListingProduct()->getMagentoProduct()->isGroupedType()) {

            foreach ($options as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
                $sku = $option->getChildObject()->getSku();
                break;
            }

        // Bundle product
        } else if ($this->getListingProduct()->getMagentoProduct()->isBundleType()) {

            foreach ($options as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

                if (!$option->getProductId()) {
                    continue;
                }

                $sku != '' && $sku .= '-';
                $sku .= $option->getChildObject()->getSku();
            }

        // Simple with options product
        } else if ($this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {

            foreach ($options as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
                $sku != '' && $sku .= '-';
                $tempSku = $option->getChildObject()->getSku();
                if ($tempSku == '') {
                    $sku .= $this->getHelper('Data')->convertStringToSku($option->getOption());
                } else {
                    $sku .= $tempSku;
                }
            }

            // Downloadable with separated links product
        } else if ($this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()) {

            /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

            $option = reset($options);
            $sku = $option->getMagentoProduct()->getSku().'-'
                .$this->getHelper('Data')->convertStringToSku($option->getOption());
        }

        if (!empty($sku)) {
            return $this->applySkuModification($sku);
        }

        return $sku;
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

    public function getQty($magentoMode = false)
    {
        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\QtyCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\QtyCalculator');
        $calculator->setProduct($this->getListingProduct());
        $calculator->setIsMagentoMode($magentoMode);

        return $calculator->getVariationValue($this->getParentObject());
    }

    // ---------------------------------------

    public function getRegularPrice()
    {
        if (!$this->getAmazonListingProduct()->isAllowedForRegularCustomers()) {
            return NULL;
        }

        $src = $this->getAmazonSellingFormatTemplate()->getRegularPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setCoefficient($this->getAmazonSellingFormatTemplate()->getRegularPriceCoefficient());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getRegularPriceVatPercent());
        $calculator->setPriceVariationMode($this->getAmazonSellingFormatTemplate()->getRegularPriceVariationMode());

        return $calculator->getVariationValue($this->getParentObject());
    }

    public function getRegularMapPrice()
    {
        if (!$this->getAmazonListingProduct()->isAllowedForRegularCustomers()) {
            return NULL;
        }

        $src = $this->getAmazonSellingFormatTemplate()->getRegularMapPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setPriceVariationMode($this->getAmazonSellingFormatTemplate()->getRegularPriceVariationMode());

        return $calculator->getVariationValue($this->getParentObject());
    }

    public function getRegularSalePrice()
    {
        if (!$this->getAmazonListingProduct()->isAllowedForRegularCustomers()) {
            return NULL;
        }

        $src = $this->getAmazonSellingFormatTemplate()->getRegularSalePriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setIsSalePrice(true);
        $calculator->setCoefficient($this->getAmazonSellingFormatTemplate()->getRegularSalePriceCoefficient());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getRegularPriceVatPercent());
        $calculator->setPriceVariationMode($this->getAmazonSellingFormatTemplate()->getRegularPriceVariationMode());

        return $calculator->getVariationValue($this->getParentObject());
    }

    // ---------------------------------------

    public function getBusinessPrice()
    {
        if (!$this->getAmazonListingProduct()->isAllowedForBusinessCustomers()) {
            return NULL;
        }

        $src = $this->getAmazonSellingFormatTemplate()->getBusinessPriceSource();

        /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setCoefficient($this->getAmazonSellingFormatTemplate()->getBusinessPriceCoefficient());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getBusinessPriceVatPercent());
        $calculator->setPriceVariationMode($this->getAmazonSellingFormatTemplate()->getBusinessPriceVariationMode());

        return $calculator->getVariationValue($this->getParentObject());
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessDiscounts()
    {
        if (!$this->getAmazonListingProduct()->isAllowedForBusinessCustomers()) {
            return NULL;
        }

        if ($this->getAmazonSellingFormatTemplate()->isBusinessDiscountsModeNone()) {
            return array();
        }

        if ($this->getAmazonSellingFormatTemplate()->isBusinessDiscountsModeTier()) {
            $src = $this->getAmazonSellingFormatTemplate()->getBusinessDiscountsSource();

            $storeId = $this->getListing()->getStoreId();
            $src['tier_website_id'] = $this->getHelper('Magento\Store')->getWebsite($storeId)->getId();

            /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
            $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
            $calculator->setSource($src)->setProduct($this->getListingProduct());
            $calculator->setSourceModeMapping(array(
                PriceCalculator::MODE_TIER
                    => \Ess\M2ePro\Model\Amazon\Template\SellingFormat::BUSINESS_DISCOUNTS_MODE_TIER,
            ));
            $calculator->setCoefficient($this->getAmazonSellingFormatTemplate()->getBusinessDiscountsTierCoefficient());
            $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getBusinessPriceVatPercent());
            $calculator->setPriceVariationMode(
                $this->getAmazonSellingFormatTemplate()->getBusinessPriceVariationMode()
            );

            return array_slice(
                $calculator->getVariationValue($this->getParentObject()), 0,
                \Ess\M2ePro\Model\Amazon\Listing\Product::BUSINESS_DISCOUNTS_MAX_RULES_COUNT_ALLOWED,
                true
            );
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount[] $businessDiscounts */
        $businessDiscounts = $this->getAmazonSellingFormatTemplate()->getBusinessDiscounts(true);
        if (empty($businessDiscounts)) {
            return array();
        }

        $resultValue = array();

        foreach ($businessDiscounts as $businessDiscount) {
            /** @var $calculator \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator */
            $calculator = $this->modelFactory->getObject('Amazon\Listing\Product\PriceCalculator');
            $calculator->setSource($businessDiscount->getSource())->setProduct($this->getListingProduct());
            $calculator->setSourceModeMapping(array(
                PriceCalculator::MODE_PRODUCT
                    => \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount::MODE_PRODUCT,
                PriceCalculator::MODE_SPECIAL
                    => \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount::MODE_SPECIAL,
                PriceCalculator::MODE_ATTRIBUTE
                    => \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount::MODE_ATTRIBUTE,
            ));
            $calculator->setCoefficient($businessDiscount->getCoefficient());
            $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getBusinessPriceVatPercent());
            $calculator->setPriceVariationMode(
                $this->getAmazonSellingFormatTemplate()->getBusinessPriceVariationMode()
            );

            $resultValue[$businessDiscount->getQty()] = $calculator->getVariationValue($this->getParentObject());

            $rulesMaxCount = \Ess\M2ePro\Model\Amazon\Listing\Product::BUSINESS_DISCOUNTS_MAX_RULES_COUNT_ALLOWED;

            if (count($resultValue) >= $rulesMaxCount) {
                break;
            }
        }

        return $resultValue;
    }

    //########################################
}