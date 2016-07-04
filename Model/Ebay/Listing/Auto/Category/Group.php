<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Auto\Category\Group getParentObject()
 */
namespace Ess\M2ePro\Model\Ebay\Listing\Auto\Category;

class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $otherCategoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProductModel = NULL;

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

        $this->categoryTemplateModel = NULL;
        $this->otherCategoryTemplateModel = NULL;
        $this->magentoProductModel = NULL;

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategoryTemplate()
    {
        if (is_null($this->categoryTemplateModel)) {

            try {
                $this->categoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay\Template\Category',
                    (int)$this->getAddingTemplateCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->categoryTemplateModel;
            }

            if (!is_null($this->getMagentoProduct())) {
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
        if (is_null($this->otherCategoryTemplateModel)) {

            try {
                $this->otherCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay\Template\OtherCategory', (int)$this->getAddingTemplateOtherCategoryId()
                );
            } catch (\Exception $exception) {
                return $this->otherCategoryTemplateModel;
            }

            if (!is_null($this->getMagentoProduct())) {
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