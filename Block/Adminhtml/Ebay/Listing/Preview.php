<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Preview extends AbstractBlock
{
    const NEXT = 0;
    const PREVIOUS = 1;
    const CURRENT = 3;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
    private $ebayListingProduct;

    private $variations = NULL;
    private $images = NULL;

    protected $ebayFactory;
    protected $currency;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Locale\Currency $currency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        $this->currency = $currency;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $id = $this->getRequest()->getParam('currentProductId');

        $this->ebayListingProduct = $this->ebayFactory->getObjectLoaded(
            'Listing\Product', $id
        )->getChildObject();

        $this->setTemplate('ebay/listing/preview.phtml');
        $this->css->addFile('ebay/listing/preview.css');
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add('This is Item Preview Mode', $this->__('This is Item Preview Mode'));

        $variations = $this->getHelper('Data')->jsonEncode($this->getVariations());
        $images = $this->getHelper('Data')->jsonEncode($this->getImages());

        $this->js->add(<<<JS

        M2ePro.formData.variations = {$variations};
        M2ePro.formData.images = {$images};

        require(['M2ePro/Ebay/Listing/Preview'], function () {

            window.EbayListingPreviewItemsObj = new EbayListingPreviewItems();
            EbayListingPreviewItemsObj.initVariations();
        });
JS
);

        return parent::_beforeToHtml();
    }

    public function truncate($text, $length)
    {
        return $this->filterManager->truncate($text, ['length' => $length]);
    }

    //########################################

    public function getProductShortInfo($direction)
    {
        $currentProductId = $this->getRequest()->getParam('currentProductId');
        $productIds = $this->getRequest()->getParam('productIds');

        $parsedProductIds = explode(',', $productIds);

        do {
            if ($currentProductId === current($parsedProductIds)) {
                break;
            }
        } while (next($parsedProductIds));

        if ($direction === self::NEXT && next($parsedProductIds) === false) {
            return null;
        }
        if ($direction === self::PREVIOUS && prev($parsedProductIds) === false) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $tempEbayListingProduct */

        $tempEbayListingProduct = $this->ebayFactory->getObjectLoaded(
            'Listing\Product', current($parsedProductIds)
        )->getChildObject();

        return array(
            'title' => $tempEbayListingProduct->getMagentoProduct()->getName(),
            'id' => $tempEbayListingProduct->getMagentoProduct()->getProductId(),
            'url' => $this->getUrl('*/ebay_listing/previewItems', array(
                'currentProductId' => current($parsedProductIds),
                'productIds' => $productIds,
            ))
        );
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Data')
            ->escapeHtml($this->ebayListingProduct->getDescriptionTemplateSource()->getTitle());
    }

    public function getSubtitle()
    {
        return $this->getHelper('Data')
            ->escapeHtml($this->ebayListingProduct->getDescriptionTemplateSource()->getSubTitle());
    }

    public function getDescription()
    {
        return $this->ebayListingProduct->getDescriptionRenderer()->parseTemplate(
            $this->ebayListingProduct->getDescriptionTemplateSource()->getDescription()
        );
    }

    public function getCondition()
    {
        return $this->getConditionHumanTitle($this->ebayListingProduct->getDescriptionTemplateSource()->getCondition());
    }

    public function getConditionNote()
    {
        return $this->getHelper('Data')
            ->escapeHtml($this->ebayListingProduct->getDescriptionTemplateSource()->getConditionNote());
    }

    // ---------------------------------------

    public function getPrice(array $variations)
    {
        $data = array(
            'price' => null,
            'price_stp' => null,
            'price_map' => null
        );

        if ($this->ebayListingProduct->isListingTypeFixed()) {
            $data['price_fixed'] = number_format($this->ebayListingProduct->getFixedPrice(), 2);

            if ($this->ebayListingProduct->isPriceDiscountStp() &&
                $this->ebayListingProduct->getPriceDiscountStp() > $this->ebayListingProduct->getFixedPrice()) {
                $data['price_stp'] = number_format($this->ebayListingProduct->getPriceDiscountStp(), 2);
            } elseif ($this->ebayListingProduct->isPriceDiscountMap() &&
                $this->ebayListingProduct->getPriceDiscountMap() > $this->ebayListingProduct->getFixedPrice()) {
                $data['price_map'] = number_format($this->ebayListingProduct->getPriceDiscountMap(), 2);
            }
        } else {
            $data['price_start'] = number_format($this->ebayListingProduct->getStartPrice(), 2);
        }

        $productPrice = null;

        if (empty($variations)) {
            $productPrice = isset($data['price_fixed']) ? $data['price_fixed'] : $data['price_start'];
        } else {
            $variationPrices = array();

            foreach ($variations['variations'] as $variation) {
                if ($variation['data']['qty']) {
                    $variationPrices[] = $variation['data'];
                }
            }

            if (!empty($variationPrices)) {

                $min = $variationPrices[0]['price'];
                $productPrice = $min;
                $data['price_stp'] = $variationPrices[0]['price_stp'];
                $data['price_map'] = $variationPrices[0]['price_map'];

                foreach ($variationPrices as $variationPrice) {
                    if ($variationPrice['price'] < $min) {
                        $productPrice = $variationPrice['price'];
                        $data['price_stp'] = $variationPrice['price_stp'];
                        $data['price_map'] = $variationPrice['price_map'];
                    }
                }
            }
        }

        $data['price'] = $productPrice;

        return $data;
    }

    public function getQty()
    {
        return $this->ebayListingProduct->getQty();
    }

    public function getCurrency()
    {
        return $this->ebayListingProduct->getEbayMarketplace()->getCurrency();
    }

    public function getCurrencySymbol()
    {
        return $this->currency->getCurrency($this->getCurrency())->getSymbol();
    }

    // ---------------------------------------

    public function getVariations()
    {
        if (!is_null($this->variations)) {
            return $this->variations;
        }

        $variations = $this->ebayListingProduct->getVariations(true);
        $data = array();

        if ($this->ebayListingProduct->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled()) {
            return $this->variations = array();
        }

        if (!$this->ebayListingProduct->isListingTypeFixed()) {
            return $this->variations = array();
        }

        if (!$this->ebayListingProduct->getEbayMarketplace()->isMultivariationEnabled()) {
            return $this->variations = array();
        }

        foreach ($variations as $variation) {

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            /** @var $productVariation \Ess\M2ePro\Model\Ebay\Listing\Product\Variation */

            $productVariation = $variation->getChildObject();

            $variationQty = $productVariation->getQty();
            if ($variationQty == 0) {
                continue;
            }

            /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

            $options = $productVariation->getOptions(true);

            $variationData = array(
                'price' => number_format($productVariation->getPrice(), 2),
                'qty' => $variationQty,
                'price_stp' => null,
                'price_map' => null
            );

            if ($this->ebayListingProduct->isPriceDiscountStp()
                && $productVariation->getPriceDiscountStp() > $productVariation->getPrice()) {
                $variationData['price_stp'] = number_format($productVariation->getPriceDiscountStp(), 2);
            } elseif ($this->ebayListingProduct->isPriceDiscountMap()
                && $productVariation->getPriceDiscountMap() > $productVariation->getPrice()) {
                $variationData['price_map'] = number_format($productVariation->getPriceDiscountMap(), 2);
            }

            $variationSpecifics = array();

            foreach ($options as $option) {

                $optionTitle = trim($option->getOption());
                $attributeTitle = trim($option->getAttribute());

                $variationSpecifics[$attributeTitle] = $optionTitle;
                $data['variation_sets'][$attributeTitle][] = $optionTitle;
            }

            $variationData = array(
                'data' => $variationData,
                'specifics' => $variationSpecifics
            );

            $data['variations'][] = $variationData;
        }

        if (!empty($data['variation_sets'])) {
            foreach ($data['variation_sets'] as &$variationSets) {
                $variationSets = array_unique($variationSets);
            }
        }

        return $this->variations = $data;
    }

    // ---------------------------------------

    private function getConfigurableImagesAttributeLabels()
    {
        $descriptionTemplate = $this->ebayListingProduct->getEbayDescriptionTemplate();

        if (!$descriptionTemplate->isVariationConfigurableImages()) {
            return array();
        }

        $product = $this->ebayListingProduct->getMagentoProduct()->getProduct();

        $attributeCodes = $descriptionTemplate->getDecodedVariationConfigurableImages();
        $attributes = array();

        foreach ($attributeCodes as $attributeCode) {
            /** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
            $attribute = $product->getResource()->getAttribute($attributeCode);

            if (!$attribute) {
                continue;
            }

            $attribute->setStoreId($product->getStoreId());
            $attributes[] = $attribute;
        }

        if (empty($attributes)) {
            return array();
        }

        $attributeLabels = array();

        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $this->ebayListingProduct->getMagentoProduct()->getTypeInstance();

        foreach ($productTypeInstance->getConfigurableAttributes($product) as $configurableAttribute) {

            /** @var $configurableAttribute \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute */
            $configurableAttribute->setStoteId($product->getStoreId());

            foreach ($attributes as $attribute) {

                if ((int)$attribute->getAttributeId() == (int)$configurableAttribute->getAttributeId()) {

                    $attributeLabels = array_values($attribute->getStoreLabels());
                    $attributeLabels[] = $configurableAttribute->getData('label');
                    $attributeLabels[] = $attribute->getFrontendLabel();

                    $attributeLabels = array_filter($attributeLabels);

                    break 2;
                }
            }
        }

        return $attributeLabels;
    }

    private function getImagesDataByAttributeLabels(array $attributeLabels)
    {
        $images = array();
        $attributeLabel = false;

        $variations = $this->ebayListingProduct->getVariations(true);

        foreach ($variations as $variation) {

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */

            if ($variation->getChildObject()->isDelete() || !$variation->getChildObject()->getQty()) {
                continue;
            }

            $options = $variation->getOptions(true);

            foreach ($options as $option) {

                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

                $foundAttributeLabel = false;
                foreach ($attributeLabels as $tempLabel) {
                    if (strtolower($tempLabel) == strtolower($option->getAttribute())) {
                        $foundAttributeLabel = $option->getAttribute();
                        break;
                    }
                }

                if ($foundAttributeLabel === false) {
                    continue;
                }

                $attributeLabel = $foundAttributeLabel;

                $optionImages = $this->ebayListingProduct->getEbayDescriptionTemplate()
                    ->getSource($option->getMagentoProduct())
                    ->getVariationImages();

                $links = array();
                foreach ($optionImages as $image) {

                    if (!$image->getUrl()) {
                        continue;
                    }

                    $links[] = $image->getUrl();
                }

                if (count($links) <= 0) {
                    continue;
                }

                $images[$option->getOption()] = $links;
            }
        }

        if (!$attributeLabel || !$images) {
            return array();
        }

        return array(
            'specific' => $attributeLabel,
            'images' => $images
        );
    }

    public function getImages()
    {
        if (!is_null($this->images)) {
            return $this->images;
        }

        $images = array();

        if ($this->ebayListingProduct->isVariationsReady()) {

            $attributeLabels = array();
            $images['variations'] = array();

            if ($this->ebayListingProduct->getMagentoProduct()->isConfigurableType()) {
                $attributeLabels = $this->getConfigurableImagesAttributeLabels();
            }

            if ($this->ebayListingProduct->getMagentoProduct()->isGroupedType()) {
                $attributeLabels = array(\Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL);
            }

            if (count($attributeLabels) > 0) {
                $images['variations'] = $this->getImagesDataByAttributeLabels($attributeLabels);
            }
        }

        $links = array();
        foreach ($this->ebayListingProduct->getDescriptionTemplateSource()->getGalleryImages() as $image) {

            if (!$image->getUrl()) {
                continue;
            }
            $links[] = $image->getUrl();
        }

        $images['gallery'] = $links;
        return $this->images = $images;
    }

    // ---------------------------------------

    public function getCategory()
    {
        $finalCategory = '';
        $marketplaceId = $this->ebayListingProduct->getMarketplace()->getId();

        if (is_null($this->ebayListingProduct->getCategoryTemplateSource())) {
            return $finalCategory;
        }

        $categoryId = $this->ebayListingProduct->getCategoryTemplateSource()->getMainCategory();
        $categoryTitle = $this->getHelper('Component\Ebay\Category\Ebay')->getPath($categoryId, $marketplaceId);

        if (!$categoryTitle) {
            return $categoryTitle;
        }

        $finalCategory = '<a>' . str_replace('>', '</a> > <a>', $categoryTitle) . '</a> (' . $categoryId . ')';

        return $finalCategory;
    }

    public function getOtherCategories()
    {
        $otherCategoriesFinalTitles = array();

        $marketplaceId = $this->ebayListingProduct->getMarketplace()->getId();
        $accountId = $this->ebayListingProduct->getEbayAccount()->getId();

        $otherCategoryTemplateSource = $this->ebayListingProduct->getOtherCategoryTemplateSource();

        if (is_null($otherCategoryTemplateSource)) {
            return $otherCategoriesFinalTitles;
        }

        $otherCategoriesIds = array(
            'secondary' => $otherCategoryTemplateSource->getSecondaryCategory(),
            'primary_store' => $otherCategoryTemplateSource->getStoreCategoryMain(),
            'secondary_store' => $otherCategoryTemplateSource->getStoreCategorySecondary()
        );

        $otherCategoriesTitles = array(
            'secondary' => $this->getHelper('Component\Ebay\Category\Ebay')
                ->getPath($otherCategoriesIds['secondary'], $marketplaceId),
            'primary_store' => $this->getHelper('Component\Ebay\Category\Store')
                ->getPath($otherCategoriesIds['primary_store'], $accountId),
            'secondary_store' => $this->getHelper('Component\Ebay\Category\Store')
                ->getPath($otherCategoriesIds['secondary_store'], $accountId)
        );

        foreach ($otherCategoriesTitles as $otherCategoryType => $otherCategoryTitle) {
            if ($otherCategoryTitle) {
                $otherCategoriesFinalTitles[$otherCategoryType] =
                    '<a>' . str_replace('>', '</a> > <a>', $otherCategoryTitle)
                    . '</a> (' . $otherCategoriesIds[$otherCategoryType] . ')';
            }
        }

        return $otherCategoriesFinalTitles;
    }

    public function getSpecifics()
    {
        $data = array();

        if (is_null($this->ebayListingProduct->getCategoryTemplate())) {
            return $data;
        }

        foreach ($this->ebayListingProduct->getCategoryTemplate()->getSpecifics(true) as $specific) {

            /** @var $specific \Ess\M2ePro\Model\Ebay\Template\Category\Specific */

            $tempAttributeLabel = $specific->getSource($this->ebayListingProduct->getMagentoProduct())
                ->getLabel();
            $tempAttributeValues = $specific->getSource($this->ebayListingProduct->getMagentoProduct())
                ->getValues();

            $values = array();
            foreach ($tempAttributeValues as $tempAttributeValue) {
                if ($tempAttributeValue == '--') {
                    continue;
                }
                $values[] = $tempAttributeValue;
            }

            if (empty($values)) {
                continue;
            }

            $data[] = array(
                'name' => $tempAttributeLabel,
                'value' => $values
            );
        }

        return $data;
    }

    //########################################

    private function getConditionHumanTitle($code)
    {
        $codes = array(
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NEW =>
                $this->__('New'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NEW_OTHER =>
                $this->__('New Other'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NEW_WITH_DEFECT =>
                $this->__('New With Defects'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_MANUFACTURER_REFURBISHED =>
                $this->__('Manufacturer Refurbished'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_SELLER_REFURBISHED =>
                $this->__('Seller Refurbished, Re-manufactured'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_USED =>
                $this->__('Used'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_VERY_GOOD =>
                $this->__('Very Good'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_GOOD =>
                $this->__('Good'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_ACCEPTABLE =>
                $this->__('Acceptable'),
            \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_EBAY_NOT_WORKING =>
                $this->__('For Parts or Not Working')
        );

        if (!isset($codes[$code])) {
            return '';
        }

        return $codes[$code];
    }

    private function getCountryHumanTitle($countryId)
    {
        $countries = $this->getHelper('Magento')->getCountries();

        foreach ($countries as $country) {
            if ($countryId === $country['value']) {
                return $this->__($country['label']);
            }
        }
        return '';
    }

    private function getShippingServiceHumanTitle($serviceMethodId)
    {
        $shippingServicesInfo = $this->ebayListingProduct->getEbayMarketplace()->getShippingInfo();

        foreach ($shippingServicesInfo as $shippingServiceInfo) {
            foreach ($shippingServiceInfo['methods'] as $shippingServiceMethod) {
                if ($serviceMethodId == $shippingServiceMethod['ebay_id']) {
                    return $this->__($shippingServiceMethod['title']);
                }
            }
        }
        return '';
    }

    private function getShippingLocationHumanTitle(array $locationIds)
    {
        $locationsTitle = array();
        $locationsInfo = $this->ebayListingProduct->getEbayMarketplace()->getShippingLocationInfo();

        foreach ($locationIds as $locationId) {
            foreach ($locationsInfo as $locationInfo) {
                if ($locationId == $locationInfo['ebay_id']) {
                    $locationsTitle[] = $this->__($locationInfo['title']);
                }
            }
        }
        return $locationsTitle;
    }

    private function getShippingExcludeLocationHumanTitle($excludeLocationId)
    {
        $excludeLocationsInfo = $this->ebayListingProduct->getEbayMarketplace()->getShippingLocationExcludeInfo();

        foreach ($excludeLocationsInfo as $excludeLocationInfo) {
            if ($excludeLocationId == $excludeLocationInfo['ebay_id']) {
                return $this->__($excludeLocationInfo['title']);
            }
        }
        return '';
    }

    public function getItemLocation()
    {
        $itemLocation = array(
            $this->ebayListingProduct->getShippingTemplateSource()->getPostalCode(),
            $this->ebayListingProduct->getShippingTemplateSource()->getAddress(),
            $this->getCountryHumanTitle($this->ebayListingProduct->getShippingTemplateSource()->getCountry())
        );
        return implode($itemLocation, ', ');
    }

    public function getShippingDispatchTime()
    {
        $dispatchTime = null;

        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingFlatEnabled() ||
            $this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled()
        ) {

            $dispatchTimeId = $this->ebayListingProduct->getShippingTemplate()->getDispatchTime();

            if ($dispatchTimeId == 0) {
                return $this->__('Same Business Day');
            } else {
                $dispatchInfo = $this->ebayListingProduct->getEbayMarketplace()->getDispatchInfo();

                foreach ($dispatchInfo as $dispatch) {
                    if ($dispatch['ebay_id'] == $dispatchTimeId) {
                        $dispatchTime = $dispatch['title'];
                        break;
                    }
                }

                return $this->__($dispatchTime);
            }
        }

        return $dispatchTime;
    }

    public function getShippingLocalHandlingCost()
    {
        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
            return $this->ebayListingProduct->getShippingTemplate()->getCalculatedShipping()
                ->getLocalHandlingCost();
        }
        return 0;
    }

    public function getShippingInternationalHandlingCost()
    {
        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
            return $this->ebayListingProduct->getShippingTemplate()->getCalculatedShipping()
                ->getInternationalHandlingCost();
        }
        return 0;
    }

    public function getShippingLocalType()
    {
        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingLocalEnabled()) {
            return $this->__('No Shipping - local pickup only');
        }
        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingFreightEnabled()) {
            return $this->__('Freight - large Items');
        }
        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingFlatEnabled()) {
            return $this->__('Flat - same cost to all Buyers');
        }
        if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
            return $this->__('Calculated - cost varies by Buyer Location');
        }

        return '';
    }

    public function getShippingInternationalType()
    {
        if ($this->ebayListingProduct->getShippingTemplate()->isInternationalShippingNoInternationalEnabled()) {
            return '';
        }
        if ($this->ebayListingProduct->getShippingTemplate()->isInternationalShippingFlatEnabled()) {
            return $this->__('Flat - same cost to all Buyers');
        }
        if ($this->ebayListingProduct->getShippingTemplate()->isInternationalShippingCalculatedEnabled()) {
            return $this->__('Calculated - cost varies by Buyer Location');
        }

        return '';
    }

    public function isLocalShippingCalculated()
    {
        return $this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled();
    }

    public function isInternationalShippingCalculated()
    {
        return $this->ebayListingProduct->getShippingTemplate()->isInternationalShippingCalculatedEnabled();
    }

    public function getShippingLocalServices()
    {
        $services = array();
        $storeId = $this->ebayListingProduct->getListing()->getStoreId();

        foreach ($this->ebayListingProduct->getShippingTemplate()->getServices(true) as $service) {

            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */

            if (!$service->isShippingTypeLocal()) {
                continue;
            }

            $tempDataMethod = array(
                'service' => $this->getShippingServiceHumanTitle($service->getShippingValue())
            );

            if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingFlatEnabled()) {

                $tempDataMethod['cost'] = $service->getSource($this->ebayListingProduct->getMagentoProduct())
                    ->getCost($storeId);

                $tempDataMethod['cost_additional'] = $service->getSource($this->ebayListingProduct->getMagentoProduct())
                    ->getCostAdditional($storeId);
            }

            if ($this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled()) {
                $tempDataMethod['is_free'] = $service->isCostModeFree();
            }

            $services[] = $tempDataMethod;
        }

        return $services;
    }

    public function getShippingInternationalServices()
    {
        $services = array();
        $storeId = $this->ebayListingProduct->getListing()->getStoreId();

        foreach ($this->ebayListingProduct->getShippingTemplate()->getServices(true) as $service) {

            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Shipping\Service */

            if (!$service->isShippingTypeInternational()) {
                continue;
            }

            $tempDataMethod = array(
                'service' => $this->getShippingServiceHumanTitle($service->getShippingValue()),
                'locations' => implode(', ', $this->getShippingLocationHumanTitle($service->getLocations()))
            );

            if ($this->ebayListingProduct->getShippingTemplate()->isInternationalShippingFlatEnabled()) {

                $tempDataMethod['cost'] = $service->getSource($this->ebayListingProduct->getMagentoProduct())
                    ->getCost($storeId);

                $tempDataMethod['cost_additional'] = $service->getSource($this->ebayListingProduct->getMagentoProduct())
                    ->getCostAdditional($storeId);
            }

            $services[] = $tempDataMethod;
        }

        return $services;
    }

    public function getPayment()
    {
        $data = array();

        if ($this->ebayListingProduct->getPaymentTemplate()->isPayPalEnabled()) {
            $data['paypal'] = true;
        }

        $services = $this->ebayListingProduct->getPaymentTemplate()->getServices(true);
        $paymentMethodsInfo = $this->ebayListingProduct->getMarketplace()->getChildObject()->getPaymentInfo();

        $paymentMethods = array();
        foreach ($services as $service) {
            /** @var $service \Ess\M2ePro\Model\Ebay\Template\Payment\Service */

            foreach ($paymentMethodsInfo as $paymentMethodInfo) {
                if ($service->getCodeName() == $paymentMethodInfo['ebay_id']) {
                    $paymentMethods[] = $paymentMethodInfo['title'];
                }
            }
        }

        $data['paymentMethods'] = $paymentMethods;

        return $data;
    }

    public function getShippingExcludedLocations()
    {
        $locations = array();

        foreach ($this->ebayListingProduct->getShippingTemplate()->getExcludedLocations() as $location) {
            $locations[] = $this->getShippingExcludeLocationHumanTitle($location['code']);
        }

        return implode(', ', $locations);
    }

    public function getShippingInternationalGlobalOffer()
    {
        return $this->ebayListingProduct->getShippingTemplate()->isGlobalShippingProgramEnabled();
    }

    public function getReturnPolicy()
    {
        $returnPolicyInfo = $this->ebayListingProduct->getEbayMarketplace()->getReturnPolicyInfo();

        $returnAccepted = $this->ebayListingProduct->getReturnTemplate()->getAccepted();
        if ($returnAccepted === 'ReturnsNotAccepted') {
            return array();
        }

        $returnPolicyTitles = array(
            'returns_accepted'      => '',
            'returns_within'        => '',
            'refund'                => '',
            'shipping_cost_paid_by' => '',
            'restocking_fee_value'  => ''
        );

        foreach ($returnPolicyInfo['returns_accepted'] as $returnAcceptedId) {
            if ($returnAccepted === $returnAcceptedId['ebay_id']) {
                $returnPolicyTitles['returns_accepted'] = $this->__($returnAcceptedId['title']);
                break;
            }
        }

        $returnWithin = $this->ebayListingProduct->getReturnTemplate()->getWithin();
        foreach ($returnPolicyInfo['returns_within'] as $returnWithinId) {
            if ($returnWithin === $returnWithinId['ebay_id']) {
                $returnPolicyTitles['returns_within'] = $this->__($returnWithinId['title']);
                break;
            }
        }

        $returnRefund = $this->ebayListingProduct->getReturnTemplate()->getOption();
        foreach ($returnPolicyInfo['refund'] as $returnRefundId) {
            if ($returnRefund === $returnRefundId['ebay_id']) {
                $returnPolicyTitles['refund'] = $this->__($returnRefundId['title']);
                break;
            }
        }

        $returnShippingCost = $this->ebayListingProduct->getReturnTemplate()->getShippingCost();
        foreach ($returnPolicyInfo['shipping_cost_paid_by'] as $returnShippingCostId) {
            if ($returnShippingCost === $returnShippingCostId['ebay_id']) {
                $returnPolicyTitles['shipping_cost_paid_by'] =
                    $this->__($returnShippingCostId['title']);
                break;
            }
        }

        $returnRestockingFee = $this->ebayListingProduct->getReturnTemplate()->getRestockingFee();
        if ($returnRestockingFee === "NoRestockingFee") {
            $returnPolicyTitles['restocking_fee_value'] = '';
        } else {
            foreach ($returnPolicyInfo['restocking_fee_value'] as $returnRestockingFeeId) {
                if ($returnRestockingFee === $returnRestockingFeeId['ebay_id']) {
                    $returnPolicyTitles['restocking_fee_value'] =
                        $this->__($returnRestockingFeeId['title']);
                    break;
                }
            }
        }

        $returnPolicyTitles['is_holiday_enabled'] = $this->ebayListingProduct->getReturnTemplate()->isHolidayEnabled();
        $returnPolicyTitles['description'] = $this->ebayListingProduct->getReturnTemplate()->getDescription();

        return $returnPolicyTitles;
    }
}