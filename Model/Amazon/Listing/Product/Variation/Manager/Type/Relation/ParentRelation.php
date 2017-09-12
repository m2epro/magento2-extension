<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ChildRelation;

class ParentRelation extends \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\LogicalUnit
{
    private $processor = null;

    /** @var \Ess\M2ePro\Model\Listing\Product[] $childListingsProducts */
    private $childListingsProducts = null;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor
     */
    public function getProcessor()
    {
        if (is_null($this->processor)) {
            $this->processor = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Manager'
                . '\Type\Relation\ParentRelation\Processor');
            $this->processor->setListingProduct($this->getListingProduct());
        }

        return $this->processor;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    public function getChildListingsProducts()
    {
        if ($this->isCacheEnabled() && !is_null($this->childListingsProducts)) {
            return $this->childListingsProducts;
        }

        $collection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('variation_parent_id', $this->getListingProduct()->getId());

        /** @var \Ess\M2ePro\Model\Listing\Product[] $childListingsProducts */
        $childListingsProducts = $collection->getItems();

        if (!$this->isCacheEnabled()) {
            return $childListingsProducts;
        }

        foreach ($childListingsProducts as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonChildListingProduct */
            $amazonChildListingProduct = $childListingProduct->getChildObject();
            $amazonChildListingProduct->getVariationManager()->getTypeModel()->enableCache();
        }

        return $this->childListingsProducts = $childListingsProducts;
    }

    //########################################

    /**
     * @return bool
     */
    public function isNeedProcessor()
    {
        return (bool)$this->getAmazonListingProduct()->getData('variation_parent_need_processor');
    }

    //########################################

    /**
     * @return bool
     */
    public function isActualProductAttributes()
    {
        $productAttributes = array_map('strtolower', (array)$this->getProductAttributes());
        $magentoAttributes = array_map('strtolower', (array)$this->getMagentoAttributes());

        sort($productAttributes);
        sort($magentoAttributes);

        return $productAttributes == $magentoAttributes;
    }

    /**
     * @return bool
     */
    public function isActualRealProductAttributes()
    {
        $realProductAttributes = array_map('strtolower', (array)$this->getRealProductAttributes());
        $realMagentoAttributes = array_map('strtolower', (array)$this->getRealMagentoAttributes());

        sort($realProductAttributes);
        sort($realMagentoAttributes);

        return $realProductAttributes == $realMagentoAttributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getProductAttributes()
    {
        return array_merge($this->getRealProductAttributes(), array_keys($this->getVirtualProductAttributes()));
    }

    /**
     * @return mixed
     */
    public function getRealProductAttributes()
    {
        return parent::getProductAttributes();
    }

    //########################################

    /**
     * @param bool $save
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function resetProductAttributes($save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_product_attributes', $this->getRealMagentoAttributes()
        );

        $this->setVirtualChannelAttributes(array(), false);

        $this->restoreAllRemovedProductOptions(false);

        $save && $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @return bool
     */
    public function hasChannelTheme()
    {
        return (bool)$this->getChannelTheme();
    }

    /**
     * @return bool
     */
    public function isActualChannelTheme()
    {
        if (!$this->hasChannelTheme()) {
            return false;
        }

        $themeAttributes = $this->modelFactory->getObject('Amazon\Marketplace\Details')
            ->setMarketplaceId($this->getListingProduct()->getMarketplace()->getId())
            ->getVariationThemeAttributes(
                $this->getAmazonListingProduct()->getAmazonDescriptionTemplate()->getProductDataNick(),
                $this->getChannelTheme()
            );

        $channelAttributes = $this->getRealChannelAttributes();

        sort($themeAttributes);
        sort($channelAttributes);

        if ($this->getAmazonListingProduct()->getGeneralId() && $themeAttributes != $channelAttributes) {
            return false;
        }

        $isThemeSetManually = $this->getListingProduct()->getSetting(
            'additional_data', 'is_variation_channel_theme_set_manually', false
        );

        if ($isThemeSetManually) {
            return true;
        }

        $themeAttributesSnapshot = $this->getListingProduct()->getSetting(
            'additional_data', 'variation_channel_theme_product_attributes_snapshot', array()
        );

        $magentoAttributes = $this->getMagentoAttributes();

        sort($magentoAttributes);
        sort($themeAttributesSnapshot);

        return $themeAttributesSnapshot == $magentoAttributes;
    }

    /**
     * @return mixed
     */
    public function getChannelTheme()
    {
        return $this->getListingProduct()->getSetting('additional_data', 'variation_channel_theme', null);
    }

    // ---------------------------------------

    /**
     * @param $value
     * @param bool $isManually
     * @param bool $save
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setChannelTheme($value, $isManually = false, $save = true)
    {
        $this->getListingProduct()->setSetting('additional_data', 'variation_channel_theme', $value);

        $this->getListingProduct()->setSetting(
            'additional_data', 'is_variation_channel_theme_set_manually', $isManually
        );

        if (!$isManually && !empty($value)) {
            $this->getListingProduct()->setSetting(
                'additional_data', 'variation_channel_theme_product_attributes_snapshot', $this->getMagentoAttributes()
            );
        } else {
            $this->getListingProduct()->setSetting(
                'additional_data', 'variation_channel_theme_product_attributes_snapshot', array()
            );
        }

        $this->setVirtualProductAttributes(array(), false);
        $this->setVirtualChannelAttributes(array(), false);

        $save && $this->getListingProduct()->save();
    }

    /**
     * @param bool $save
     */
    public function resetChannelTheme($save = true)
    {
        $this->setChannelTheme(null, false, $save);
    }

    //########################################

    /**
     * @return array
     */
    public function getChannelAttributes()
    {
        if ($this->getAmazonListingProduct()->getGeneralId()) {
            return array_keys($this->getChannelAttributesSets());
        }

        if ($this->hasChannelTheme()) {
            $marketplaceDetails = $this->modelFactory->getObject('Amazon\Marketplace\Details');
            $marketplaceDetails->setMarketplaceId($this->getListingProduct()->getListing()->getMarketplaceId());

            return $marketplaceDetails->getVariationThemeAttributes(
                $this->getAmazonListingProduct()->getAmazonDescriptionTemplate()->getProductDataNick(),
                $this->getChannelTheme()
            );
        }

        return array();
    }

    /**
     * @return array
     */
    public function getRealChannelAttributes()
    {
        if ($this->getAmazonListingProduct()->getGeneralId()) {
            return array_keys($this->getRealChannelAttributesSets());
        }

        return $this->getChannelAttributes();
    }

    //########################################

    /**
     * @return bool
     */
    public function hasMatchedAttributes()
    {
        return (bool)$this->getMatchedAttributes();
    }

    /**
     * @return mixed
     */
    public function getMatchedAttributes()
    {
        $matchedAttributes = $this->getRealMatchedAttributes();
        if (empty($matchedAttributes)) {
            return array();
        }

        foreach ($this->getVirtualProductAttributes() as $attribute => $value) {
            $matchedAttributes[$attribute] = $attribute;
        }

        foreach ($this->getVirtualChannelAttributes() as $attribute => $value) {
            $matchedAttributes[$attribute] = $attribute;
        }

        return $matchedAttributes;
    }

    /**
     * @return mixed
     */
    public function getRealMatchedAttributes()
    {
        $matchedAttributes = $this->getListingProduct()->getSetting(
            'additional_data', 'variation_matched_attributes', null
        );

        if (empty($matchedAttributes)) {
            return array();
        }

        ksort($matchedAttributes);

        return $matchedAttributes;
    }

    // ---------------------------------------

    /**
     * @param array $matchedAttributes
     * @param bool $save
     */
    public function setMatchedAttributes(array $matchedAttributes, $save = true)
    {
        foreach ($this->getVirtualProductAttributes() as $attribute => $value) {
            unset($matchedAttributes[$attribute]);
        }

        foreach ($this->getVirtualChannelAttributes() as $attribute => $value) {
            unset($matchedAttributes[array_search($attribute, $matchedAttributes)]);
        }

        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_matched_attributes', $matchedAttributes
        );

        $save && $this->getListingProduct()->save();
    }

    //########################################

    public function getVirtualProductAttributes()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data', 'variation_virtual_product_attributes', array()
        );
    }

    public function setVirtualProductAttributes(array $attributes, $save = true)
    {
        if (array_intersect(array_keys($attributes), $this->getRealProductAttributes())) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Virtual product attributes are intersect with real attributes'
            );
        }

        if (!empty($attributes)) {
            $this->setVirtualChannelAttributes(array(), false);
        }

        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_virtual_product_attributes', $attributes
        );

        $save && $this->getListingProduct()->save();
    }

    public function isActualVirtualProductAttributes()
    {
        if (!$this->getVirtualProductAttributes()) {
            return true;
        }

        if ($this->getAmazonListingProduct()->getGeneralId()) {
            $channelAttributesSets = $this->getRealChannelAttributesSets();

            foreach ($this->getVirtualProductAttributes() as $attribute => $value) {
                if (!isset($channelAttributesSets[$attribute])) {
                    return false;
                }

                $channelAttributeValues = $channelAttributesSets[$attribute];
                if (!in_array($value, $channelAttributeValues) && !empty($channelAttributeValues)) {
                    return false;
                }
            }

            return true;
        }

        if ($this->getChannelTheme()) {
            $marketplaceDetails = $this->modelFactory->getObject('Amazon\Marketplace\Details');
            $marketplaceDetails->setMarketplaceId($this->getListingProduct()->getListing()->getMarketplaceId());

            $themeAttributes = $marketplaceDetails->getVariationThemeAttributes(
                $this->getAmazonListingProduct()->getAmazonDescriptionTemplate()->getProductDataNick(),
                $this->getChannelTheme()
            );

            $virtualProductAttributes = array_keys($this->getVirtualProductAttributes());

            return !array_diff($virtualProductAttributes, $themeAttributes);
        }

        return false;
    }

    // ---------------------------------------

    public function getVirtualChannelAttributes()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data', 'variation_virtual_channel_attributes', array()
        );
    }

    public function setVirtualChannelAttributes(array $attributes, $save = true)
    {
        if (array_intersect(array_keys($attributes), $this->getRealChannelAttributes())) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Virtual channel attributes are intersect with real attributes'
            );
        }

        if (!empty($attributes)) {
            $this->setVirtualProductAttributes(array(), false);
        }

        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_virtual_channel_attributes', $attributes
        );

        $save && $this->getListingProduct()->save();
    }

    /**
     * @return bool
     */
    public function isActualVirtualChannelAttributes()
    {
        if (!$this->getVirtualChannelAttributes()) {
            return true;
        }

        $magentoVariations = $this->getRealMagentoVariations();
        $magentoVariationsSet = $magentoVariations['set'];

        foreach ($this->getVirtualChannelAttributes() as $attribute => $value) {
            if (!isset($magentoVariationsSet[$attribute])) {
                return false;
            }

            $productAttributeValues = $magentoVariationsSet[$attribute];
            if (!in_array($value, $productAttributeValues)) {
                return false;
            }
        }

        return true;
    }

    //########################################

    public function getChannelAttributesSets()
    {
        $attributesSets = $this->getListingProduct()->getSetting(
            'additional_data', 'variation_channel_attributes_sets', null
        );

        if (empty($attributesSets)) {
            return array();
        }

        foreach ($this->getVirtualChannelAttributes() as $virtualAttribute => $virtualValue) {
            $attributesSets[$virtualAttribute] = array($virtualValue);
        }

        $virtualProductAttributes = $this->getVirtualProductAttributes();

        if (!empty($virtualProductAttributes)) {
            foreach ($attributesSets as $attribute => $values) {
                if (!isset($virtualProductAttributes[$attribute])) {
                    continue;
                }

                $virtualValue = $virtualProductAttributes[$attribute];
                if (!in_array($virtualValue, $values)) {
                    $attributesSets[$attribute] = array();
                    continue;
                }

                $attributesSets[$attribute] = array($virtualValue);
            }
        }

        return $attributesSets;
    }

    public function getRealChannelAttributesSets()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data', 'variation_channel_attributes_sets', null
        );
    }

    public function setChannelAttributesSets(array $channelAttributesSets, $save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_channel_attributes_sets', $channelAttributesSets
        );
        $save && $this->getListingProduct()->save();
    }

    // ---------------------------------------

    public function getChannelVariations()
    {
        $channelVariations = $this->getListingProduct()->getSetting(
            'additional_data', 'variation_channel_variations', null
        );

        if (empty($channelVariations)) {
            return array();
        }

        $virtualChannelAttributes = $this->getVirtualChannelAttributes();
        if (!empty($virtualChannelAttributes)) {
            foreach ($channelVariations as $generalId => $channelOptions) {
                $channelVariations[$generalId] = $channelOptions + $virtualChannelAttributes;
            }
        }

        $virtualProductAttributes = $this->getVirtualProductAttributes();
        if (!empty($virtualProductAttributes)) {
            foreach ($channelVariations as $generalId => $channelOptions) {
                foreach ($channelOptions as $attribute => $value) {
                    if (!isset($virtualProductAttributes[$attribute])) {
                        continue;
                    }

                    if ($virtualProductAttributes[$attribute] == $value) {
                        continue;
                    }

                    unset($channelVariations[$generalId]);
                    break;
                }
            }
        }

        return $channelVariations;
    }

    public function getRealChannelVariations()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data', 'variation_channel_variations', null
        );
    }

    public function getChannelVariationGeneralId(array $options)
    {
        foreach ($this->getChannelVariations() as $asin => $variation) {
            if ($options == $variation) {
                return $asin;
            }
        }

        return null;
    }

    public function setChannelVariations(array $channelVariations, $save = true)
    {
        $this->getListingProduct()->setSetting('additional_data', 'variation_channel_variations', $channelVariations);
        $save && $this->getListingProduct()->save();
    }

    // ---------------------------------------

    public function getRemovedProductOptions()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data', 'variation_removed_product_variations', array()
        );
    }

    public function isProductsOptionsRemoved(array $productOptions)
    {
        foreach ($this->getRemovedProductOptions() as $removedProductOptions) {
            if ($productOptions != $removedProductOptions) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function addRemovedProductOptions(array $productOptions, $save = true)
    {
        if ($this->isProductsOptionsRemoved($productOptions)) {
            return;
        }

        $removedProductOptions = $this->getListingProduct()->getSetting(
            'additional_data', 'variation_removed_product_variations', array()
        );

        $removedProductOptions[] = $productOptions;

        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_removed_product_variations', $removedProductOptions
        );
        $save && $this->getListingProduct()->save();
    }

    public function restoreRemovedProductOptions(array $productOptions, $save = true)
    {
        if (!$this->isProductsOptionsRemoved($productOptions)) {
            return;
        }

        $removedProductOptions = $this->getRemovedProductOptions();

        foreach ($removedProductOptions as $key => $removedOptions) {
            if ($productOptions != $removedOptions) {
                continue;
            }

            unset($removedProductOptions[$key]);
            break;
        }

        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_removed_product_variations', $removedProductOptions
        );
        $save && $this->getListingProduct()->save();
    }

    public function restoreAllRemovedProductOptions($save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data', 'variation_removed_product_variations', array()
        );
        $save && $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @param bool $freeOptionsFilter
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getUsedProductOptions($freeOptionsFilter = false)
    {
        $usedVariations = array();

        foreach ($this->getChildListingsProducts() as $childListingProduct) {
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched() ||
                ($childTypeModel->isVariationChannelMatched() && $freeOptionsFilter)
            ) {
                continue;
            }

            if ($freeOptionsFilter
                && ($childListingProduct->isLocked()
                    || $childListingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)
            ) {
                continue;
            }

            $usedVariations[] = $childTypeModel->getProductOptions();
        }

        return $usedVariations;
    }

    /**
     * @param bool $freeOptionsFilter
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getUsedChannelOptions($freeOptionsFilter = false)
    {
        $usedOptions = array();

        foreach ($this->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            /** @var ChildRelation $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationChannelMatched() ||
                ($childTypeModel->isVariationProductMatched() && $freeOptionsFilter)
            ) {
                continue;
            }

            if ($freeOptionsFilter
                && ($childListingProduct->isLocked()
                    || $childListingProduct->getStatus() != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)
            ) {
                continue;
            }

            $usedOptions[] = $childTypeModel->getChannelOptions();
        }

        return $usedOptions;
    }

    // ---------------------------------------

    public function getUnusedProductOptions()
    {
        return $this->getUnusedOptions($this->getCurrentProductOptions(), $this->getUsedProductOptions());
    }

    public function getNotRemovedUnusedProductOptions()
    {
        return $this->getUnusedOptions($this->getUnusedProductOptions(), $this->getRemovedProductOptions());
    }

    public function getUnusedChannelOptions()
    {
        return $this->getUnusedOptions($this->getChannelVariations(), $this->getUsedChannelOptions());
    }

    private function getUnusedOptions($currentOptions, $usedOptions)
    {
        if (empty($currentOptions)) {
            return array();
        }

        if (empty($usedOptions)) {
            return $currentOptions;
        }

        $unusedOptions = array();

        foreach ($currentOptions as $id => $currentOption) {

            $isExist = false;
            foreach ($usedOptions as $option) {
                if ($option != $currentOption) {
                    continue;
                }

                $isExist = true;
                break;
            }

            if ($isExist) {
                continue;
            }

            $unusedOptions[$id] = $currentOption;
        }

        return $unusedOptions;
    }

    // ---------------------------------------

    private function getCurrentProductOptions()
    {
        $magentoProductVariations = $this->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();

        $productOptions = array();

        foreach ($magentoProductVariations['variations'] as $option) {
            $productOption = array();

            foreach ($option as $attribute) {
                $productOption[$attribute['attribute']] = $attribute['option'];
            }

            $productOptions[] = $productOption;
        }

        return $productOptions;
    }

    //########################################

    /**
     * @param array $productOptions
     * @param array $channelOptions
     * @param $generalId
     * @return \Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function createChildListingProduct(array $productOptions,
                                              array $channelOptions = array(),
                                              $generalId = null)
    {
        $data = array(
            'listing_id' => $this->getListingProduct()->getListingId(),
            'product_id' => $this->getListingProduct()->getProductId(),
            'status'     => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            'general_id' => $generalId,
            'is_general_id_owner' => $this->getAmazonListingProduct()->isGeneralIdOwner(),
            'status_changer'      => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN,
            'is_variation_product'    => 1,
            'is_variation_parent'     => 0,
            'variation_parent_id'     => $this->getListingProduct()->getId(),
            'template_description_id' => $this->getAmazonListingProduct()->getTemplateDescriptionId(),
            'template_shipping_template_id' => $this->getAmazonListingProduct()->getTemplateShippingTemplateId(),
            'template_shipping_override_id' => $this->getAmazonListingProduct()->getTemplateShippingOverrideId(),
            'template_product_tax_code_id'  => $this->getAmazonListingProduct()->getTemplateProductTaxCodeId(),
        );

        /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */
        $childListingProduct = $this->amazonFactory->getObject('Listing\Product')->setData($data);
        $childListingProduct->save();

        if ($this->isCacheEnabled()) {
            $this->childListingsProducts[$childListingProduct->getId()] = $childListingProduct;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonChildListingProduct */
        $amazonChildListingProduct = $childListingProduct->getChildObject();

        $childTypeModel = $amazonChildListingProduct->getVariationManager()->getTypeModel();

        if (!empty($productOptions)) {
            $productVariation = $this->getListingProduct()
                ->getMagentoProduct()
                ->getVariationInstance()
                ->getVariationTypeStandard($productOptions);

            $childTypeModel->setProductVariation($productVariation);
        }

        if (!empty($channelOptions)) {
            $childTypeModel->setChannelVariation($channelOptions);
        }

        return $childListingProduct;
    }

    /**
     * @param $listingProductId
     * @return bool
     */
    public function removeChildListingProduct($listingProductId)
    {
        $childListingsProducts = $this->getChildListingsProducts();
        if (!isset($childListingsProducts[$listingProductId])) {
            return false;
        }

        if (!$childListingsProducts[$listingProductId]->delete()) {
            return false;
        }

        if ($this->isCacheEnabled()) {
            unset($this->childListingsProducts[$listingProductId]);
        }

        return true;
    }

    //########################################

    public function clearTypeData()
    {
        parent::clearTypeData();

        $additionalData = $this->getListingProduct()->getAdditionalData();

        unset($additionalData['variation_channel_theme']);
        unset($additionalData['is_variation_channel_theme_set_manually']);
        unset($additionalData['variation_channel_theme_product_attributes_snapshot']);

        unset($additionalData['variation_matched_attributes']);
        unset($additionalData['variation_virtual_product_attributes']);
        unset($additionalData['variation_virtual_channel_attributes']);
        unset($additionalData['variation_channel_attributes_sets']);
        unset($additionalData['variation_channel_variations']);
        unset($additionalData['variation_removed_product_variations']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
        $this->getListingProduct()->getChildObject()->setData('variation_parent_need_processor', 0);
        $this->getListingProduct()->save();

        foreach ($this->getChildListingsProducts() as $childListingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $childVariationManager */
            $childVariationManager = $childListingProduct->getChildObject()->getVariationManager();

            if ($this->getMagentoProduct()->isProductWithVariations()) {
                $childVariationManager->getTypeModel()->unsetChannelVariation();
                $childVariationManager->setIndividualType();
            } else {
                $childVariationManager->setSimpleType();
            }

            $childListingProduct->save();
        }
    }

    //########################################

    public function getRealMagentoAttributes()
    {
        $magentoVariations = $this->getRealMagentoVariations();
        return array_keys($magentoVariations['set']);
    }

    public function getRealMagentoVariations()
    {
        $this->getMagentoProduct()->setIgnoreVariationVirtualAttributes(true);
        $this->getMagentoProduct()->setIgnoreVariationFilterAttributes(true);

        $magentoVariations = $this->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();

        $this->getMagentoProduct()->setIgnoreVariationVirtualAttributes(false);
        $this->getMagentoProduct()->setIgnoreVariationFilterAttributes(false);

        return $magentoVariations;
    }

    //########################################
}