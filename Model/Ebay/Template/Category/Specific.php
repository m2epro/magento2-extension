<?php

namespace Ess\M2ePro\Model\Ebay\Template\Category;

class Specific extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    public const MODE_ITEM_SPECIFICS = 1;
    public const MODE_CUSTOM_ITEM_SPECIFICS = 3;

    public const VALUE_MODE_NONE = 0;
    public const VALUE_MODE_EBAY_RECOMMENDED = 1;
    public const VALUE_MODE_CUSTOM_VALUE = 2;
    public const VALUE_MODE_CUSTOM_ATTRIBUTE = 3;
    public const VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE = 4;

    public const RENDER_TYPE_TEXT = 'text';
    public const RENDER_TYPE_SELECT_ONE = 'select_one';
    public const RENDER_TYPE_SELECT_MULTIPLE = 'select_multiple';
    public const RENDER_TYPE_SELECT_ONE_OR_TEXT = 'select_one_or_text';
    public const RENDER_TYPE_SELECT_MULTIPLE_OR_TEXT = 'select_multiple_or_text';

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category\Specific\Source[]
     */
    private $categorySpecificSourceModels = [];

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Specific::class);
    }

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->categoryTemplateModel = null;
        $temp && $this->categorySpecificSourceModels = [];

        return $temp;
    }

    // ----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategoryTemplate()
    {
        if ($this->categoryTemplateModel === null) {
            $this->categoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Category',
                $this->getTemplateCategoryId()
            );
        }

        return $this->categoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->categoryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Specific\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->categorySpecificSourceModels[$productId])) {
            return $this->categorySpecificSourceModels[$productId];
        }

        $this->categorySpecificSourceModels[$productId] = $this->modelFactory->getObject(
            'Ebay_Template_Category_Specific_Source'
        );
        $this->categorySpecificSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->categorySpecificSourceModels[$productId]->setCategorySpecificTemplate($this);

        return $this->categorySpecificSourceModels[$productId];
    }

    // ----------------------------------------

    public function setTemplateCategoryId(int $id): self
    {
        $this->setData('template_category_id', $id);

        return $this;
    }

    public function getTemplateCategoryId(): int
    {
        return (int)$this->getData('template_category_id');
    }

    public function setMode(int $mode): self
    {
        $this->setData('mode', $mode);

        return $this;
    }

    public function getMode(): int
    {
        return (int)$this->getData('mode');
    }

    public function setAttributeTitle(string $title): self
    {
        $this->setData('attribute_title', $title);

        return $this;
    }

    public function getAttributeTitle(): string
    {
        return (string)$this->getData('attribute_title');
    }

    public function setValueCustomValue(array $values): self
    {
        $this->setData('value_custom_value', \Ess\M2ePro\Helper\Json::encode($values));

        return $this;
    }

    public function getValueCustomValue()
    {
        return $this->getData('value_custom_value');
    }

    public function setValueCustomAttribute(string $value): self
    {
        $this->setData('value_custom_attribute', $value);

        return $this;
    }

    public function getValueCustomAttribute()
    {
        return $this->getData('value_custom_attribute');
    }

    public function setValueCustomAttributeMode(): self
    {
        $this->setValueMode(self::VALUE_MODE_CUSTOM_ATTRIBUTE);
        $this->setData('value_custom_value', null);

        return $this;
    }

    public function setValueCustomValueMode(): self
    {
        $this->setValueMode(self::VALUE_MODE_CUSTOM_VALUE);
        $this->setData('value_custom_attribute', null);

        return $this;
    }

    public function setValueMode(int $mode): self
    {
        $this->setData('value_mode', $mode);

        return $this;
    }

    public function getValueMode(): int
    {
        return (int)$this->getData('value_mode');
    }

    // ---------------------------------------

    public function isItemSpecificsMode(): bool
    {
        return $this->getMode() == self::MODE_ITEM_SPECIFICS;
    }

    public function isCustomItemSpecificsMode(): bool
    {
        return $this->getMode() == self::MODE_CUSTOM_ITEM_SPECIFICS;
    }

    // ---------------------------------------

    public function isNoneValueMode(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_NONE;
    }

    public function isEbayRecommendedValueMode(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_EBAY_RECOMMENDED;
    }

    public function isCustomValueValueMode(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_CUSTOM_VALUE;
    }

    public function isCustomAttributeValueMode(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_CUSTOM_ATTRIBUTE;
    }

    public function isCustomLabelAttributeValueMode(): bool
    {
        return $this->getValueMode() === self::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE;
    }

    // ----------------------------------------

    public function getValueAttributes(): array
    {
        $attributes = [];

        if ($this->isCustomAttributeValueMode() || $this->isCustomLabelAttributeValueMode()) {
            $attributes[] = $this->getValueCustomAttribute();
        }

        return $attributes;
    }
}
