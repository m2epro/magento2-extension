<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Model\Ebay\Template\Description as TemplateDescription;
use Ess\M2ePro\Model\Ebay\Template\Description\Source as DescriptionSource;

class Preview extends AbstractBlock
{
    public const NEXT = 0;
    public const PREVIOUS = 1;
    public const CURRENT = 3;

    protected $ebayFactory;
    protected $currency;

    private $variations = null;
    private $images = null;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
    private $ebayListingProduct;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Store */
    private $componentEbayCategoryStore;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Locale\Currency $currency,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->currency = $currency;
        $this->componentEbayCategoryStore = $componentEbayCategoryStore;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->dataHelper = $dataHelper;
        $this->magentoHelper = $magentoHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $id = $this->getRequest()->getParam('currentProductId');

        $this->ebayListingProduct = $this->ebayFactory->getObjectLoaded(
            'Listing\Product',
            $id
        )->getChildObject();

        $this->setTemplate('ebay/listing/preview.phtml');
        $this->css->addFile('ebay/listing/preview.css');
    }

    protected function _beforeToHtml()
    {
        $this->jsTranslator->add('This is Item Preview Mode', $this->__('This is Item Preview Mode'));

        $variations = \Ess\M2ePro\Helper\Json::encode($this->getVariations());
        $images = \Ess\M2ePro\Helper\Json::encode($this->getImages());

        $this->js->add(
            <<<JS

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
            'Listing\Product',
            current($parsedProductIds)
        )->getChildObject();

        return [
            'title' => $tempEbayListingProduct->getMagentoProduct()->getName(),
            'id' => $tempEbayListingProduct->getMagentoProduct()->getProductId(),
            'url' => $this->getUrl(
                '*/ebay_listing/previewItems',
                [
                    'currentProductId' => current($parsedProductIds),
                    'productIds' => $productIds,
                ]
            ),
        ];
    }

    //########################################

    public function getTitle()
    {
        return $this->dataHelper
            ->escapeHtml($this->ebayListingProduct->getDescriptionTemplateSource()->getTitle());
    }

    public function getSubtitle()
    {
        return $this->dataHelper
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
        return $this->dataHelper
            ->escapeHtml($this->ebayListingProduct->getDescriptionTemplateSource()->getConditionNote());
    }

    // ---------------------------------------

    public function getPrice(array $variations)
    {
        $data = [
            'price' => null,
            'price_stp' => null,
            'price_map' => null,
        ];

        if ($this->ebayListingProduct->isListingTypeFixed()) {
            $data['price_fixed'] = number_format($this->ebayListingProduct->getFixedPrice(), 2);

            if (
                $this->ebayListingProduct->isPriceDiscountStp() &&
                $this->ebayListingProduct->getPriceDiscountStp() > $this->ebayListingProduct->getFixedPrice()
            ) {
                $data['price_stp'] = number_format($this->ebayListingProduct->getPriceDiscountStp(), 2);
            } elseif (
                $this->ebayListingProduct->isPriceDiscountMap() &&
                $this->ebayListingProduct->getPriceDiscountMap() > $this->ebayListingProduct->getFixedPrice()
            ) {
                $data['price_map'] = number_format($this->ebayListingProduct->getPriceDiscountMap(), 2);
            }
        } else {
            $data['price_start'] = number_format($this->ebayListingProduct->getStartPrice(), 2);
        }

        $productPrice = null;

        if (empty($variations)) {
            $productPrice = isset($data['price_fixed']) ? $data['price_fixed'] : $data['price_start'];
        } else {
            $variationPrices = [];

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
        if ($this->variations !== null) {
            return $this->variations;
        }

        $variations = $this->ebayListingProduct->getVariations(true);
        $data = [];

        if ($this->ebayListingProduct->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled()) {
            return $this->variations = [];
        }

        if (!$this->ebayListingProduct->isListingTypeFixed()) {
            return $this->variations = [];
        }

        if (!$this->ebayListingProduct->getEbayMarketplace()->isMultivariationEnabled()) {
            return $this->variations = [];
        }

        foreach ($variations as $variation) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $productVariation */

            $productVariation = $variation->getChildObject();

            $variationQty = $productVariation->getQty();
            if ($variationQty == 0) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */

            $options = $productVariation->getOptions(true);

            $variationData = [
                'price' => number_format($productVariation->getPrice(), 2),
                'qty' => $variationQty,
                'price_stp' => null,
                'price_map' => null,
            ];

            if (
                $this->ebayListingProduct->isPriceDiscountStp()
                && $productVariation->getPriceDiscountStp() > $productVariation->getPrice()
            ) {
                $variationData['price_stp'] = number_format($productVariation->getPriceDiscountStp(), 2);
            } elseif (
                $this->ebayListingProduct->isPriceDiscountMap()
                && $productVariation->getPriceDiscountMap() > $productVariation->getPrice()
            ) {
                $variationData['price_map'] = number_format($productVariation->getPriceDiscountMap(), 2);
            }

            $variationSpecifics = [];

            foreach ($options as $option) {
                $optionTitle = trim($option->getOption());
                $attributeTitle = trim($option->getAttribute());

                $variationSpecifics[$attributeTitle] = $optionTitle;
                $data['variation_sets'][$attributeTitle][] = $optionTitle;
            }

            $variationData = [
                'data' => $variationData,
                'specifics' => $variationSpecifics,
            ];

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
            return [];
        }

        $product = $this->ebayListingProduct->getMagentoProduct()->getProduct();

        $attributeCodes = $descriptionTemplate->getDecodedVariationConfigurableImages();
        $attributes = [];

        foreach ($attributeCodes as $attributeCode) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attribute = $product->getResource()->getAttribute($attributeCode);

            if (!$attribute) {
                continue;
            }

            $attribute->setStoreId($product->getStoreId());
            $attributes[] = $attribute;
        }

        if (empty($attributes)) {
            return [];
        }

        $attributeLabels = [];

        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productTypeInstance */
        $productTypeInstance = $this->ebayListingProduct->getMagentoProduct()->getTypeInstance();

        foreach ($productTypeInstance->getConfigurableAttributes($product) as $configurableAttribute) {
            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $configurableAttribute */
            $configurableAttribute->setStoteId($product->getStoreId());

            foreach ($attributes as $attribute) {
                if ((int)$attribute->getAttributeId() == (int)$configurableAttribute->getAttributeId()) {
                    $attributeLabels = [];
                    foreach ($attribute->getStoreLabels() as $storeLabel) {
                        $attributeLabels[] = trim($storeLabel);
                    }
                    $attributeLabels[] = trim($configurableAttribute->getData('label'));
                    $attributeLabels[] = trim($attribute->getFrontendLabel());

                    $attributeLabels = array_filter($attributeLabels);

                    break 2;
                }
            }
        }

        return $attributeLabels;
    }

    protected function getBundleImagesAttributeLabels()
    {
        $variations = $this->ebayListingProduct->getMagentoProduct()
                                               ->getVariationInstance()
                                               ->getVariationsTypeStandard();

        if (!empty($variations['set'])) {
            return [(string)key($variations['set'])];
        }

        return [];
    }

    private function getImagesDataByAttributeLabels(array $attributeLabels)
    {
        $images = [];
        $imagesLinks = [];
        $attributeLabel = false;

        foreach ($this->ebayListingProduct->getVariations(true) as $variation) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */

            if ($variation->getChildObject()->isDelete() || !$variation->getChildObject()->getQty()) {
                continue;
            }

            foreach ($variation->getOptions(true) as $option) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $option */

                $optionLabel = trim($option->getAttribute());
                $optionValue = trim($option->getOption());

                $foundAttributeLabel = false;
                foreach ($attributeLabels as $tempLabel) {
                    if (strtolower($tempLabel) == strtolower($optionLabel)) {
                        $foundAttributeLabel = $optionLabel;
                        break;
                    }
                }

                if ($foundAttributeLabel === false) {
                    continue;
                }

                if (!isset($imagesLinks[$optionValue])) {
                    $imagesLinks[$optionValue] = [];
                }

                $attributeLabel = $foundAttributeLabel;
                $optionImages = $this->ebayListingProduct->getEbayDescriptionTemplate()
                                                         ->getSource($option->getMagentoProduct())
                                                         ->getVariationImages();

                foreach ($optionImages as $image) {
                    if (!$image->getUrl()) {
                        continue;
                    }

                    if (count($imagesLinks[$optionValue]) >= DescriptionSource::VARIATION_IMAGES_COUNT_MAX) {
                        break 2;
                    }

                    if (!isset($images[$image->getHash()])) {
                        $imagesLinks[$optionValue][] = $image->getUrl();
                        $images[$image->getHash()] = $image;
                    }
                }
            }
        }

        if (!$attributeLabel || !$imagesLinks) {
            return [];
        }

        return [
            'specific' => $attributeLabel,
            'images' => $imagesLinks,
        ];
    }

    public function getImages()
    {
        if ($this->images !== null) {
            return $this->images;
        }

        $images = [];

        if ($this->ebayListingProduct->isVariationsReady()) {
            $attributeLabels = [];
            $images['variations'] = [];

            if ($this->ebayListingProduct->getMagentoProduct()->isConfigurableType()) {
                $attributeLabels = $this->getConfigurableImagesAttributeLabels();
            }

            if ($this->ebayListingProduct->getMagentoProduct()->isGroupedType()) {
                $attributeLabels = [\Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL];
            }

            if ($this->ebayListingProduct->getMagentoProduct()->isBundleType()) {
                $attributeLabels = $this->getBundleImagesAttributeLabels();
            }

            if (!empty($attributeLabels)) {
                $images['variations'] = $this->getImagesDataByAttributeLabels($attributeLabels);
            }
        }

        $links = [];
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

        if ($this->ebayListingProduct->getCategoryTemplateSource() === null) {
            return $finalCategory;
        }

        $categoryId = $this->ebayListingProduct->getCategoryTemplateSource()->getCategoryId();
        $categoryTitle = $this->componentEbayCategoryEbay->getPath($categoryId, $marketplaceId);

        if (!$categoryTitle) {
            return $categoryTitle;
        }

        $finalCategory = '<a>' . str_replace('>', '</a> > <a>', $categoryTitle) . '</a> (' . $categoryId . ')';

        return $finalCategory;
    }

    public function getOtherCategories()
    {
        $categoriesTitles = [];

        $marketplaceId = $this->ebayListingProduct->getMarketplace()->getId();
        $accountId = $this->ebayListingProduct->getEbayAccount()->getId();

        $source = $this->ebayListingProduct->getCategorySecondaryTemplateSource();
        if ($source !== null) {
            $title = $this->componentEbayCategoryEbay->getPath(
                $source->getCategoryId(),
                $marketplaceId
            );
            if (empty($title)) {
                $categoriesTitles['secondary'] = [$source->getCategoryId(), $title];
            }
        }

        $source = $this->ebayListingProduct->getStoreCategoryTemplateSource();
        if ($source !== null) {
            $title = $this->componentEbayCategoryStore->getPath(
                $source->getCategoryId(),
                $accountId
            );
            if (empty($title)) {
                $categoriesTitles['primary_store'] = [$source->getCategoryId(), $title];
            }
        }

        $source = $this->ebayListingProduct->getStoreCategorySecondaryTemplateSource();
        if ($source !== null) {
            $title = $this->componentEbayCategoryStore->getPath(
                $source->getCategoryId(),
                $accountId
            );
            if (empty($title)) {
                $categoriesTitles['secondary_store'] = [$source->getCategoryId(), $title];
            }
        }

        foreach ($categoriesTitles as $categoryType => &$categoryData) {
            [$id, $title] = $categoryData;
            $categoryData = '<a>' . str_replace('>', '</a> > <a>', $title) . '</a> (' . $id . ')';
        }

        unset($categoryData);

        return $categoriesTitles;
    }

    public function getSpecifics()
    {
        $data = [];

        if ($this->ebayListingProduct->getCategoryTemplate() === null) {
            return $data;
        }

        foreach ($this->ebayListingProduct->getCategoryTemplate()->getSpecifics(true) as $specific) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Specific $specific */

            $tempAttributeLabel = $specific->getSource($this->ebayListingProduct->getMagentoProduct())
                                           ->getLabel();
            $tempAttributeValues = $specific->getSource($this->ebayListingProduct->getMagentoProduct())
                                            ->getValues();

            if (is_array($tempAttributeValues) && !empty($tempAttributeValues['found_in_children'])) {
                $tempAttributeValues = [$tempAttributeValues['value']];
            }

            $values = [];
            foreach ($tempAttributeValues as $tempAttributeValue) {
                if ($tempAttributeValue == '--') {
                    continue;
                }
                $values[] = $tempAttributeValue;
            }

            if (empty($values)) {
                continue;
            }

            $data[] = [
                'name' => $tempAttributeLabel,
                'value' => $values,
            ];
        }

        return $data;
    }

    //########################################

    private function getConditionHumanTitle($code)
    {
        $codes = [
            TemplateDescription::CONDITION_EBAY_NEW => __('New'),
            TemplateDescription::CONDITION_EBAY_NEW_OTHER => __('New Other'),
            TemplateDescription::CONDITION_EBAY_NEW_WITH_DEFECT => __('New With Defects'),
            TemplateDescription::CONDITION_EBAY_CERTIFIED_REFURBISHED => __('Manufacturer Refurbished'),
            TemplateDescription::CONDITION_EBAY_EXCELLENT_REFURBISHED => __('Excellent (Refurbished)'),
            TemplateDescription::CONDITION_EBAY_VERY_GOOD_REFURBISHED => __('Very Good (Refurbished)'),
            TemplateDescription::CONDITION_EBAY_GOOD_REFURBISHED => __('Good (Refurbished)'),
            TemplateDescription::CONDITION_EBAY_SELLER_REFURBISHED => __('Seller Refurbished, Re-manufactured'),
            TemplateDescription::CONDITION_EBAY_LIKE_NEW => __('Like New'),
            TemplateDescription::CONDITION_EBAY_PRE_OWNED_EXCELLENT => __('Excellent (Pre-owned)'),
            TemplateDescription::CONDITION_EBAY_USED_EXCELLENT => __('Good (Pre-owned)'),
            TemplateDescription::CONDITION_EBAY_PRE_OWNED_FAIR => __('Fair (Pre-owned)'),
            TemplateDescription::CONDITION_EBAY_VERY_GOOD => __('Very Good'),
            TemplateDescription::CONDITION_EBAY_GOOD => __('Good'),
            TemplateDescription::CONDITION_EBAY_ACCEPTABLE => __('Acceptable'),
            TemplateDescription::CONDITION_EBAY_NOT_WORKING => __('For Parts or Not Working'),
        ];

        if (!isset($codes[$code])) {
            return '';
        }

        return $codes[$code];
    }

    private function getCountryHumanTitle($countryId)
    {
        $countries = $this->magentoHelper->getCountries();

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
        $locationsTitle = [];
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
        $itemLocation = [
            $this->ebayListingProduct->getShippingTemplateSource()->getPostalCode(),
            $this->ebayListingProduct->getShippingTemplateSource()->getAddress(),
            $this->getCountryHumanTitle($this->ebayListingProduct->getShippingTemplateSource()->getCountry()),
        ];

        return implode(', ', $itemLocation);
    }

    public function getShippingDispatchTime()
    {
        $dispatchTime = null;

        if (
            $this->ebayListingProduct->getShippingTemplate()->isLocalShippingFlatEnabled() ||
            $this->ebayListingProduct->getShippingTemplate()->isLocalShippingCalculatedEnabled()
        ) {
            $dispatchTimeId = $this->ebayListingProduct->getShippingTemplateSource()->getDispatchTime();

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
        $services = [];
        $storeId = $this->ebayListingProduct->getListing()->getStoreId();

        foreach ($this->ebayListingProduct->getShippingTemplate()->getServices(true) as $service) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Service $service */

            if (!$service->isShippingTypeLocal()) {
                continue;
            }

            $tempDataMethod = [
                'service' => $this->getShippingServiceHumanTitle($service->getShippingValue()),
            ];

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
        $services = [];
        $storeId = $this->ebayListingProduct->getListing()->getStoreId();

        foreach ($this->ebayListingProduct->getShippingTemplate()->getServices(true) as $service) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Shipping\Service $service */

            if (!$service->isShippingTypeInternational()) {
                continue;
            }

            $tempDataMethod = [
                'service' => $this->getShippingServiceHumanTitle($service->getShippingValue()),
                'locations' => implode(', ', $this->getShippingLocationHumanTitle($service->getLocations())),
            ];

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

    public function getShippingExcludedLocations()
    {
        $locations = [];

        foreach ($this->ebayListingProduct->getShippingTemplate()->getExcludedLocations() as $location) {
            $locations[] = $this->getShippingExcludeLocationHumanTitle($location['code']);
        }

        return implode(', ', $locations);
    }

    public function getShippingInternationalGlobalOffer()
    {
        return $this->ebayListingProduct->getShippingTemplate()->isGlobalShippingProgramEnabled();
    }

    // ---------------------------------------

    public function getReturnPolicy()
    {
        $returnPolicyTitles = [
            'returns_accepted' => '',
            'returns_within' => '',
            'refund' => '',
            'shipping_cost_paid_by' => '',

            'international_returns_accepted' => '',
            'international_returns_within' => '',
            'international_refund' => '',
            'international_shipping_cost_paid_by' => '',

            'description' => '',
        ];

        $returnAccepted = $this->ebayListingProduct->getReturnTemplate()->getAccepted();
        foreach ($this->getDictionaryInfo('returns_accepted') as $returnAcceptedId) {
            if ($returnAccepted === $returnAcceptedId['ebay_id']) {
                $returnPolicyTitles['returns_accepted'] = $this->__($returnAcceptedId['title']);
                break;
            }
        }

        $returnWithin = $this->ebayListingProduct->getReturnTemplate()->getWithin();
        foreach ($this->getDictionaryInfo('returns_within') as $returnWithinId) {
            if ($returnWithin === $returnWithinId['ebay_id']) {
                $returnPolicyTitles['returns_within'] = $this->__($returnWithinId['title']);
                break;
            }
        }

        $returnRefund = $this->ebayListingProduct->getReturnTemplate()->getOption();
        foreach ($this->getDictionaryInfo('refund') as $returnRefundId) {
            if ($returnRefund === $returnRefundId['ebay_id']) {
                $returnPolicyTitles['refund'] = $this->__($returnRefundId['title']);
                break;
            }
        }

        $returnShippingCost = $this->ebayListingProduct->getReturnTemplate()->getShippingCost();
        foreach ($this->getDictionaryInfo('shipping_cost_paid_by') as $returnShippingCostId) {
            if ($returnShippingCost === $returnShippingCostId['ebay_id']) {
                $returnPolicyTitles['shipping_cost_paid_by'] = $this->__($returnShippingCostId['title']);
                break;
            }
        }

        // ---------------------------------------

        $returnAccepted = $this->ebayListingProduct->getReturnTemplate()->getInternationalAccepted();
        foreach ($this->getInternationalDictionaryInfo('returns_accepted') as $returnAcceptedId) {
            if ($returnAccepted === $returnAcceptedId['ebay_id']) {
                $returnPolicyTitles['international_returns_accepted'] = $this->__($returnAcceptedId['title']);
                break;
            }
        }

        $returnWithin = $this->ebayListingProduct->getReturnTemplate()->getInternationalWithin();
        foreach ($this->getInternationalDictionaryInfo('returns_within') as $returnWithinId) {
            if ($returnWithin === $returnWithinId['ebay_id']) {
                $returnPolicyTitles['international_returns_within'] = $this->__($returnWithinId['title']);
                break;
            }
        }

        $returnRefund = $this->ebayListingProduct->getReturnTemplate()->getInternationalOption();
        foreach ($this->getInternationalDictionaryInfo('refund') as $returnRefundId) {
            if ($returnRefund === $returnRefundId['ebay_id']) {
                $returnPolicyTitles['international_refund'] = $this->__($returnRefundId['title']);
                break;
            }
        }

        $returnShippingCost = $this->ebayListingProduct->getReturnTemplate()->getInternationalShippingCost();
        foreach ($this->getInternationalDictionaryInfo('shipping_cost_paid_by') as $shippingCostId) {
            if ($returnShippingCost === $shippingCostId['ebay_id']) {
                $returnPolicyTitles['international_shipping_cost_paid_by'] = $this->__($shippingCostId['title']);
                break;
            }
        }

        // ---------------------------------------

        $returnPolicyTitles['description'] = $this->ebayListingProduct->getReturnTemplate()->getDescription();

        return $returnPolicyTitles;
    }

    public function isDomesticReturnsAccepted()
    {
        $template = $this->ebayListingProduct->getReturnTemplate();

        return $template->getAccepted() === \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy::RETURNS_ACCEPTED;
    }

    public function isInternationalReturnsAccepted()
    {
        $template = $this->ebayListingProduct->getReturnTemplate();

        return $this->isDomesticReturnsAccepted() &&
            $template->getInternationalAccepted() === \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy::RETURNS_ACCEPTED;
    }

    //########################################

    protected function getDictionaryInfo($key)
    {
        $returnPolicyInfo = $this->ebayListingProduct->getEbayMarketplace()->getReturnPolicyInfo();

        return !empty($returnPolicyInfo[$key]) ? $returnPolicyInfo[$key] : [];
    }

    protected function getInternationalDictionaryInfo($key)
    {
        $returnPolicyInfo = $this->ebayListingProduct->getEbayMarketplace()->getReturnPolicyInfo();

        if (!empty($returnPolicyInfo['international_' . $key])) {
            return $returnPolicyInfo['international_' . $key];
        }

        return $this->getDictionaryInfo($key);
    }

    //########################################
}
