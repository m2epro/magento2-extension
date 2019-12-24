<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator getConfigurator()
 */
namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

use \Ess\M2ePro\Model\Ebay\Template\Description\Source as DescriptionSource;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Variations
 */
class Variations extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    protected $variationsThatCanNotBeDeleted = [];

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [
            'is_variation_item' => $this->getIsVariationItem()
        ];

        $this->logLimitationsAndReasons();

        if (!$this->getIsVariationItem() || !$this->getConfigurator()->isVariationsAllowed()) {
            return $data;
        }

        $data['variation'] = $this->getVariationsData();

        $this->getConfigurator()->tryToIncreasePriority(
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator::PRIORITY_VARIATION
        );

        if ($sets = $this->getSetsData()) {
            $data['variations_sets'] = $sets;
        }

        $data['variation_image'] = $this->getImagesData();

        if ($variationsThatCanNotBeDeleted = $this->getVariationsThatCanNotBeDeleted()) {
            $data['variations_that_can_not_be_deleted'] = $variationsThatCanNotBeDeleted;
        }

        return $data;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getVariationsData()
    {
        $data = [];

        $qtyMode = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtyMode();

        $productsIds = [];
        $variationIdsIndexes = [];

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            /** @var $ebayVariation \Ess\M2ePro\Model\Ebay\Listing\Product\Variation */

            $ebayVariation = $variation->getChildObject();

            if (isset($this->validatorsData['variation_fixed_price_'.$variation->getId()])) {
                $variationPrice = $this->validatorsData['variation_fixed_price_'.$variation->getId()];
            } else {
                $variationPrice = $ebayVariation->getPrice();
            }

            $item = [
                '_instance_' => $variation,
                'price'      => $variationPrice,
                'qty'        => $ebayVariation->isDelete() ? 0 : $ebayVariation->getQty(),
                'sku'        => $ebayVariation->getOnlineSku() ? $ebayVariation->getOnlineSku()
                                                               : $ebayVariation->getSku(),
                'add'        => $ebayVariation->isAdd(),
                'delete'     => $ebayVariation->isDelete(),
                'specifics'  => []
            ];

            if ($ebayVariation->isDelete()) {
                $item['sku'] = 'del-' . sha1(microtime(1).$ebayVariation->getOnlineSku());
            }

            if (($qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED ||
                $qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) && !$item['delete']) {
                foreach ($variation->getOptions(true) as $option) {
                    $productsIds[] = $option->getProductId();
                }
            }

            if ($this->getEbayListingProduct()->isPriceDiscountStp()) {
                $priceDiscountData = [
                    'original_retail_price' => $ebayVariation->getPriceDiscountStp()
                ];

                if ($this->getEbayMarketplace()->isStpAdvancedEnabled()) {
                    $priceDiscountData = array_merge(
                        $priceDiscountData,
                        $this->getEbayListingProduct()->getEbaySellingFormatTemplate()
                             ->getPriceDiscountStpAdditionalFlags()
                    );
                }

                $item['price_discount_stp'] = $priceDiscountData;
            }

            if ($this->getEbayListingProduct()->isPriceDiscountMap()) {
                $priceDiscountMapData = [
                    'minimum_advertised_price' => $ebayVariation->getPriceDiscountMap(),
                ];

                $exposure = $ebayVariation->getEbaySellingFormatTemplate()->getPriceDiscountMapExposureType();
                $priceDiscountMapData['minimum_advertised_price_exposure'] =
                    \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::
                        getPriceDiscountMapExposureType($exposure);

                $item['price_discount_map'] = $priceDiscountMapData;
            }

            $variationDetails = $this->getVariationDetails($variation);

            if (!empty($variationDetails)) {
                $item['details'] = $variationDetails;
            }

            foreach ($variation->getOptions(true) as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

                $item['specifics'][$option->getAttribute()] = $option->getOption();
            }

            //-- MPN Specific has been changed
            if (!empty($item['details']['mpn_previous']) && !empty($item['details']['mpn']) &&
                $item['details']['mpn_previous'] != $item['details']['mpn']) {
                $oneMoreVariation = [
                    'qty'       => 0,
                    'price'     => $item['price'],
                    'sku'       => 'del-' . sha1(microtime(1) . $item['sku']),
                    'add'       => 0,
                    'delete'    => 1,
                    'specifics' => $item['specifics'],
                    'has_sales' => true,
                    'details'   => $item['details']
                ];
                $oneMoreVariation['details']['mpn'] = $item['details']['mpn_previous'];

                $specificsReplacements = $this->getEbayListingProduct()->getVariationSpecificsReplacements();
                if (!empty($specificsReplacements)) {
                    $oneMoreVariation['variations_specifics_replacements'] = $specificsReplacements;
                }

                unset($item['details']['mpn_previous']);

                $this->variationsThatCanNotBeDeleted[] = $oneMoreVariation;
            }
            //--

            $data[] = $item;

            $variationIdsIndexes[$variation->getId()] = count($data) - 1;
        }

        $this->addMetaData('variation_ids_indexes', $variationIdsIndexes);

        $this->checkQtyWarnings($productsIds);

        return $data;
    }

    /**
     * @return bool|array
     */
    public function getSetsData()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (isset($additionalData['variations_sets'])) {
            return $additionalData['variations_sets'];
        }

        return false;
    }

    public function getVariationsThatCanNotBeDeleted()
    {
        $canNotBeDeleted = $this->variationsThatCanNotBeDeleted;
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (isset($additionalData['variations_that_can_not_be_deleted'])) {
            $canNotBeDeleted = array_merge(
                $canNotBeDeleted,
                $additionalData['variations_that_can_not_be_deleted']
            );
        }

        return $canNotBeDeleted;
    }

    /**
     * @return array
     */
    public function getImagesData()
    {
        $attributeLabels = [];

        if ($this->getMagentoProduct()->isConfigurableType()) {
            $attributeLabels = $this->getConfigurableImagesAttributeLabels();
        }

        if ($this->getMagentoProduct()->isGroupedType()) {
            $attributeLabels = [\Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL];
        }

        if (count($attributeLabels) <= 0) {
            return [];
        }

        return $this->getImagesDataByAttributeLabels($attributeLabels);
    }

    //########################################

    private function logLimitationsAndReasons()
    {
        if ($this->getMagentoProduct()->isProductWithoutVariations()) {
            return;
        }

        if (!$this->getEbayMarketplace()->isMultivariationEnabled()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: Marketplace allows to list only Simple Items.'
                )
            );
            return;
        }

        $isVariationEnabled = $this->getHelper('Component_Ebay_Category_Ebay')
                                    ->isVariationEnabled(
                                        (int)$this->getCategorySource()->getMainCategory(),
                                        $this->getMarketplace()->getId()
                                    );

        if ($isVariationEnabled !== null && !$isVariationEnabled) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: eBay Catalog Primary Category allows to list only Simple Items.'
                )
            );
            return;
        }

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: ignore Variation Option is enabled in Selling Policy.'
                )
            );
            return;
        }

        if (!$this->getEbayListingProduct()->isListingTypeFixed()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. '.
                    'Reason: Listing type "Auction" does not support Multi-Variations.'
                )
            );
            return;
        }
    }

    // ---------------------------------------

    private function getConfigurableImagesAttributeLabels()
    {
        $descriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        if (!$descriptionTemplate->isVariationConfigurableImages()) {
            return [];
        }

        $product = $this->getMagentoProduct()->getProduct();

        $attributeCodes = $descriptionTemplate->getDecodedVariationConfigurableImages();
        $attributes = [];

        foreach ($attributeCodes as $attributeCode) {
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

        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();
        $magentoProduct = $this->getMagentoProduct()->getProduct();

        foreach ($productTypeInstance->getConfigurableAttributes($magentoProduct) as $configurableAttribute) {

            /** @var $configurableAttribute \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute */
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

        if (empty($attributeLabels)) {
            $this->addNotFoundAttributesMessages(
                $this->getHelper('Module\Translation')->__('Change Images for Attribute'),
                $attributes
            );

            return [];
        }

        return $attributeLabels;
    }

    private function getImagesDataByAttributeLabels(array $attributeLabels)
    {
        $images = [];
        $imagesLinks = [];
        $attributeLabel = false;

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {

            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */

            if ($variation->getChildObject()->isDelete()) {
                continue;
            }

            foreach ($variation->getOptions(true) as $option) {

                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

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
                $optionImages = $this->getEbayListingProduct()->getEbayDescriptionTemplate()
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

        if (!empty($images)) {
            $this->addMetaData(
                'ebay_product_variation_images_hash',
                $this->getHelper('Component_Ebay_Images')->getHash($images, $attributeLabel)
            );
        }

        return [
            'specific' => $attributeLabel,
            'images'   => $imagesLinks
        ];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    private function getCategorySource()
    {
        return $this->getEbayListingProduct()->getCategoryTemplateSource();
    }

    //########################################

    public function checkQtyWarnings($productsIds)
    {
        $qtyMode = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtyMode();
        if ($qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED ||
            $qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) {
            $productsIds = array_unique($productsIds);
            $qtyWarnings = [];

            $listingProductId = $this->getListingProduct()->getId();
            $storeId = $this->getListing()->getStoreId();

            foreach ($productsIds as $productId) {
                if (!empty(\Ess\M2ePro\Model\Magento\Product::$statistics
                        [$listingProductId][$productId][$storeId]['qty'])) {
                    $qtys = \Ess\M2ePro\Model\Magento\Product::$statistics
                        [$listingProductId][$productId][$storeId]['qty'];
                    $qtyWarnings = array_unique(array_merge($qtyWarnings, array_keys($qtys)));
                }

                if (count($qtyWarnings) === 2) {
                    break;
                }
            }

            foreach ($qtyWarnings as $qtyWarningType) {
                $this->addQtyWarnings($qtyWarningType);
            }
        }
    }

    /**
     * @param int $type
     */
    public function addQtyWarnings($type)
    {
        if ($type === \Ess\M2ePro\Model\Magento\Product::FORCING_QTY_TYPE_MANAGE_STOCK_NO) {
        // M2ePro\TRANSLATIONS
        // During the Quantity Calculation the Settings in the "Manage Stock No" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Manage Stock No" '.
                                     'field were taken into consideration.');
        }

        if ($type === \Ess\M2ePro\Model\Magento\Product::FORCING_QTY_TYPE_BACKORDERS) {
            // M2ePro\TRANSLATIONS
            // During the Quantity Calculation the Settings in the "Backorders" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Backorders" '.
                                     'field were taken into consideration.');
        }
    }

    //########################################

    private function getVariationDetails(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        $data = [];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
        $ebayDescriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        $options = null;
        $additionalData = $variation->getAdditionalData();

        foreach (['isbn','upc','ean','mpn','epid'] as $tempType) {
            if ($tempType == 'mpn' && !empty($additionalData['online_product_details']['mpn'])) {
                $data['mpn'] = $additionalData['online_product_details']['mpn'];

                $isMpnCanBeChanged = $this->getHelper('Module')->getConfig()->getGroupValue(
                    '/component/ebay/variation/',
                    'mpn_can_be_changed'
                );

                if (!$isMpnCanBeChanged) {
                    continue;
                }

                $data['mpn_previous'] = $additionalData['online_product_details']['mpn'];
            }

            if (isset($additionalData['product_details'][$tempType])) {
                $data[$tempType] = $additionalData['product_details'][$tempType];
                continue;
            }

            if ($tempType == 'mpn') {
                if ($ebayDescriptionTemplate->isProductDetailsModeNone('brand')) {
                    continue;
                }

                if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply('brand')) {
                    $data[$tempType] = Description::PRODUCT_DETAILS_DOES_NOT_APPLY;
                    continue;
                }
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeNone($tempType)) {
                continue;
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply($tempType)) {
                $data[$tempType] = Description::PRODUCT_DETAILS_DOES_NOT_APPLY;
                continue;
            }

            if (!$this->getMagentoProduct()->isConfigurableType() &&
                !$this->getMagentoProduct()->isGroupedType()) {
                continue;
            }

            $attribute = $ebayDescriptionTemplate->getProductDetailAttribute($tempType);

            if (!$attribute) {
                continue;
            }

            if ($options === null) {
                $options = $variation->getOptions(true);
            }

            /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
            $option = reset($options);

            $this->searchNotFoundAttributes();
            $tempValue = $option->getMagentoProduct()->getAttributeValue($attribute);

            if (!$this->processNotFoundAttributes(strtoupper($tempType)) || !$tempValue) {
                continue;
            }

            $data[$tempType] = $tempValue;
        }

        return $this->deleteNotAllowedIdentifier($data);
    }

    private function deleteNotAllowedIdentifier(array $data)
    {
        if (empty($data)) {
            return $data;
        }

        $categoryId = $this->getCategorySource()->getMainCategory();
        $marketplaceId = $this->getMarketplace()->getId();
        $categoryFeatures = $this->getHelper('Component_Ebay_Category_Ebay')
                                  ->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return $data;
        }

        $statusDisabled = \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (['ean','upc','isbn','epid'] as $identifier) {
            $key = $identifier.'_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {
                unset($data[$identifier]);

                // M2ePro\TRANSLATIONS
                // The value of %type% was not sent because it is not allowed in this Category
                $this->addWarningMessage(
                    $this->getHelper('Module\Translation')->__(
                        'The value of %type% was not sent because it is not allowed in this Category',
                        $this->getHelper('Module\Translation')->__(strtoupper($identifier))
                    )
                );
            }
        }

        return $data;
    }

    //########################################
}
