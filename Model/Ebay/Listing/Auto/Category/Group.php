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
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    protected $categorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $storeCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $storeCategorySecondaryTemplateModel = null;

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

        $this->categoryTemplateModel               = null;
        $this->categorySecondaryTemplateModel      = null;
        $this->storeCategoryTemplateModel          = null;
        $this->storeCategorySecondaryTemplateModel = null;
        $this->magentoProductModel                 = null;

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
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategorySecondaryTemplate()
    {
        if ($this->categorySecondaryTemplateModel === null) {
            try {
                $this->categorySecondaryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_Category',
                    (int)$this->getAddingTemplateCategorySecondaryId(),
                    null,
                    ['template']
                );
            } catch (\Exception $exception) {
                return $this->categorySecondaryTemplateModel;
            }

            if ($this->getMagentoProduct() !== null) {
                $this->categorySecondaryTemplateModel->setMagentoProduct($this->getMagentoProduct());
            }
        }

        return $this->categorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setCategorySecondaryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->categorySecondaryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getStoreCategoryTemplate()
    {
        if ($this->storeCategoryTemplateModel === null) {
            try {
                $this->storeCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_StoreCategory',
                    (int)$this->getAddingTemplateStoreCategoryId(),
                    null,
                    ['template']
                );
            } catch (\Exception $exception) {
                return $this->storeCategoryTemplateModel;
            }

            if ($this->getMagentoProduct() !== null) {
                $this->storeCategoryTemplateModel->setMagentoProduct($this->getMagentoProduct());
            }
        }

        return $this->storeCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setStoreCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->storeCategoryTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getStoreCategorySecondaryTemplate()
    {
        if ($this->storeCategorySecondaryTemplateModel === null) {
            try {
                $this->storeCategorySecondaryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                    'Ebay_Template_StoreCategory',
                    (int)$this->getAddingTemplateStoreCategorySecondaryId(),
                    null,
                    ['template']
                );
            } catch (\Exception $exception) {
                return $this->storeCategorySecondaryTemplateModel;
            }

            if ($this->getMagentoProduct() !== null) {
                $this->storeCategorySecondaryTemplateModel->setMagentoProduct($this->getMagentoProduct());
            }
        }

        return $this->storeCategorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setStoreCategorySecondaryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->storeCategorySecondaryTemplateModel = $instance;
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

    public function getAddingTemplateCategorySecondaryId()
    {
        return $this->getData('adding_template_category_secondary_id');
    }

    public function getAddingTemplateStoreCategoryId()
    {
        return $this->getData('adding_template_store_category_id');
    }

    public function getAddingTemplateStoreCategorySecondaryId()
    {
        return $this->getData('adding_template_store_category_secondary_id');
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
