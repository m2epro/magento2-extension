<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Variations
 */
class Variations extends AbstractModel
{
    protected $variationsThatCanNotBeDeleted = [];

    //########################################

    public function getBuilderData()
    {
        $data = [
            'is_variation_item' => $this->isVariationItem
        ];

        $this->logLimitationsAndReasons();

        if (!$this->isVariationItem) {
            return $data;
        }

        $data['variation'] = $this->getVariationsData();

        if ($sets = $this->getSetsData()) {
            $data['variations_sets'] = $sets;
        }

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
    protected function getVariationsData()
    {
        $data = [];

        $qtyMode = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtyMode();

        $productsIds = [];
        $variationMetaData = [];

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
            /** @var $ebayVariation \Ess\M2ePro\Model\Ebay\Listing\Product\Variation */

            $ebayVariation = $variation->getChildObject();

            $item = [
                '_instance_' => $variation,
                'qty' => $ebayVariation->isDelete() ? 0 : $ebayVariation->getQty(),
                'sku' => $this->getSku($variation),
                'add' => $ebayVariation->isAdd(),
                'delete' => $ebayVariation->isDelete(),
                'specifics' => []
            ];
            if ($ebayVariation->isDelete()) {
                if ($ebayVariation->getOnlineQtySold() === 0 &&
                    ($ebayVariation->isStopped() || $ebayVariation->isHidden())) {
                    $ebayVariation->getParentObject()->delete();
                    continue;
                }

                $item['sku'] = 'del-' . sha1(microtime(1) . $ebayVariation->getOnlineSku());
            }

            // @codingStandardsIgnoreLine
            $item = array_merge($item, $this->getVariationPriceData($variation));

            if (($qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED ||
                    $qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) && !$item['delete']) {
                foreach ($variation->getOptions(true) as $option) {
                    $productsIds[] = $option->getProductId();
                }
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

            if (isset($item['price']) && $variation->getChildObject()->getOnlinePrice() == $item['price']) {
                $item['price_not_changed'] = true;
            }

            if (isset($item['qty']) && $variation->getChildObject()->getOnlineQty() == $item['qty']) {
                $item['qty_not_changed'] = true;
            }

            $data[] = $item;
            $variationMetaData[$variation->getId()] = [
                // @codingStandardsIgnoreLine
                'index'        => count($data) - 1,
                'online_qty'   => $variation->getChildObject()->getOnlineQty(),
                'online_price' => $variation->getChildObject()->getOnlinePrice()
            ];
        }

        $this->addMetaData('variation_data', $variationMetaData);

        $this->checkQtyWarnings($productsIds);

        return $data;
    }

    protected function getSku(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
        $ebayVariation = $variation->getChildObject();

        if ($ebayVariation->getOnlineSku()) {
            return $ebayVariation->getOnlineSku();
        }

        $sku = $ebayVariation->getSku();

        if (strlen($sku) >= \Ess\M2ePro\Helper\Component\Ebay::VARIATION_SKU_MAX_LENGTH) {
            $sku = $this->getHelper('Data')->hashString($sku, 'sha1', 'RANDOM_');
        }

        return $sku;
    }

    /**
     * @return bool|array
     */
    protected function getSetsData()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();

        if (isset($additionalData['variations_sets'])) {
            return $additionalData['variations_sets'];
        }

        return false;
    }

    protected function getVariationsThatCanNotBeDeleted()
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

    //########################################

    protected function getVariationPriceData(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        $priceData = [];

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
        $ebayVariation = $variation->getChildObject();

        if (isset($this->validatorsData['variation_fixed_price_' . $variation->getId()])) {
            $priceData['price'] = $this->cachedData['variation_fixed_price_' . $variation->getId()];
        } else {
            $priceData['price'] = $ebayVariation->getPrice();
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

            $priceData['price_discount_stp'] = $priceDiscountData;
        }

        if ($this->getEbayListingProduct()->isPriceDiscountMap()) {
            $priceDiscountMapData = [
                'minimum_advertised_price' => $ebayVariation->getPriceDiscountMap(),
            ];

            $exposure = $ebayVariation->getEbaySellingFormatTemplate()->getPriceDiscountMapExposureType();
            $priceDiscountMapData['minimum_advertised_price_exposure'] =
                \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Price::
                getPriceDiscountMapExposureType($exposure);

            $priceData['price_discount_map'] = $priceDiscountMapData;
        }

        return $priceData;
    }

    protected function logLimitationsAndReasons()
    {
        if ($this->getMagentoProduct()->isProductWithoutVariations()) {
            return;
        }

        if (!$this->getEbayMarketplace()->isMultivariationEnabled()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. ' .
                    'Reason: Marketplace allows to list only Simple Items.'
                )
            );
            return;
        }

        $isVariationEnabled = $this->getHelper('Component_Ebay_Category_Ebay')
            ->isVariationEnabled(
                (int)$this->getCategorySource()->getCategoryId(),
                $this->getMarketplace()->getId()
            );

        if ($isVariationEnabled !== null && !$isVariationEnabled) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. ' .
                    'Reason: eBay Primary Category allows to list only Simple Items.'
                )
            );
            return;
        }

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. ' .
                    'Reason: ignore Variation Option is enabled in Selling Policy.'
                )
            );
            return;
        }

        if (!$this->getEbayListingProduct()->isListingTypeFixed()) {
            $this->addWarningMessage(
                $this->getHelper('Module\Translation')->__(
                    'The Product was Listed as a Simple Product as it has limitation for Multi-Variation Items. ' .
                    'Reason: Listing type "Auction" does not support Multi-Variations.'
                )
            );
            return;
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    protected function getCategorySource()
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
                if (!empty(
                    \Ess\M2ePro\Model\Magento\Product::$statistics
                    [$listingProductId][$productId][$storeId]['qty']
                )) {
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
            $this->addWarningMessage(
                'During the Quantity Calculation the Settings in the "Manage Stock No" ' .
                'field were taken into consideration.'
            );
        }

        if ($type === \Ess\M2ePro\Model\Magento\Product::FORCING_QTY_TYPE_BACKORDERS) {
            $this->addWarningMessage(
                'During the Quantity Calculation the Settings in the "Backorders" ' .
                'field were taken into consideration.'
            );
        }
    }

    //########################################

    protected function getVariationDetails(\Ess\M2ePro\Model\Listing\Product\Variation $variation)
    {
        $data = [];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Description $ebayDescriptionTemplate */
        $ebayDescriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        $options = null;
        $additionalData = $variation->getAdditionalData();

        foreach (['isbn', 'upc', 'ean', 'mpn', 'epid'] as $tempType) {
            if ($tempType == 'mpn' && !empty($additionalData['online_product_details']['mpn'])) {
                $data['mpn'] = $additionalData['online_product_details']['mpn'];

                $isMpnCanBeChanged = $this->getHelper('Component_Ebay_Configuration')
                    ->getVariationMpnCanBeChanged();

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
                    $data[$tempType] = \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::
                    PRODUCT_DETAILS_DOES_NOT_APPLY;
                    continue;
                }
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeNone($tempType)) {
                continue;
            }

            if ($ebayDescriptionTemplate->isProductDetailsModeDoesNotApply($tempType)) {
                $data[$tempType] = \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General::
                PRODUCT_DETAILS_DOES_NOT_APPLY;
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

    protected function deleteNotAllowedIdentifier(array $data)
    {
        if (empty($data)) {
            return $data;
        }

        $categoryId = $this->getCategorySource()->getCategoryId();
        $marketplaceId = $this->getMarketplace()->getId();
        $categoryFeatures = $this->getHelper('Component_Ebay_Category_Ebay')
            ->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return $data;
        }

        $statusDisabled = \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (['ean', 'upc', 'isbn', 'epid'] as $identifier) {
            $key = $identifier . '_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {
                unset($data[$identifier]);

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
