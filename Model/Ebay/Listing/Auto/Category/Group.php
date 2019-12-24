<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Auto\Category\Group getParentObject()
 */
namespace Ess\M2ePro\Model\Ebay\Listing\Auto\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Auto\Category\Group
 */
class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $otherCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProductModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Auto\Category\Group');
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->categoryTemplateModel = null;
        $this->otherCategoryTemplateModel = null;
        $this->magentoProductModel = null;

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategoryTemplate()
    {
        if ($this->categoryTemplateModel === null) {
            try {
                $this->categoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_Category',
                    (int)$this->getAddingTemplateCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->categoryTemplateModel;
            }

            if ($this->getMagentoProduct() !== null) {
                $this->categoryTemplateModel->setMagentoProduct($this->getMagentoProduct());
            }
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
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    public function getOtherCategoryTemplate()
    {
        if ($this->otherCategoryTemplateModel === null) {
            try {
                $this->otherCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_OtherCategory',
                    (int)$this->getAddingTemplateOtherCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->otherCategoryTemplateModel;
            }

            if ($this->getMagentoProduct() !== null) {
                $this->otherCategoryTemplateModel->setMagentoProduct($this->getMagentoProduct());
            }
        }

        return $this->otherCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance
     */
    public function setOtherCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance)
    {
        $this->otherCategoryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProductModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $instance
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $instance)
    {
        $this->magentoProductModel = $instance;
    }

    //########################################

    public function getAddingTemplateCategoryId()
    {
        return $this->getData('adding_template_category_id');
    }

    public function getAddingTemplateOtherCategoryId()
    {
        return $this->getData('adding_template_other_category_id');
    }

    //########################################

    /**
     * @return bool
     */
    public function isAddingModeAddAndAssignCategory()
    {
        return $this->getParentObject()->getAddingMode() ==
              \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY;
    }

    //########################################
}
