<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\OtherCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $otherCategoryTemplateModel \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $otherCategoryTemplateModel = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance
     * @return $this
     */
    public function setOtherCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance)
    {
        $this->otherCategoryTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    public function getOtherCategoryTemplate()
    {
        return $this->otherCategoryTemplateModel;
    }

    //########################################

    /**
     * @return int|string
     */
    public function getSecondaryCategory()
    {
        $src = $this->getOtherCategoryTemplate()->getCategorySecondarySource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return 0;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    // ---------------------------------------

    /**
     * @return int|string
     */
    public function getStoreCategoryMain()
    {
        $src = $this->getOtherCategoryTemplate()->getStoreCategoryMainSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return 0;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    /**
     * @return int|string
     */
    public function getStoreCategorySecondary()
    {
        $src = $this->getOtherCategoryTemplate()->getStoreCategorySecondarySource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
            return 0;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    //########################################
}
