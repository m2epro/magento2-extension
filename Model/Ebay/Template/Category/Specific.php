<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\Specific
 */
class Specific extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const MODE_ITEM_SPECIFICS = 1;
    const MODE_CUSTOM_ITEM_SPECIFICS = 3;

    const VALUE_MODE_NONE = 0;
    const VALUE_MODE_EBAY_RECOMMENDED = 1;
    const VALUE_MODE_CUSTOM_VALUE = 2;
    const VALUE_MODE_CUSTOM_ATTRIBUTE = 3;
    const VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE = 4;

    const RENDER_TYPE_TEXT = 'text';
    const RENDER_TYPE_SELECT_ONE = 'select_one';
    const RENDER_TYPE_SELECT_MULTIPLE = 'select_multiple';
    const RENDER_TYPE_SELECT_ONE_OR_TEXT = 'select_one_or_text';
    const RENDER_TYPE_SELECT_MULTIPLE_OR_TEXT = 'select_multiple_or_text';

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category\Specific\Source[]
     */
    private $categorySpecificSourceModels = [];

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category\Specific');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->categoryTemplateModel = null;
        $temp && $this->categorySpecificSourceModels = [];
        return $temp;
    }

    //########################################

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

    //########################################

    /**
     * @return int
     */
    public function getTemplateCategoryId()
    {
        return (int)$this->getData('template_category_id');
    }

    //########################################

    /**
     * @return int
     */
    public function getMode()
    {
        return (int)$this->getData('mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isItemSpecificsMode()
    {
        return $this->getMode() == self::MODE_ITEM_SPECIFICS;
    }

    /**
     * @return bool
     */
    public function isCustomItemSpecificsMode()
    {
        return $this->getMode() == self::MODE_CUSTOM_ITEM_SPECIFICS;
    }

    //########################################

    /**
     * @return int
     */
    public function getValueMode()
    {
        return (int)$this->getData('value_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isNoneValueMode()
    {
        return $this->getValueMode() == self::VALUE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isEbayRecommendedValueMode()
    {
        return $this->getValueMode() == self::VALUE_MODE_EBAY_RECOMMENDED;
    }

    /**
     * @return bool
     */
    public function isCustomValueValueMode()
    {
        return $this->getValueMode() == self::VALUE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isCustomAttributeValueMode()
    {
        return $this->getValueMode() == self::VALUE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isCustomLabelAttributeValueMode()
    {
        return $this->getValueMode() == self::VALUE_MODE_CUSTOM_LABEL_ATTRIBUTE;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        $attributes = [];

        if ($this->isCustomAttributeValueMode() || $this->isCustomLabelAttributeValueMode()) {
            $attributes[] = $this->getData('value_custom_attribute');
        }

        return $attributes;
    }

    //########################################
}
