<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Category\Specific
 */
class Specific extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const DICTIONARY_TYPE_TEXT = 1;
    const DICTIONARY_TYPE_SELECT = 2;
    const DICTIONARY_TYPE_CONTAINER = 3;

    const DICTIONARY_MODE_RECOMMENDED_VALUE = 'recommended_value';
    const DICTIONARY_MODE_CUSTOM_VALUE = 'custom_value';
    const DICTIONARY_MODE_CUSTOM_ATTRIBUTE = 'custom_attribute';
    const DICTIONARY_MODE_NONE = 'none';

    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_DATETIME = 'date_time';

    const UNIT_SPECIFIC_CODE = 'unit';
    const MEASURE_SPECIFIC_CODE = 'measure';

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\Category
     */
    private $categoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\Category\Specific\Source[]
     */
    private $categorySpecificSourceModels = [];

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\Specific');
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
     * @return \Ess\M2ePro\Model\Walmart\Template\Category
     * @throws Exception
     */
    public function getCategoryTemplate()
    {
        if ($this->categoryTemplateModel === null) {
            $this->categoryTemplateModel = $this->activeRecordFactory->getObjectLoaded(
                'Walmart_Template_Category',
                $this->getTemplateCategoryId()
            );
        }

        return $this->categoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Walmart\Template\Category $instance
     */
    public function setCategoryTemplate(\Ess\M2ePro\Model\Walmart\Template\Category $instance)
    {
        $this->categoryTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Category
     * @throws Exception
     */
    public function getWalmartCategoryTemplate()
    {
        return $this->getCategoryTemplate();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Walmart\Template\Category\Specific\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->categorySpecificSourceModels[$productId])) {
            return $this->categorySpecificSourceModels[$productId];
        }

        $this->categorySpecificSourceModels[$productId] = $this->modelFactory->getObject(
            'Walmart_Template_Category_Specific_Source'
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

    /**
     * @return string
     */
    public function getXpath()
    {
        return trim($this->getData('xpath'), '/');
    }

    public function getMode()
    {
        return $this->getData('mode');
    }

    public function getIsRequired()
    {
        return $this->getData('is_required');
    }

    public function getCustomValue()
    {
        return $this->getData('custom_value');
    }

    public function getCustomAttribute()
    {
        return $this->getData('custom_attribute');
    }

    public function getType()
    {
        return $this->getData('type');
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $value = $this->getData('attributes');
        return is_string($value) ? (array)$this->getHelper('Data')->jsonDecode($value) : [];
    }

    // ---------------------------------------

    public function getCode()
    {
        $pathParts = explode('/', $this->getXpath());
        return preg_replace('/-[0-9]+$/', '', array_pop($pathParts));
    }

    //########################################

    public function isRequired()
    {
        return (bool)$this->getIsRequired();
    }

    //----------------------------------------

    public function isModeNone()
    {
        return $this->getMode() == self::DICTIONARY_MODE_NONE;
    }

    public function isModeCustomValue()
    {
        return $this->getMode() == self::DICTIONARY_MODE_CUSTOM_VALUE;
    }

    public function isModeCustomAttribute()
    {
        return $this->getMode() == self::DICTIONARY_MODE_CUSTOM_ATTRIBUTE;
    }

    public function isModeRecommended()
    {
        return $this->getMode() == self::DICTIONARY_MODE_RECOMMENDED_VALUE;
    }

    //----------------------------------------

    public function isTypeInt()
    {
        return $this->getType() == self::TYPE_INT;
    }

    public function isTypeFloat()
    {
        return $this->getType() == self::TYPE_FLOAT;
    }

    public function isTypeDateTime()
    {
        return $this->getType() == self::TYPE_DATETIME;
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
        $attribute = $this->getCustomAttribute();

        if (empty($attribute)) {
            return [];
        }

        return [$attribute];
    }

    //########################################
}
