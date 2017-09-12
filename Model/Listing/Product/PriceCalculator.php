<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product;

use Ess\M2ePro\Helper\Magento\Attribute;
use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Listing\Product as ListingProduct;
use Ess\M2ePro\Model\Magento\Product;
use Ess\M2ePro\Model\Magento\Product\Cache;
use Ess\M2ePro\Model\Listing\Product\Variation;
use Ess\M2ePro\Model\Template\SellingFormat;

abstract class PriceCalculator extends AbstractModel
{
    const MODE_NONE      = 0;
    const MODE_PRODUCT   = 1;
    const MODE_SPECIAL   = 2;
    const MODE_ATTRIBUTE = 3;
    const MODE_TIER      = 4;

    //########################################

    /**
     * @var null|array
     */
    private $source = NULL;

    /**
     * @var array
     */
    private $sourceModeMapping = array(
        self::MODE_NONE      => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
        self::MODE_PRODUCT   => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
        self::MODE_SPECIAL   => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL,
        self::MODE_ATTRIBUTE => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
        self::MODE_TIER      => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_TIER,
    );

    /**
     * @var null|ListingProduct
     */
    private $product = NULL;

    /**
     * @var null|string
     */
    private $coefficient = NULL;

    /**
     * @var null|float
     */
    private $vatPercent = NULL;

    /**
     * @var null|int
     */
    private $priceVariationMode = NULL;

    /**
     * @var null|float
     */
    private $productValueCache = NULL;

    //########################################

    /**
     * @param array $source
     * @return PriceCalculator
     */
    public function setSource(array $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @param null|string $key
     * @return array|mixed
     * @throws Logic
     */
    protected function getSource($key = NULL)
    {
        if (empty($this->source)) {
            throw new Logic('Initialize all parameters first.');
        }

        if (is_null($key)) {
            return $this->source;
        }

        return isset($this->source[$key]) ? $this->source[$key] : NULL;
    }

    // ---------------------------------------

    public function setSourceModeMapping(array $mapping)
    {
        $this->sourceModeMapping = $mapping;
        return $this;
    }

    protected function getSourceMode()
    {
        if (!in_array($this->getSource('mode'), $this->sourceModeMapping)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Unknown source mode.');
        }

        return array_search($this->getSource('mode'), $this->sourceModeMapping);
    }

    // ---------------------------------------

    /**
     * @param ListingProduct $product
     * @return PriceCalculator
     */
    public function setProduct(ListingProduct $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return ListingProduct
     * @throws Logic
     */
    protected function getProduct()
    {
        if (is_null($this->product)) {
            throw new Logic('Initialize all parameters first.');
        }

        return $this->product;
    }

    // ---------------------------------------

    /**
     * @param string $value
     * @return PriceCalculator
     */
    public function setCoefficient($value)
    {
        $this->coefficient = $value;
        return $this;
    }

    /**
     * @return string
     */
    protected function getCoefficient()
    {
        return $this->coefficient;
    }

    // ---------------------------------------

    public function setVatPercent($value)
    {
        $this->vatPercent = $value;
        return $this;
    }

    /**
     * @return float|null
     */
    protected function getVatPercent()
    {
        return $this->vatPercent;
    }

    // ---------------------------------------

    /**
     * @param $mode
     * @return PriceCalculator
     */
    public function setPriceVariationMode($mode)
    {
        $this->priceVariationMode = $mode;
        return $this;
    }

    /**
     * @return int|null
     */
    protected function getPriceVariationMode()
    {
        return $this->priceVariationMode;
    }

    /**
     * @return bool
     */
    abstract protected function isPriceVariationModeParent();

    /**
     * @return bool
     */
    abstract protected function isPriceVariationModeChildren();

    //########################################

    /**
     * @return Listing
     */
    protected function getListing()
    {
        return $this->getProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
     */
    protected function getComponentListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
     */
    protected function getComponentProduct()
    {
        return $this->getProduct()->getChildObject();
    }

    /**
     * @return Cache
     */
    protected function getMagentoProduct()
    {
        return $this->getProduct()->getMagentoProduct();
    }

    //########################################

    public function getProductValue()
    {
        if ($this->isSourceModeNone()) {
            return 0;
        }

        $value = $this->getProductBaseValue();
        return $this->prepareFinalValue($value);
    }

    public function getVariationValue(Variation $variation)
    {
        if ($this->isSourceModeNone()) {
            return 0;
        }

        $value = $this->getVariationBaseValue($variation);
        return $this->prepareFinalValue($value);
    }

    //########################################

    protected function getProductBaseValue()
    {
        if (!is_null($this->productValueCache)) {
            return $this->productValueCache;
        }

        if ($this->isSourceModeProduct()) {

            if ($this->getMagentoProduct()->isConfigurableType()) {

                $value = $this->getConfigurableProductValue($this->getMagentoProduct());

            } else if ($this->getMagentoProduct()->isGroupedType()) {

                $value = $this->getGroupedProductValue($this->getMagentoProduct());

            } else if ($this->getMagentoProduct()->isBundleType() &&
                $this->getMagentoProduct()->isBundlePriceTypeDynamic()) {

                $value = $this->getBundleProductDynamicValue($this->getMagentoProduct());

            } else {
                $value = $this->getExistedProductValue($this->getMagentoProduct());
            }

        } elseif ($this->isSourceModeSpecial()) {

            if ($this->getMagentoProduct()->isConfigurableType()) {

                $value = $this->getConfigurableProductValue($this->getMagentoProduct());

            } else if ($this->getMagentoProduct()->isGroupedType()) {

                $value = $this->getGroupedProductValue($this->getMagentoProduct());

            } else if ($this->getMagentoProduct()->isBundleType() &&
                $this->getMagentoProduct()->isBundlePriceTypeDynamic()) {

                $value = $this->getBundleProductDynamicSpecialValue($this->getMagentoProduct());

            } else {
                $value = $this->getExistedProductSpecialValue($this->getMagentoProduct());
            }

        } elseif ($this->isSourceModeAttribute()) {

            if ($this->getMagentoProduct()->isConfigurableType()) {

                if ($this->getSource('attribute') == Attribute::PRICE_CODE ||
                    $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE) {
                    $value = $this->getConfigurableProductValue($this->getMagentoProduct());
                } else {
                    $value = $this->getHelper('Magento\Attribute')->convertAttributeTypePriceFromStoreToMarketplace(
                        $this->getMagentoProduct(),
                        $this->getSource('attribute'),
                        $this->getCurrencyForPriceConvert(),
                        $this->getListing()->getStoreId()
                    );
                }

            } else if ($this->getMagentoProduct()->isGroupedType()) {

                if ($this->getSource('attribute') == Attribute::PRICE_CODE ||
                    $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE) {
                    $value = $this->getGroupedProductValue($this->getMagentoProduct());
                } else {
                    $value = $this->getHelper('Magento\Attribute')->convertAttributeTypePriceFromStoreToMarketplace(
                        $this->getMagentoProduct(),
                        $this->getSource('attribute'),
                        $this->getCurrencyForPriceConvert(),
                        $this->getListing()->getStoreId()
                    );
                }

            } else if ($this->getMagentoProduct()->isBundleType() &&
                       ($this->getMagentoProduct()->isBundlePriceTypeDynamic() ||
                        ($this->getMagentoProduct()->isBundlePriceTypeFixed() &&
                         $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE))) {

                if ($this->getMagentoProduct()->isBundlePriceTypeFixed() &&
                    $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE) {

                    $value = $this->getExistedProductSpecialValue($this->getMagentoProduct());
                } else {
                    if ($this->getSource('attribute') == Attribute::PRICE_CODE) {
                        $value = $this->getBundleProductDynamicValue($this->getMagentoProduct());
                    } else if ($this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE) {
                        $value = $this->getBundleProductDynamicSpecialValue($this->getMagentoProduct());
                    } else {
                        $value = $this->getHelper('Magento\Attribute')->convertAttributeTypePriceFromStoreToMarketplace(
                            $this->getMagentoProduct(),
                            $this->getSource('attribute'),
                            $this->getCurrencyForPriceConvert(),
                            $this->getListing()->getStoreId()
                        );
                    }
                }

            } else {
                $value = $this->getHelper('Magento\Attribute')->convertAttributeTypePriceFromStoreToMarketplace(
                    $this->getMagentoProduct(),
                    $this->getSource('attribute'),
                    $this->getCurrencyForPriceConvert(),
                    $this->getListing()->getStoreId()
                );
            }

        } elseif ($this->isSourceModeTier()) {

            if ($this->getMagentoProduct()->isGroupedType()) {

                $value = $this->getGroupedTierValue($this->getMagentoProduct());

            } else if ($this->getMagentoProduct()->isBundleType()) {

                if ($this->getMagentoProduct()->isBundlePriceTypeDynamic()) {
                    $value = $this->getBundleTierDynamicValue($this->getMagentoProduct());
                } else {
                    $value = $this->getBundleTierFixedValue($this->getMagentoProduct());
                }

            } else {
                $value = $this->getExistedProductTierValue($this->getMagentoProduct());
            }

        } else {
            throw new Logic('Unknown Mode in Database.');
        }

        return $this->productValueCache = $value;
    }

    protected function getVariationBaseValue(Variation $variation)
    {
        if ($this->getMagentoProduct()->isConfigurableType()) {
            $value = $this->getConfigurableVariationValue($variation);
        } else if ($this->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {
            $value = $this->getSimpleWithCustomOptionsVariationValue($variation);
        } else if ($this->getMagentoProduct()->isBundleType()) {
            $value = $this->getBundleVariationValue($variation);
        } else if ($this->getMagentoProduct()->isGroupedType()) {
            $value = $this->getGroupedVariationValue($variation);
        } else if ($this->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()) {
            $value = $this->getDownloadableWithSeparatedLinksVariationValue($variation);
        } else {
            throw new Logic('Unknown Product type.',
                array(
                    'listing_product_id' => $this->getProduct()->getId(),
                    'product_id' => $this->getMagentoProduct()->getProductId(),
                    'type'       => $this->getMagentoProduct()->getTypeId()
                ));
        }

        return $value;
    }

    protected function getOptionBaseValue(Variation\Option $option)
    {
        if ($this->isSourceModeProduct()) {
            $value = $this->getExistedProductValue($option->getMagentoProduct());
        } elseif ($this->isSourceModeSpecial()) {
            $value = $this->getExistedProductSpecialValue($option->getMagentoProduct());
        } elseif ($this->isSourceModeAttribute()) {
            $value = $this->getHelper('Magento\Attribute')->convertAttributeTypePriceFromStoreToMarketplace(
                $option->getMagentoProduct(),
                $this->getSource('attribute'),
                $this->getCurrencyForPriceConvert(),
                $this->getListing()->getStoreId()
            );
        } elseif ($this->isSourceModeTier()) {
            $value = $this->getExistedProductTierValue($option->getMagentoProduct());
        } else {
            throw new Logic('Unknown Mode in Database.');
        }

        return $value;
    }

    //########################################

    protected function getConfigurableVariationValue(Variation $variation)
    {
        $options = $variation->getOptions(true);
        return $this->getOptionBaseValue(reset($options));
    }

    protected function getSimpleWithCustomOptionsVariationValue(Variation $variation)
    {
        $value = $this->getProductBaseValue();
        return $this->applyAdditionalOptionValuesModifications($variation, $value);
    }

    protected function getBundleVariationValue(Variation $variation)
    {
        if ($this->isPriceVariationModeChildren()) {

            $value = 0;

            foreach ($variation->getOptions(true) as $option) {
                if (!$option->getProductId()) {
                    continue;
                }

                if ($this->isSourceModeTier()) {
                    $value += $this->getExistedProductValue($option->getMagentoProduct());
                } else {
                    $value += $this->getOptionBaseValue($option);
                }
            }

            if ($this->isSourceModeTier()) {
                return $this->calculateBundleTierValue($this->getMagentoProduct(), $value);
            }

            return $value;
        }

        if ($this->getMagentoProduct()->isBundlePriceTypeFixed() ||
            ($this->isSourceModeAttribute() &&
             $this->getSource('attribute') != Attribute::PRICE_CODE &&
             $this->getSource('attribute') != Attribute::SPECIAL_PRICE_CODE)) {

            $value = $this->getProductBaseValue();

            if ($this->isSourceModeTier()) {
                return $this->applyAdditionalOptionValuesModifications($variation, $value);
            }

        } else {

            $value = 0;

            foreach ($variation->getOptions(true) as $option) {
                if (!$option->getProductId()) {
                    continue;
                }

                $tempValue = (float)$option->getMagentoProduct()->getSpecialPrice();
                $tempValue <= 0 && $tempValue = (float)$option->getMagentoProduct()->getPrice();

                $value += $tempValue;
            }

            if ($this->isSourceModeSpecial() &&
                $value > 0 && $this->getMagentoProduct()->isSpecialPriceActual()) {

                $percent = (double)$this->getMagentoProduct()->getProduct()->getSpecialPrice();
                $value = round((($value * $percent) / 100), 2);
            }

            if ($this->isSourceModeAttribute()) {

                $isConversionEnabled = (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
                    '/magento/attribute/', 'price_type_converting'
                );

                if ($isConversionEnabled &&
                    ($this->getSource('attribute') == Attribute::PRICE_CODE ||
                        $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE)
                ) {
                    $value = $this->convertValueFromStoreToMarketplace($value);
                }

            } else {

                $value = $this->convertValueFromStoreToMarketplace($value);

            }
        }

        if ($this->isSourceModeTier()) {
            $value = $this->calculateBundleTierValue($this->getMagentoProduct(), $value);
        }

        return $this->applyAdditionalOptionValuesModifications($variation, $value);
    }

    protected function getGroupedVariationValue(Variation $variation)
    {
        $options = $variation->getOptions(true);
        return $this->getOptionBaseValue(reset($options));
    }

    protected function getDownloadableWithSeparatedLinksVariationValue(Variation $variation)
    {
        $value = $this->getProductBaseValue();
        return $this->applyAdditionalOptionValuesModifications($variation, $value);
    }

    //########################################

    protected function applyAdditionalOptionValuesModifications(Variation $variation, $value)
    {
        foreach ($variation->getOptions(true) as $option) {

            $additionalValue = 0;

            if ($this->getMagentoProduct()->isSimpleType()) {
                $additionalValue = $this->getSimpleWithCustomOptionsAdditionalOptionValue($option);
            } else if ($this->getMagentoProduct()->isBundleType() && $option->getProductId()) {
                $additionalValue = $this->getBundleAdditionalOptionValue($option);
            } else if ($this->getMagentoProduct()->isDownloadableType()) {
                $additionalValue = $this->getDownloadableWithSeparatedLinksAdditionalOptionValue($option);
            }

            if (!$this->isSourceModeTier()) {
                $value += $additionalValue;
                continue;
            }

            foreach ($value as $key => &$item) {
                $item += is_array($additionalValue) ? $additionalValue[$key] : $additionalValue;
            }
        }

        return $value;
    }

    // ---------------------------------------

    protected function getSimpleWithCustomOptionsAdditionalOptionValue(Variation\Option $option)
    {
        $value = 0;

        $attributeName = strtolower($option->getAttribute());
        $optionName = strtolower($option->getOption());

        $simpleAttributes = $this->getMagentoProduct()->getProduct()->getOptions();

        foreach ($simpleAttributes as $tempAttribute) {

            if (!(bool)(int)$tempAttribute->getData('is_require')) {
                continue;
            }

            if (!in_array($tempAttribute->getType(), array('drop_down', 'radio', 'multiple', 'checkbox'))) {
                continue;
            }

            $tempAttributeTitles = array(
                $tempAttribute->getData('default_title'),
                $tempAttribute->getData('store_title'),
                $tempAttribute->getData('title')
            );

            $tempAttributeTitles = array_map('strtolower', array_filter($tempAttributeTitles));

            if (!in_array($attributeName, $tempAttributeTitles)) {
                continue;
            }

            foreach ($tempAttribute->getValues() as $tempOption) {

                $tempOptionTitles = array(
                    $tempOption->getData('default_title'),
                    $tempOption->getData('store_title'),
                    $tempOption->getData('title')
                );

                $tempOptionTitles = array_map('strtolower', array_filter($tempOptionTitles));
                $tempOptionTitles = $this->prepareOptionTitles($tempOptionTitles);

                if (!in_array($optionName, $tempOptionTitles)) {
                    continue;
                }

                if (!is_null($tempOption->getData('price_type')) &&
                    $tempOption->getData('price_type') !== false) {

                    switch ($tempOption->getData('price_type')) {
                        case 'percent':

                            if ($this->isSourceModeTier()) {

                                $value = $this->getProductBaseValue();
                                foreach ($value as &$item) {
                                    $item = ($item * (float)$tempOption->getData('price')) / 100;
                                }

                            } else {
                                $value = ($this->getProductBaseValue() * (float)$tempOption->getData('price')) / 100;
                            }

                            break;
                        case 'fixed':
                            $value = (float)$tempOption->getData('price');
                            $value = $this->convertValueFromStoreToMarketplace($value);
                            break;
                    }
                }

                break 2;
            }
        }

        return $value;
    }

    protected function getBundleAdditionalOptionValue(Variation\Option $option)
    {
        $value = 0;

        if ($this->getMagentoProduct()->isBundlePriceTypeDynamic()) {
            return $value;
        }

        $magentoProduct = $this->getMagentoProduct();
        $product = $magentoProduct->getProduct();
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();
        $bundleAttributes = $productTypeInstance->getOptionsCollection($product);

        $attributeName = strtolower($option->getAttribute());

        foreach ($bundleAttributes as $tempAttribute) {

            if (!(bool)(int)$tempAttribute->getData('required')) {
                continue;
            }

            if ((is_null($tempAttribute->getData('title')) ||
                    strtolower($tempAttribute->getData('title')) != $attributeName) &&
                (is_null($tempAttribute->getData('default_title')) ||
                    strtolower($tempAttribute->getData('default_title')) != $attributeName)) {
                continue;
            }

            $tempOptions = $productTypeInstance
                ->getSelectionsCollection(array(0 => $tempAttribute->getId()), $product)
                ->getItems();

            foreach ($tempOptions as $tempOption) {

                if ((int)$tempOption->getId() != $option->getProductId()) {
                    continue;
                }

                if ((bool)(int)$tempOption->getData('selection_price_type')) {

                    if ($this->isSourceModeTier()) {

                        $value = $this->getProductBaseValue();
                        foreach ($value as &$item) {
                            $item = ($item * (float)$tempOption->getData('selection_price_value')) / 100;
                        }

                    } else {

                        $value = ($this->getProductBaseValue() * (float)$tempOption->getData('selection_price_value'))
                                 / 100;
                    }

                } else {

                    $value = (float)$tempOption->getData('selection_price_value');

                    if (($this->isSourceModeSpecial() || $this->isSourceModeAttribute() &&
                        $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE) &&
                        $this->getMagentoProduct()->isSpecialPriceActual()
                    ) {
                        $value = ($value * $product->getSpecialPrice()) / 100;
                    }

                    if ($this->isSourceModeTier()) {
                        $value = $this->calculateBundleTierValue($magentoProduct, $value);

                        foreach ($value as &$item) {
                            $item = $this->convertValueFromStoreToMarketplace($item);
                        }
                    } else {
                        $value = $this->convertValueFromStoreToMarketplace($value);
                    }
                }

                break 2;
            }
        }

        return $value;
    }

    protected function getDownloadableWithSeparatedLinksAdditionalOptionValue(Variation\Option $option)
    {
        $value = 0;

        $optionName = strtolower($option->getOption());

        /** @var \Magento\Downloadable\Model\Link[] $links */
        $links = $this->getMagentoProduct()->getTypeInstance()->getLinks(
            $this->getMagentoProduct()->getProduct()
        );

        foreach ($links as $link) {

            $tempLinkTitles = array(
                $link->getStoreTitle(),
                $link->getDefaultTitle(),
            );

            $tempLinkTitles = array_map('strtolower', array_filter($tempLinkTitles));
            $tempLinkTitles = $this->prepareOptionTitles($tempLinkTitles);

            if (!in_array($optionName, $tempLinkTitles)) {
                continue;
            }

            $value = (float)$link->getPrice();
            $value = $this->convertValueFromStoreToMarketplace($value);

            break;
        }

        return $value;
    }

    //########################################

    protected function getExistedProductValue(Product $product)
    {
        $value = $product->getPrice();
        return $this->convertValueFromStoreToMarketplace($value);
    }

    protected function getExistedProductSpecialValue(Product $product)
    {
        $value = (float)$product->getSpecialPrice();

        if ($value <= 0) {
            return $this->getExistedProductValue($product);
        }

        return $this->convertValueFromStoreToMarketplace($value);
    }

    protected function getExistedProductTierValue(Product $product)
    {
        $tierPrice = $product->getTierPrice(
            $this->getSource('tier_website_id'), $this->getSource('tier_customer_group_id')
        );

        foreach ($tierPrice as $qty => $value) {
            $tierPrice[$qty] = $this->convertValueFromStoreToMarketplace($value);
        }

        return $tierPrice;
    }

    // ---------------------------------------

    protected function getConfigurableProductValue(Product $product)
    {
        $value = 0;

        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $product->getTypeInstance();

        foreach ($productTypeInstance->getUsedProducts($product->getProduct()) as $childProduct) {

            /** @var $childProduct Product */
            $childProduct = $this->modelFactory->getObject('Magento\Product')->setProduct($childProduct);

            $variationValue = (float)$childProduct->getSpecialPrice();
            $variationValue <= 0 && $variationValue = (float)$childProduct->getPrice();

            if ($variationValue < $value || $value == 0) {
                $value = $variationValue;
            }
        }

        return $this->convertValueFromStoreToMarketplace($value);
    }

    protected function getGroupedProductValue(Product $product)
    {
        $value = 0;

        /** @var $productTypeInstance \Magento\GroupedProduct\Model\Product\Type\Grouped */
        $productTypeInstance = $product->getTypeInstance();

        foreach ($productTypeInstance->getAssociatedProducts($product->getProduct()) as $childProduct) {

            /** @var $childProduct Product */
            $childProduct = $this->modelFactory->getObject('Magento\Product')->setProduct($childProduct);

            $variationValue = (float)$childProduct->getSpecialPrice();
            $variationValue <= 0 && $variationValue = (float)$childProduct->getPrice();

            if ($variationValue < $value || $value == 0) {
                $value = $variationValue;
            }
        }

        if ($this->isSourceModeAttribute()) {
            $isConvertEnabled = (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/magento/attribute/', 'price_type_converting'
            );

            if ($isConvertEnabled &&
                ($this->getSource('attribute') == Attribute::PRICE_CODE ||
                 $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE)
            ) {
                return $this->convertValueFromStoreToMarketplace($value);
            }

            return $value;
        }

        return $this->convertValueFromStoreToMarketplace($value);
    }

    protected function getBundleProductDynamicValue(Product $product)
    {
        $value = 0;

        $variationsData = $product->getVariationInstance()->getVariationsTypeStandard();

        foreach ($variationsData['variations'] as $variation) {

            $variationValue = 0;

            foreach ($variation as $option) {

                /** @var $childProduct Product */
                $childProduct = $this->modelFactory->getObject('Magento\Product')->setProductId($option['product_id']);

                $optionValue = (float)$childProduct->getSpecialPrice();
                $optionValue <= 0 && $optionValue = (float)$childProduct->getPrice();

                $variationValue += $optionValue;
            }

            if ($variationValue < $value || $value == 0) {
                $value = $variationValue;
            }
        }

        if ($this->isSourceModeAttribute()) {
            $isConvertEnabled = (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/magento/attribute/', 'price_type_converting'
            );

            if ($isConvertEnabled &&
                ($this->getSource('attribute') == Attribute::PRICE_CODE ||
                    $this->getSource('attribute') == Attribute::SPECIAL_PRICE_CODE)
            ) {
                return $this->convertValueFromStoreToMarketplace($value);
            }

            return $value;
        }

        return $this->convertValueFromStoreToMarketplace($value);
    }

    protected function getBundleProductDynamicSpecialValue(Product $product)
    {
        $value = $this->getBundleProductDynamicValue($product);

        if ($value <= 0 || !$product->isSpecialPriceActual()) {
            return $value;
        }

        $percent = (double)$product->getProduct()->getSpecialPrice();
        return round((($value * $percent) / 100), 2);
    }

    protected function getGroupedTierValue(\Ess\M2ePro\Model\Magento\Product $product)
    {
        /** @var $productTypeInstance \Magento\GroupedProduct\Model\Product\Type\Grouped */
        $productTypeInstance = $product->getTypeInstance();

        $lowestVariationValue = NULL;
        $resultChildProduct   = NULL;

        foreach ($productTypeInstance->getAssociatedProducts($product->getProduct()) as $childProduct) {

            /** @var $childProduct \Ess\M2ePro\Model\Magento\Product */
            $childProduct = $this->modelFactory->getObject('Magento\Product')->setProduct($childProduct);

            $variationValue = (float)$childProduct->getSpecialPrice();
            $variationValue <= 0 && $variationValue = (float)$childProduct->getPrice();

            if ($variationValue < $lowestVariationValue || is_null($lowestVariationValue)) {
                $lowestVariationValue = $variationValue;
                $resultChildProduct   = $childProduct;
            }
        }

        if (is_null($resultChildProduct)) {
            return NULL;
        }

        return $this->getExistedProductTierValue($resultChildProduct);
    }

    protected function getBundleTierFixedValue(\Ess\M2ePro\Model\Magento\Product $product)
    {
        return $this->calculateBundleTierValue($product, $this->getExistedProductValue($product));
    }

    protected function getBundleTierDynamicValue(\Ess\M2ePro\Model\Magento\Product $product)
    {
        return $this->calculateBundleTierValue($product, $this->getBundleProductDynamicValue($product));
    }

    //########################################

    protected function prepareFinalValue($value)
    {
        if (!is_null($this->getCoefficient())) {
            if (!$this->isSourceModeTier()) {
                $value = $this->modifyValueByCoefficient($value);
            } else {
                foreach ($value as $qty => $price) {
                    $value[$qty] = $this->modifyValueByCoefficient($price);
                }
            }
        }

        if (!is_null($this->getVatPercent())) {
            if (!$this->isSourceModeTier()) {
                $value = $this->increaseValueByVatPercent($value);
            } else {
                foreach ($value as $qty => $price) {
                    $value[$qty] = $this->increaseValueByVatPercent($price);
                }
            }
        }

        if (!$this->isSourceModeTier()) {
            $value < 0 && $value = 0;
            $value = round($value, 2);
        } else {
            foreach ($value as $qty => $price) {
                $price < 0 && $value[$qty] = 0;
                $value[$qty] = round($value[$qty], 2);
            }
        }

        return $value;
    }

    // ---------------------------------------

    protected function modifyValueByCoefficient($value)
    {
        if ($value <= 0) {
            return $value;
        }

        $coefficient = $this->getCoefficient();

        if (is_string($coefficient)) {
            $coefficient = trim($coefficient);
        }

        if (!$coefficient) {
            return $value;
        }

        if (strpos($coefficient, '%') !== false) {

            $coefficient = str_replace('%', '', $coefficient);

            if (preg_match('/^[+-]/', $coefficient)) {
                return $value + $value * (float)$coefficient / 100;
            }

            return $value * (float)$coefficient / 100;
        }

        if (preg_match('/^[+-]/', $coefficient)) {
            return $value + (float)$coefficient;
        }

        return $value * (float)$coefficient;
    }

    protected function increaseValueByVatPercent($value)
    {
        return $value + (($this->getVatPercent()*$value) / 100);
    }

    // ---------------------------------------

    protected function convertValueFromStoreToMarketplace($value)
    {
        return $this->modelFactory->getObject('Currency')->convertPrice(
            $value,
            $this->getCurrencyForPriceConvert(),
            $this->getListing()->getStoreId()
        );
    }

    abstract protected function getCurrencyForPriceConvert();

    // ---------------------------------------

    protected function calculateBundleTierValue(Product $product, $baseValue)
    {
        $tierPrice = $product->getTierPrice(
            $this->getSource('tier_website_id'), $this->getSource('tier_customer_group_id')
        );

        $value = array();

        foreach ($tierPrice as $qty => $discount) {
            $value[$qty] = round(($baseValue - ($baseValue * (double)$discount) / 100), 2);
        }

        return $value;
    }

    // ---------------------------------------

    protected function prepareOptionTitles($optionTitles)
    {
        return $optionTitles;
    }

    //########################################

    protected function isSourceModeNone()
    {
        return $this->getSourceMode() == self::MODE_NONE;
    }

    protected function isSourceModeProduct()
    {
        return $this->getSourceMode() == self::MODE_PRODUCT;
    }

    protected function isSourceModeSpecial()
    {
        return $this->getSourceMode() == self::MODE_SPECIAL;
    }

    protected function isSourceModeAttribute()
    {
        return $this->getSourceMode() == self::MODE_ATTRIBUTE;
    }

    protected function isSourceModeTier()
    {
        return $this->getSourceMode() == self::MODE_TIER;
    }

    //########################################
}