<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation
 */
class ParentRelation extends \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\LogicalUnit
{
    /** @var Processor $processor */
    private $processor = null;

    /** @var \Ess\M2ePro\Model\Listing\Product[] $childListingsProducts */
    private $childListingsProducts = null;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor
     */
    public function getProcessor()
    {
        if ($this->processor === null) {
            $this->processor = $this->modelFactory->getObject('Walmart\Listing\Product\Variation\Manager' .
                '\Type\Relation\ParentRelation\Processor');
            $this->processor->setListingProduct($this->getListingProduct());
        }

        return $this->processor;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    public function getChildListingsProducts()
    {
        if ($this->isCacheEnabled() && $this->childListingsProducts !== null) {
            return $this->childListingsProducts;
        }

        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('variation_parent_id', $this->getListingProduct()->getId());

        /** @var \Ess\M2ePro\Model\Listing\Product[] $childListingsProducts */
        $childListingsProducts = $collection->getItems();

        if (!$this->isCacheEnabled()) {
            return $childListingsProducts;
        }

        foreach ($childListingsProducts as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartChildListingProduct */
            $walmartChildListingProduct = $childListingProduct->getChildObject();
            $walmartChildListingProduct->getVariationManager()->getTypeModel()->enableCache();
        }

        return $this->childListingsProducts = $childListingsProducts;
    }

    //########################################

    /**
     * @return bool
     */
    public function isNeedProcessor()
    {
        return (bool)$this->getWalmartListingProduct()->getData('variation_parent_need_processor');
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
            'additional_data',
            'variation_product_attributes',
            $this->getRealMagentoAttributes()
        );

        $this->setVirtualChannelAttributes([], false);

        $this->restoreAllRemovedProductOptions(false);

        $save && $this->getListingProduct()->save();
    }

    //########################################

    /**
     * @return bool
     */
    public function hasChannelGroupId()
    {
        return (bool)$this->getChannelGroupId();
    }

    public function setChannelGroupId($groupId, $save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_channel_group_id',
            $groupId
        );

        $save && $this->getListingProduct()->save();
    }

    public function getChannelGroupId()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_channel_group_id',
            null
        );
    }

    //########################################

    /**
     * @return bool
     */
    public function hasChannelAttributes()
    {
        $channelAttributes = $this->getChannelAttributes();

        return !empty($channelAttributes);
    }

    public function setChannelAttributes(array $attributes, $save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_channel_attributes',
            $attributes
        );

        $this->setVirtualProductAttributes([], false);
        $this->setVirtualChannelAttributes([], false);

        $save && $this->getListingProduct()->save();
    }

    /**
     * @return array
     */
    public function getChannelAttributes()
    {
        $attributes = $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_channel_attributes',
            null
        );

        if (empty($attributes)) {
            return [];
        }

        $attributes = array_merge($attributes, array_keys($this->getVirtualChannelAttributes()));

        return array_unique($attributes);
    }

    /**
     * @return array
     */
    public function getRealChannelAttributes()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_channel_attributes',
            []
        );
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
            return [];
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
            'additional_data',
            'variation_matched_attributes',
            null
        );

        if (empty($matchedAttributes)) {
            return [];
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
            'additional_data',
            'variation_matched_attributes',
            $matchedAttributes
        );

        $save && $this->getListingProduct()->save();
    }

    //########################################

    public function getVirtualProductAttributes()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_virtual_product_attributes',
            []
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
            $this->setVirtualChannelAttributes([], false);
        }

        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_virtual_product_attributes',
            $attributes
        );

        $save && $this->getListingProduct()->save();
    }

    public function isActualVirtualProductAttributes()
    {
        if (!$this->getVirtualProductAttributes()) {
            return true;
        }

        $channelAttributes = $this->getRealChannelAttributes();

        if (empty($channelAttributes)) {
            return true;
        }

        return !array_diff(array_keys($this->getVirtualProductAttributes()), $channelAttributes);
    }

    // ---------------------------------------

    public function getVirtualChannelAttributes()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_virtual_channel_attributes',
            []
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
            $this->setVirtualProductAttributes([], false);
        }

        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_virtual_channel_attributes',
            $attributes
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

    public function getRemovedProductOptions()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_removed_product_variations',
            []
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
            'additional_data',
            'variation_removed_product_variations',
            []
        );

        $removedProductOptions[] = $productOptions;

        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_removed_product_variations',
            $removedProductOptions
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
            'additional_data',
            'variation_removed_product_variations',
            $removedProductOptions
        );
        $save && $this->getListingProduct()->save();
    }

    public function restoreAllRemovedProductOptions($save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_removed_product_variations',
            []
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
        $usedVariations = [];

        foreach ($this->getChildListingsProducts() as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
            $childTypeModel = $childListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if (!$childTypeModel->isVariationProductMatched()) {
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

    // ---------------------------------------

    public function getUnusedProductOptions()
    {
        return $this->getUnusedOptions($this->getCurrentProductOptions(), $this->getUsedProductOptions());
    }

    public function getNotRemovedUnusedProductOptions()
    {
        return $this->getUnusedOptions($this->getUnusedProductOptions(), $this->getRemovedProductOptions());
    }

    private function getUnusedOptions($currentOptions, $usedOptions)
    {
        if (empty($currentOptions)) {
            return [];
        }

        if (empty($usedOptions)) {
            return $currentOptions;
        }

        $unusedOptions = [];

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

        $productOptions = [];

        foreach ($magentoProductVariations['variations'] as $option) {
            $productOption = [];

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
     * @return \Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function createChildListingProduct(array $productOptions, array $channelOptions)
    {
        $data = [
            'listing_id'           => $this->getListingProduct()->getListingId(),
            'product_id'           => $this->getListingProduct()->getProductId(),
            'status'               => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            'status_changer'       => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN,
            'is_variation_product' => 1,
            'is_variation_parent'  => 0,
            'variation_parent_id'  => $this->getListingProduct()->getId(),
            'template_category_id' => $this->getWalmartListingProduct()->getTemplateCategoryId(),
        ];

        /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */
        $childListingProduct = $this->walmartFactory->getObject('Listing\Product')->setData($data);
        $childListingProduct->save();

        if ($this->isCacheEnabled()) {
            $this->childListingsProducts[$childListingProduct->getId()] = $childListingProduct;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartChildListingProduct */
        $walmartChildListingProduct = $childListingProduct->getChildObject();

        $childTypeModel = $walmartChildListingProduct->getVariationManager()->getTypeModel();

        if (!empty($productOptions)) {
            $productVariation = $this->getListingProduct()
                ->getMagentoProduct()
                ->getVariationInstance()
                ->getVariationTypeStandard($productOptions);

            $childTypeModel->setProductVariation($productVariation);
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
        unset($additionalData['variation_channel_attributes']);
        unset($additionalData['variation_channel_variations']);
        unset($additionalData['variation_removed_product_variations']);

        $this->getListingProduct()->setSettings('additional_data', $additionalData);
        $this->getWalmartListingProduct()->setData('variation_parent_need_processor', 0);
        $this->getListingProduct()->save();

        foreach ($this->getChildListingsProducts() as $childListingProduct) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager $childVariationManager */
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

    public function setSwatchImagesAttribute($attribute, $save = true)
    {
        $this->getListingProduct()->setSetting(
            'additional_data',
            'variation_swatch_images_attribute',
            $attribute
        );
        $save && $this->getListingProduct()->save();
    }

    public function getSwatchImagesAttribute()
    {
        return $this->getListingProduct()->getSetting(
            'additional_data',
            'variation_swatch_images_attribute'
        );
    }

    //########################################
}
