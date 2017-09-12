<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

class Variation extends \Ess\M2ePro\Model\AbstractModel
{
    const GROUPED_PRODUCT_ATTRIBUTE_LABEL              = 'Option';
    const DOWNLOADABLE_PRODUCT_DEFAULT_ATTRIBUTE_LABEL = 'Links';

    protected $productFactory;
    protected $entityOptionCollectionFactory;
    protected $productOptionCollectionFactory;
    protected $storeManager;
    protected $bundleOptionFactory;
    protected $bundleSelectionCollectionFactory;
    protected $downloadableLinkFactory;

    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    protected $magentoProduct;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $entityOptionCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $productOptionCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Bundle\Model\OptionFactory $bundleOptionFactory,
        \Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory $bundleSelectionCollectionFactory,
        \Magento\Downloadable\Model\LinkFactory $downloadableLinkFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->productFactory = $productFactory;
        $this->entityOptionCollectionFactory = $entityOptionCollectionFactory;
        $this->productOptionCollectionFactory = $productOptionCollectionFactory;
        $this->storeManager = $storeManager;
        $this->bundleOptionFactory = $bundleOptionFactory;
        $this->bundleSelectionCollectionFactory = $bundleSelectionCollectionFactory;
        $this->downloadableLinkFactory = $downloadableLinkFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    //########################################

    public function getVariationTypeStandard(array $options)
    {
        $variations = $this->getVariationsTypeStandard();

        foreach ($variations['variations'] as $variation) {

            $tempOption = array();
            foreach ($variation as $variationOption) {
                $tempOption[$variationOption['attribute']] = $variationOption['option'];
            }

            if ($options == $tempOption) {
                return $variation;
            }
        }

        return null;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getVariationsTypeStandard()
    {
        $variations = array();
        $variationsSet = array();
        $additional = array();

        if ($this->getMagentoProduct()->isConfigurableType()) {

            $tempInfo = $this->getConfigurableVariationsTypeStandard();
            isset($tempInfo['set']) && $variationsSet = $tempInfo['set'];
            isset($tempInfo['variations']) && $variations = $tempInfo['variations'];
            isset($tempInfo['additional']) && $additional = $tempInfo['additional'];

        } else {

            if ($this->getMagentoProduct()->isSimpleType()) {

                $tempInfo = $this->getSimpleVariationsTypeStandard();
                isset($tempInfo['set']) && $variationsSet = $tempInfo['set'];
                isset($tempInfo['variations']) && $variations = $tempInfo['variations'];

            } else if ($this->getMagentoProduct()->isBundleType()) {

                $tempInfo = $this->getBundleVariationsTypeStandard();
                isset($tempInfo['set']) && $variationsSet = $tempInfo['set'];
                isset($tempInfo['variations']) && $variations = $tempInfo['variations'];

            } elseif ($this->getMagentoProduct()->isGroupedType()) {

                $tempInfo = $this->getGroupedVariationsTypeStandard();
                isset($tempInfo['set']) && $variationsSet = $tempInfo['set'];
                isset($tempInfo['variations']) && $variations = $tempInfo['variations'];

            } elseif ($this->getMagentoProduct()->isDownloadableType()) {

                $tempInfo = $this->getDownloadableVariationsTypeStandard();
                isset($tempInfo['set']) && $variationsSet = $tempInfo['set'];
                isset($tempInfo['variations']) && $variations = $tempInfo['variations'];
            }

            $countOfCombinations = 1;

            foreach ($variationsSet as $set) {
                $countOfCombinations *= count($set);
            }

            if ($countOfCombinations > 100000) {
                $variationsSet = array();
                $variations = array();
            } else {
                $this->prepareVariationsScopeTypeStandard($variations);
                $variations = $this->prepareVariationsTypeStandard($variations, $variationsSet);
            }
        }

        if ($this->getMagentoProduct()->getVariationVirtualAttributes() &&
            !$this->getMagentoProduct()->isIgnoreVariationVirtualAttributes()
        ) {
            $this->injectVirtualAttributesTypeStandard($variations, $variationsSet);
        }

        if ($this->getMagentoProduct()->getVariationFilterAttributes() &&
            !$this->getMagentoProduct()->isIgnoreVariationFilterAttributes()
        ) {
            $this->filterByAttributesTypeStandard($variations, $variationsSet);
        }

        return array(
            'set'        => $variationsSet,
            'variations' => $variations,
            'additional' => $additional
        );
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getSimpleVariationsTypeStandard()
    {
        if (!$this->getMagentoProduct()->isSimpleType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $variationOptionsTitle = array();
        $variationOptionsList = array();

        foreach ($product->getOptions() as $productCustomOptions) {

            if (!(bool)(int)$productCustomOptions->getData('is_require')) {
                continue;
            }

            if (in_array($productCustomOptions->getType(), $this->getCustomOptionsAllowedTypes())) {

                $optionCombinationTitle = array();
                $possibleVariationProductOptions = array();

                $optionTitle = $productCustomOptions->getTitle();
                if ($optionTitle == '') {
                    $optionTitle = $productCustomOptions->getDefaultTitle();
                }

                foreach ($productCustomOptions->getValues() as $option) {

                    $optionCombinationTitle[] = $option->getTitle();

                    $possibleVariationProductOptions[] = array(
                        'product_id'   => $product->getId(),
                        'product_type' => $product->getTypeId(),
                        'attribute'    => $optionTitle,
                        'option'       => $option->getTitle(),
                    );
                }

                $variationOptionsTitle[$optionTitle] = $optionCombinationTitle;
                $variationOptionsList[] = $possibleVariationProductOptions;
            }
        }

        return array(
            'set'        => $variationOptionsTitle,
            'variations' => $variationOptionsList,
        );
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getConfigurableVariationsTypeStandard()
    {
        if (!$this->getMagentoProduct()->isConfigurableType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();

        $attributes = array();
        $set = array();

        foreach ($productTypeInstance->getConfigurableAttributes($product) as $configurableAttribute) {

            /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute $configurableAttribute */
            $configurableAttribute->setStoreId($this->getMagentoProduct()->getStoreId());

            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attribute = $configurableAttribute->getProductAttribute();
            if (!$attribute) {
                $message = "Configurable Magento Product (ID {$this->getMagentoProduct()->getProductId()})";
                $message .= ' has no selected configurable attribute.';
                throw new \Ess\M2ePro\Model\Exception($message);
            }
            $attribute->setStoreId($this->getMagentoProduct()->getStoreId());

            if ($this->getMagentoProduct()->getStoreId()) {
                $attributeLabel = $attribute->getStoreLabel($this->getMagentoProduct()->getStoreId());
            } else {
                $attributeLabel = $configurableAttribute->getLabel();
            }

            $attributes[$attribute->getAttributeCode()] = $attributeLabel;
            $set[$attribute->getAttributeCode()] = array(
                'label'   => $attributeLabel,
                'options' => array(),
            );
        }

        $variations = array();

        foreach ($productTypeInstance->getUsedProducts($product, null) as $childProduct) {

            $variation = array();
            $childProduct->setStoreId($this->getMagentoProduct()->getStoreId());

            foreach ($attributes as $attributeCode => $attributeLabel) {

                $attributeValue = $this->modelFactory->getObject('Magento\Product')
                    ->setProduct($childProduct)
                    ->getAttributeValue($attributeCode);

                if (empty($attributeValue)) {
                    break;
                }

                $variation[] = array(
                    'product_id'     => $childProduct->getId(),
                    'product_type'   => $product->getTypeId(),
                    'attribute'      => $attributeLabel,
                    'attribute_code' => $attributeCode,
                    'option'         => $attributeValue,
                );
            }

            if (count($attributes) == count($variation)) {
                $variations[] = $variation;
            }
        }

        foreach ($variations as $variation) {
            foreach ($variation as $option) {
                $set[$option['attribute_code']]['options'][] = $option['option'];
            }
        }

        $resultSet = array();
        foreach ($set as $code => $data) {
            $options = array();
            if (!empty($data['options'])) {
                $options = $this->sortAttributeOptions($code, array_values(array_unique($data['options'])));
            }

            $resultSet[$data['label']] = $options;
        }

        return array(
            'set'        => $resultSet,
            'variations' => $variations,
            'additional' => array(
                'attributes' => $attributes
            )
        );
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getGroupedVariationsTypeStandard()
    {
        if (!$this->getMagentoProduct()->isGroupedType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $optionCombinationTitle = array();

        $possibleVariationProductOptions = array();
        $typeInstance = $this->getMagentoProduct()->getTypeInstance();
        $associatedProducts = $typeInstance->getAssociatedProducts($product);

        foreach ($associatedProducts as $singleProduct) {

            $optionCombinationTitle[] = $singleProduct->getName();

            $possibleVariationProductOptions[] = array(
                'product_id'   => $singleProduct->getId(),
                'product_type' => $product->getTypeId(),
                'attribute'    => self::GROUPED_PRODUCT_ATTRIBUTE_LABEL,
                'option'       => $singleProduct->getName(),
            );
        }

        $variationOptionsTitle[self::GROUPED_PRODUCT_ATTRIBUTE_LABEL] = $optionCombinationTitle;
        $variationOptionsList[] = $possibleVariationProductOptions;

        return array(
            'set'        => $variationOptionsTitle,
            'variations' => $variationOptionsList,
        );
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getBundleVariationsTypeStandard()
    {
        if (!$this->getMagentoProduct()->isBundleType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $productInstance = $this->getMagentoProduct()->getTypeInstance();
        $optionCollection = $productInstance->getOptionsCollection($product);

        $variationOptionsTitle = array();
        $variationOptionsList = array();

        foreach ($optionCollection as $singleOption) {

            if (!(bool)(int)$singleOption->getData('required')) {
                continue;
            }

            $optionTitle = $singleOption->getTitle();
            if ($optionTitle == '') {
                $optionTitle = $singleOption->getDefaultTitle();
            }

            if (isset($variationOptionsTitle[$optionTitle])) {
                continue;
            }

            $optionCombinationTitle = array();
            $possibleVariationProductOptions = array();

            $selectionsCollectionItems = $productInstance->getSelectionsCollection(
                array(0 => $singleOption->getId()), $product
            )->getItems();

            foreach ($selectionsCollectionItems as $item) {
                $optionCombinationTitle[] = $item->getName();
                $possibleVariationProductOptions[] = array(
                    'product_id'   => $item->getProductId(),
                    'product_type' => $product->getTypeId(),
                    'attribute'    => $optionTitle,
                    'option'       => $item->getName(),
                );
            }

            $variationOptionsTitle[$optionTitle] = $optionCombinationTitle;
            $variationOptionsList[] = $possibleVariationProductOptions;
        }

        return array(
            'set'        => $variationOptionsTitle,
            'variations' => $variationOptionsList,
        );
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getDownloadableVariationsTypeStandard()
    {
        if (!$this->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $attributeTitle = $product->getData('links_title');

        $store = $this->storeManager->getStore($product->getStoreId());

        if (empty($attributeTitle)) {
            $attributeTitle = $store->getConfig(
                \Magento\Downloadable\Model\Link::XML_PATH_LINKS_TITLE, $this->getMagentoProduct()->getStoreId()
            );
        }

        if (empty($attributeTitle)) {
            $attributeTitle = $this->getMagentoProduct()->getProduct()->getAttributeDefaultValue('links_title');
        }

        if (empty($attributeTitle)) {
            $attributeTitle = $store->getConfig(\Magento\Downloadable\Model\Link::XML_PATH_LINKS_TITLE);
        }

        if (empty($attributeTitle)) {
            $attributeTitle = self::DOWNLOADABLE_PRODUCT_DEFAULT_ATTRIBUTE_LABEL;
        }

        $optionCombinationTitle          = array();
        $possibleVariationProductOptions = array();

        /** @var \Magento\Downloadable\Model\Link[] $links */
        $links = $this->getMagentoProduct()->getTypeInstance()->getLinks($product);

        foreach ($links as $link) {
            $linkTitle = $link->getStoreTitle();
            if (empty($linkTitle)) {
                $linkTitle = $link->getDefaultTitle();
            }

            $optionCombinationTitle[] = $linkTitle;
            $possibleVariationProductOptions[] = array(
                'product_id'   => $product->getId(),
                'product_type' => $product->getTypeId(),
                'attribute'    => $attributeTitle,
                'option'       => $linkTitle,
            );
        }

        $variationOptionsTitle[$attributeTitle] = $optionCombinationTitle;
        $variationOptionsList[] = $possibleVariationProductOptions;

        return array(
            'set'        => $variationOptionsTitle,
            'variations' => $variationOptionsList,
        );
    }

    protected function prepareVariationsScopeTypeStandard(&$optionsScope)
    {
        $tempArray = array();

        foreach ($optionsScope as $key => $optionScope) {

            $temp = reset($optionScope);
            $attribute = $temp['attribute'];

            if (isset($tempArray[$attribute])) {
                unset($optionsScope[$key]);
                continue;
            }

            $tempArray[$attribute] = 1;
        }
    }

    protected function prepareVariationsTypeStandard(&$optionsScope, &$set, $optionScopeIndex = 0)
    {
        $resultVariations = array();

        if (!isset($optionsScope[$optionScopeIndex])) {
            return $resultVariations;
        }

        $subVariations = $this->prepareVariationsTypeStandard($optionsScope, $set, $optionScopeIndex+1);

        if (count($subVariations) <= 0) {

            foreach ($optionsScope[$optionScopeIndex] as $option) {
                $resultVariations[] = array($option);
            }

            return $resultVariations;
        }

        foreach ($optionsScope[$optionScopeIndex] as $option) {

            if (!isset($set[$option['attribute']]) ||
                !in_array($option['option'],$set[$option['attribute']],true)) {
                continue;
            }

            foreach ($subVariations as $subVariation) {
                $subVariation[] = $option;
                $resultVariations[] = $subVariation;
            }
        }

        return $resultVariations;
    }

    protected function sortAttributeOptions($attributeCode, $options)
    {
        $attribute = $this->productFactory->create()->getResource()->getAttribute($attributeCode);

        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $optionCollection */
        $optionCollection = $this->entityOptionCollectionFactory->create();
        $optionCollection->setAttributeFilter($attribute->getId());
        $optionCollection->setPositionOrder();
        $optionCollection->setStoreFilter($this->getMagentoProduct()->getStoreId());

        $sortedOptions = array();
        foreach ($optionCollection as $option) {
            if (!in_array($option->getValue(), $options, true) ||
                in_array($option->getValue(), $sortedOptions, true)) {
                continue;
            }

            $sortedOptions[] = $option->getValue();
        }

        return $sortedOptions;
    }

    protected function injectVirtualAttributesTypeStandard(&$variations, &$set)
    {
        $virtualAttributes = $this->getMagentoProduct()->getVariationVirtualAttributes();
        if (empty($virtualAttributes)) {
            return;
        }

        foreach ($variations as $variationKey => $variation) {
            foreach ($virtualAttributes as $virtualAttribute => $virtualValue) {
                $existOption = reset($variation);

                $virtualOption = array(
                    'product_id'   => null,
                    'product_type' => $existOption['product_type'],
                    'attribute'    => $virtualAttribute,
                    'option'       => $virtualValue,
                );

                $variations[$variationKey][] = $virtualOption;
            }
        }

        foreach ($virtualAttributes as $virtualAttribute => $virtualValue) {
            $set[$virtualAttribute] = array($virtualValue);
        }
    }

    protected function filterByAttributesTypeStandard(&$variations, &$set)
    {
        $filterAttributes = $this->getMagentoProduct()->getVariationFilterAttributes();
        if (empty($filterAttributes)) {
            return;
        }

        foreach ($variations as $variationKey => $variation) {

            foreach ($variation as $optionKey => $option) {

                if (!isset($filterAttributes[$option['attribute']])) {
                    continue;
                }

                $filterValue = $filterAttributes[$option['attribute']];
                if ($option['option'] == $filterValue) {
                    continue;
                }

                unset($variations[$variationKey]);
                break;
            }
        }

        $variations = array_values($variations);

        foreach ($set as $attribute => $values) {
            if (!isset($filterAttributes[$attribute])) {
                continue;
            }

            $filterValue = $filterAttributes[$attribute];
            if (!in_array($filterValue, $values)) {
                $set[$attribute] = array();
                continue;
            }

            $set[$attribute] = array($filterValue);
        }
    }

    // ---------------------------------------

    public function getVariationsTypeRaw()
    {
        if ($this->getMagentoProduct()->isSimpleType()) {
            return $this->getSimpleVariationsTypeRaw();
        }

        if ($this->getMagentoProduct()->isConfigurableType()) {
            return $this->getConfigurableVariationsTypeRaw();
        }

        if ($this->getMagentoProduct()->isGroupedType()) {
            return $this->getGroupedVariationsTypeRaw();
        }

        if ($this->getMagentoProduct()->isBundleType()) {
            return $this->getBundleVariationsTypeRaw();
        }

        if ($this->getMagentoProduct()->isDownloadableType()) {
            return $this->getDownloadableVariationTypeRaw();
        }

        return array();
    }

    protected function getSimpleVariationsTypeRaw()
    {
        if (!$this->getMagentoProduct()->isSimpleType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $customOptions = array();

        $productOptions = $product->getOptions();

        foreach ($productOptions as $option) {
            if (!(bool)(int)$option->getData('is_require')) {
                continue;
            }

            if (!in_array($option->getType(), $this->getCustomOptionsAllowedTypes())) {
                continue;
            }

            $customOption = array(
                'option_id' => $option->getData('option_id'),
                'values'    => array(),
                'labels'    => array_filter(array(
                    trim($option->getData('store_title')),
                    trim($option->getData('title')),
                    trim($option->getData('default_title')),
                ))
            );

            $values = $option->getValues();

            foreach ($values as $value) {
                $customOption['values'][] = array(
                    'product_ids' => array($this->getMagentoProduct()->getProductId()),
                    'value_id' => $value->getData('option_type_id'),
                    'labels'   => array_filter(array(
                        trim($value->getData('store_title')),
                        trim($value->getData('title')),
                        trim($value->getData('default_title'))
                    ))
                );
            }

            if (count($customOption['values']) == 0) {
                continue;
            }

            $customOptions[] = $customOption;
        }

        return $customOptions;
    }

    protected function getConfigurableVariationsTypeRaw()
    {
        if (!$this->getMagentoProduct()->isConfigurableType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();

        $configurableOptions = array();

        foreach ($productTypeInstance->getConfigurableAttributes($product) as $attribute) {

            $productAttribute = $attribute->getProductAttribute();
            if (!$productAttribute) {
                $message = "Configurable Magento Product (ID {$this->getMagentoProduct()->getProductId()})";
                $message .= ' has no selected configurable attribute.';
                throw new \Ess\M2ePro\Model\Exception($message);
            }
            $productAttribute->setStoreId($this->getMagentoProduct()->getStoreId());

            $configurableOption = array(
                'option_id' => $attribute->getAttributeId(),
                'labels' => array_filter(array(
                    trim($attribute->getData('label')),
                    trim($productAttribute->getFrontendLabel()),
                    trim($productAttribute->getStoreLabel($this->getMagentoProduct()->getStoreId())),
                )),
                'values' => $this->getConfigurableAttributeValues($attribute),
            );

            if (count($configurableOption['values']) == 0) {
                continue;
            }

            $configurableOptions[] = $configurableOption;
        }

        return $configurableOptions;
    }

    protected function getGroupedVariationsTypeRaw()
    {
        if (!$this->getMagentoProduct()->isGroupedType()) {
            return array();
        }

        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();
        return $productTypeInstance->getAssociatedProducts($this->getMagentoProduct()->getProduct());
    }

    protected function getBundleVariationsTypeRaw()
    {
        if (!$this->getMagentoProduct()->isBundleType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $bundleOptions = array();

        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();

        $optionsCollection = $productTypeInstance->getOptionsCollection($product);
        $selectionsCollection = $productTypeInstance
            ->getSelectionsCollection($optionsCollection->getAllIds(), $product);

        foreach ($optionsCollection as $option) {
            if (!$option->getData('required')) {
                continue;
            }

            $bundleOption = array(
                'option_id' => $option->getData('option_id'),
                'values'    => array(),
                'labels'    => array_filter(array(
                    trim($option->getData('default_title')),
                    trim($option->getData('title')),
                )),
            );

            foreach ($selectionsCollection as $selection) {
                if ($option->getData('option_id') != $selection->getData('option_id')) {
                    continue;
                }

                $bundleOption['values'][] = array(
                    'product_ids' => array($selection->getData('product_id')),
                    'value_id'    => $selection->getData('selection_id'),
                    'labels'      => array(trim($selection->getData('name'))),
                );
            }

            if (count($bundleOption['values']) == 0) {
                continue;
            }

            $bundleOptions[] = $bundleOption;
        }

        return $bundleOptions;
    }

    protected function getDownloadableVariationTypeRaw()
    {
        if (!$this->getMagentoProduct()->isDownloadableType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();
        $store = $this->storeManager->getStore($product->getStoreId());

        $labels = array();

        $labels[] = $product->getData('links_title');
        $labels[] = $store->getConfig(
            \Magento\Downloadable\Model\Link::XML_PATH_LINKS_TITLE, $this->getMagentoProduct()->getStoreId()
        );
        $labels[] = $this->getMagentoProduct()->getProduct()->getAttributeDefaultValue('links_title');
        $labels[] = $store->getConfig(\Magento\Downloadable\Model\Link::XML_PATH_LINKS_TITLE);
        $labels[] = self::DOWNLOADABLE_PRODUCT_DEFAULT_ATTRIBUTE_LABEL;

        $resultOptions = array(
            'option_id' => $product->getId(),
            'values'    => array(),
            'labels'    => array_values(array_filter($labels))
        );

        /** @var \Magento\Downloadable\Model\Link[] $links */
        $links = $this->getMagentoProduct()->getTypeInstance()->getLinks($product);

        foreach ($links as $link) {
            $resultOptions['values'][] = array(
                'product_ids' => array($product->getId()),
                'value_id'    => $link->getId(),
                'labels'      => array_filter(array(
                    $link->getStoreTitle(),
                    $link->getDefaultTitle(),
                )),
            );
        }

        return array($resultOptions);
    }

    protected function getConfigurableAttributeValues($attribute)
    {
        $product = $this->getMagentoProduct()->getProduct();
        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();

        $productAttribute = $attribute->getProductAttribute();
        if (!$productAttribute) {
            $message = "Configurable Magento Product (ID {$this->getMagentoProduct()->getProductId()})";
            $message .= ' has no selected configurable attribute.';
            throw new \Ess\M2ePro\Model\Exception($message);
        }

        $options = $this->getConfigurableAttributeOptions($productAttribute);
        $values = array();

        foreach ($options as $option) {
            foreach ($productTypeInstance->getUsedProducts($product, null) as $associatedProduct) {
                if ($option['value_id'] != $associatedProduct->getData($productAttribute->getAttributeCode())) {
                    continue;
                }

                $attributeOptionKey = $attribute->getAttributeId() . ':' . $option['value_id'];
                if (!isset($values[$attributeOptionKey])) {
                    $values[$attributeOptionKey] = $option;
                }

                $values[$attributeOptionKey]['product_ids'][] = $associatedProduct->getId();
            }
        }

        return array_values($values);
    }

    protected function getConfigurableAttributeOptions($productAttribute)
    {
        $options = $productAttribute->getSource()->getAllOptions(false, false);
        $defaultOptions = $productAttribute->getSource()->getAllOptions(false, true);

        $mergedOptions = array();
        foreach ($options as $option) {
            $mergedOption = array(
                'product_ids' => array(),
                'value_id' => $option['value'],
                'labels' => array(
                    trim($option['label'])
                )
            );

            foreach ($defaultOptions as $defaultOption) {
                if ($defaultOption['value'] == $option['value']) {
                    $mergedOption['labels'][] = trim($defaultOption['label']);
                    break;
                }
            }

            $mergedOptions[] = $mergedOption;
        }

        return $mergedOptions;
    }

    // ---------------------------------------

    public function getTitlesVariationSet()
    {
        if ($this->getMagentoProduct()->isSimpleType()) {
            return $this->getSimpleTitlesVariationSet();
        }

        if ($this->getMagentoProduct()->isConfigurableType()) {
            return $this->getConfigurableTitlesVariationSet();
        }

        if ($this->getMagentoProduct()->isGroupedType()) {
            return $this->getGroupedTitlesVariationSet();
        }

        if ($this->getMagentoProduct()->isBundleType()) {
            return $this->getBundleTitlesVariationSet();
        }

        if ($this->getMagentoProduct()->isDownloadableType()) {
            return $this->getDownloadableTitlesVariationSet();
        }

        return array();
    }

    protected function getSimpleTitlesVariationSet()
    {
        if (!$this->getMagentoProduct()->isSimpleType()) {
            return array();
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Option\Collection $optionsCollection */
        $optionsCollection = $this->productOptionCollectionFactory->create();
        $optionsCollection->addProductToFilter($this->getMagentoProduct()->getProductId());

        $storesTitles = array();

        foreach ($this->storeManager->getStores(true) as $store) {
            /** @var \Magento\Store\Api\Data\StoreInterface $store */

            $optionsCollection->reset();

            $storeId = (int)$store->getId();

            $optionsCollection->getOptions($storeId);
            $optionsCollection->addValuesToResult($storeId);

            foreach ($optionsCollection as $option) {
                /** @var \Magento\Catalog\Model\Product\Option $option */

                if (!$option->getData('is_require')
                    || !in_array($option->getType(), $this->getCustomOptionsAllowedTypes())
                    || $option->getProductId() != $this->getMagentoProduct()->getProductId()
                ) {
                    continue;
                }

                $optionId = (int)$option->getId();

                if (!isset($storesTitles[$optionId])) {
                    $storesTitles[$optionId] = array(
                        'titles' => array(),
                        'values' => array(),
                    );
                }

                if (!is_null($option->getData('store_title'))) {
                    $storesTitles[$optionId]['titles'][$storeId] = $option->getData('store_title');
                }

                foreach ($option->getValues() as $value) {
                    /** @var \Magento\Catalog\Model\Product\Option\Value $value */

                    if (is_null($value->getData('store_title'))) {
                        continue;
                    }

                    $storesTitles[$optionId]['values'][(int)$value->getId()][$storeId]
                        = $value->getData('store_title');
                }
            }
        }

        $resultTitles = array();
        foreach ($storesTitles as $storeOption) {
            $titles = array_values(array_unique($storeOption['titles']));

            $values = array();
            foreach ($storeOption['values'] as $valueStoreTitles) {
                $keyValue = $valueStoreTitles[\Magento\Store\Model\Store::DEFAULT_STORE_ID];
                if (isset($valueStoreTitles[$this->getMagentoProduct()->getStoreId()])) {
                    $keyValue = $valueStoreTitles[$this->getMagentoProduct()->getStoreId()];
                }

                $valueStoreTitles = array_unique($valueStoreTitles);
                $valueStoreTitles = array_values($valueStoreTitles);

                $values[$keyValue] = $valueStoreTitles;
            }

            $keyValue = $storeOption['titles'][\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            if (isset($storeOption['titles'][$this->getMagentoProduct()->getStoreId()])) {
                $keyValue = $storeOption['titles'][$this->getMagentoProduct()->getStoreId()];
            }

            $resultTitles[$keyValue] = array(
                'titles' => $titles,
                'values' => $values,
            );
        }

        return $resultTitles;
    }

    protected function getConfigurableTitlesVariationSet()
    {
        if (!$this->getMagentoProduct()->isConfigurableType()) {
            return array();
        }

        $product = $this->getMagentoProduct()->getProduct();

        $configurableAttributes = $this->getMagentoProduct()->getTypeInstance()->getConfigurableAttributes($product);

        $resultTitles = array();
        foreach ($configurableAttributes as $configurableAttribute) {

            $productAttribute = $configurableAttribute->getProductAttribute();
            if (!$productAttribute) {
                $message = "Configurable Magento Product (ID {$this->getMagentoProduct()->getProductId()})";
                $message .= ' has no selected configurable attribute.';
                throw new \Ess\M2ePro\Model\Exception($message);
            }
            $attributeStoreTitles = $productAttribute->getStoreLabels();

            $attributeKeyTitle = $productAttribute->getFrontendLabel();
            if (isset($attributeStoreTitles[$this->getMagentoProduct()->getStoreId()])) {
                $attributeKeyTitle = $attributeStoreTitles[$this->getMagentoProduct()->getStoreId()];
            }

            if (!(int)$configurableAttribute->getData('use_default') && $configurableAttribute->getData('label')) {
                $attributeKeyTitle = $configurableAttribute->getData('label');
                $attributeStoreTitles[] = $configurableAttribute->getData('label');
            }

            if (isset($resultTitles[$attributeKeyTitle])) {
                continue;
            }

            $attributeStoreTitles[] = $productAttribute->getFrontendLabel();

            $resultTitles[$attributeKeyTitle]['titles'] = array_values(array_unique($attributeStoreTitles));

            $attributeValues = array();

            $stores = $this->storeManager->getStores(true);
            ksort($stores);

            foreach ($stores as $store) {
                /** @var \Magento\Store\Api\Data\StoreInterface $store */

                $storeId = (int)$store->getId();

                $valuesCollection = $this->entityOptionCollectionFactory->create()
                    ->setAttributeFilter($productAttribute->getId())
                    ->setStoreFilter($storeId, false);

                foreach ($valuesCollection as $attributeValue) {
                    $valueId = (int)$attributeValue->getId();

                    if (!isset($attributeValues[$valueId])) {
                        $attributeValues[$valueId] = array();
                    }

                    if (!in_array($attributeValue->getValue(), $attributeValues[$valueId], true)) {
                        $attributeValues[$valueId][$storeId] = $attributeValue->getValue();
                    }
                }
            }

            $resultTitles[$attributeKeyTitle]['values'] = array();

            foreach ($attributeValues as $attributeValue) {
                $keyValue = $attributeValue[\Magento\Store\Model\Store::DEFAULT_STORE_ID];
                if (isset($attributeValue[$this->getMagentoProduct()->getStoreId()])) {
                    $keyValue = $attributeValue[$this->getMagentoProduct()->getStoreId()];
                }

                $resultTitles[$attributeKeyTitle]['values'][$keyValue] = array_unique(array_values($attributeValue));
            }
        }

        return $resultTitles;
    }

    protected function getGroupedTitlesVariationSet()
    {
        $storesTitles = array();

        foreach ($this->storeManager->getStores(true) as $store) {
            /** @var \Magento\Store\Api\Data\StoreInterface $store */

            $storeId = (int)$store->getId();

            $associatedProductsCollection = $this->getMagentoProduct()->getProduct()->getTypeInstance()
                ->getAssociatedProductCollection($this->getMagentoProduct()->getProduct())
                ->addAttributeToSelect('name')
                ->addStoreFilter($storeId)
                ->setStoreId($storeId);

            foreach ($associatedProductsCollection as $associatedProduct) {
                /** @var \Magento\Catalog\Model\Product $associatedProduct */

                $productId = (int)$associatedProduct->getId();

                if (!isset($storesTitles[$productId])) {
                    $storesTitles[$productId] = array();
                }

                $storesTitles[$productId][$storeId] = $associatedProduct->getName();
            }
        }

        $resultTitles = array(
            self::GROUPED_PRODUCT_ATTRIBUTE_LABEL => array(
                'titles' => array(),
                'values' => array(),
            ),
        );
        foreach ($storesTitles as $productTitles) {
            $keyValue = $productTitles[\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            if (isset($productTitles[$this->getMagentoProduct()->getStoreId()])) {
                $keyValue = $productTitles[$this->getMagentoProduct()->getStoreId()];
            }

            $resultTitles[self::GROUPED_PRODUCT_ATTRIBUTE_LABEL]['values'][$keyValue]
                = array_values(array_unique($productTitles));
        }

        return $resultTitles;
    }

    protected function getBundleTitlesVariationSet()
    {
        $storesTitles = array();

        foreach ($this->storeManager->getStores(true) as $store) {
            /** @var \Magento\Store\Api\Data\StoreInterface $store */

            $storeId = (int)$store->getId();

            $optionsCollection = $this->bundleOptionFactory->create()->getResourceCollection()
                ->setProductIdFilter($this->getMagentoProduct()->getProductId())
                ->joinValues($storeId);

            foreach ($optionsCollection as $option) {
                /** @var \Magento\Bundle\Model\Option $option */

                if (!$option->getData('required')) {
                    continue;
                }

                $optionId = (int)$option->getOptionId();

                if (!isset($storesTitles[$optionId])) {
                    $storesTitles[$optionId] = array(
                        'titles' => array(),
                        'values' => array(),
                    );
                }

                $storesTitles[$optionId]['titles'][$storeId] = $option->getTitle();

                $selectionsCollection = $this->bundleSelectionCollectionFactory->create()
                    ->addAttributeToSelect('name')
                    ->setFlag('require_stock_items', true)
                    ->setFlag('product_children', true)
                    ->addStoreFilter($storeId)
                    ->setStoreId($storeId)
                    ->addFilterByRequiredOptions()
                    ->setOptionIdsFilter(array($optionId));

                foreach ($selectionsCollection as $selectionProduct) {
                    /** @var \Magento\Catalog\Model\Product $selectionProduct */

                    $productId = (int)$selectionProduct->getId();

                    if (!isset($storesTitles[$optionId]['values'][$productId])) {
                        $storesTitles[$optionId]['values'][$productId] = array();
                    }

                    $selectionName = $selectionProduct->getName();
                    $storesTitles[$optionId]['values'][$productId][$storeId] = $selectionName;
                }
            }
        }

        $resultTitles = array();
        foreach ($storesTitles as $storeOption) {
            $titles = array_values(array_unique($storeOption['titles']));

            $values = array();
            foreach ($storeOption['values'] as $valueStoreTitles) {
                $keyValue = $valueStoreTitles[\Magento\Store\Model\Store::DEFAULT_STORE_ID];
                if (isset($valueStoreTitles[$this->getMagentoProduct()->getStoreId()])) {
                    $keyValue = $valueStoreTitles[$this->getMagentoProduct()->getStoreId()];
                }

                $valueStoreTitles = array_unique($valueStoreTitles);
                $valueStoreTitles = array_values($valueStoreTitles);

                $values[$keyValue] = $valueStoreTitles;
            }

            $keyValue = $storeOption['titles'][\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            if (isset($storeOption['titles'][$this->getMagentoProduct()->getStoreId()])) {
                $keyValue = $storeOption['titles'][$this->getMagentoProduct()->getStoreId()];
            }

            $resultTitles[$keyValue] = array(
                'titles' => $titles,
                'values' => $values,
            );
        }

        return $resultTitles;
    }

    protected function getDownloadableTitlesVariationSet()
    {
        if (!$this->getMagentoProduct()->isDownloadableType()) {
            return array();
        }

        $storesTitles  = array();
        $storesOptions = array();

        foreach ($this->storeManager->getStores(true) as $store) {

            $storeId = (int)$store->getId();

            $productValue = $this->getMagentoProduct()->getProduct()->getResource()->getAttributeRawValue(
                $this->getMagentoProduct()->getProductId(), 'links_title', $storeId
            );
            $configValue = $store->getConfig(
                \Magento\Downloadable\Model\Link::XML_PATH_LINKS_TITLE, $storeId
            );

            $storesTitles[$storeId] = array(
                $productValue,
                $configValue
            );

            $linkCollection = $this->downloadableLinkFactory->create()->getCollection()
                ->addProductToFilter($this->getMagentoProduct()->getProductId())
                ->addTitleToResult($storeId);

            /** @var \Magento\Downloadable\Model\Link[] $links */
            $links = $linkCollection->getItems();

            foreach ($links as $link) {
                $linkId = (int)$link->getId();

                if (!empty($link->getStoreTitle())) {
                    $storesOptions[$linkId][$storeId] = $link->getStoreTitle();
                }
            }
        }

        $titleKeyValue = reset($storesTitles[\Magento\Store\Model\Store::DEFAULT_STORE_ID]);
        if (!empty($storesTitles[$this->getMagentoProduct()->getStoreId()])) {
            $titleKeyValue = reset($storesTitles[$this->getMagentoProduct()->getStoreId()]);
        }

        $resultTitles = array(
            $titleKeyValue => array(
                'titles' => array(self::DOWNLOADABLE_PRODUCT_DEFAULT_ATTRIBUTE_LABEL),
                'values' => array(),
            )
        );

        foreach ($storesTitles as $storeTitles) {
            $resultTitles[$titleKeyValue]['titles'] = array_values(array_unique(array_merge(
                $resultTitles[$titleKeyValue]['titles'], $storeTitles
            )));
        }

        foreach ($storesOptions as $optionValues) {
            $optionKeyValue = $optionValues[\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            if (!empty($optionValues[$this->getMagentoProduct()->getStoreId()])) {
                $optionKeyValue = $optionValues[$this->getMagentoProduct()->getStoreId()];
            }

            $resultTitles[$titleKeyValue]['values'][$optionKeyValue] = array_values(array_unique($optionValues));
        }

        return $resultTitles;
    }

    //########################################

    protected function getCustomOptionsAllowedTypes()
    {
        return array('drop_down', 'radio', 'multiple', 'checkbox');
    }

    //########################################
}