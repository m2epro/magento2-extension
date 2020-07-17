<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\StoreCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    private $storeCategoryTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     * @return $this
     */
    public function setStoreCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->storeCategoryTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getStoreCategoryTemplate()
    {
        return $this->storeCategoryTemplateModel;
    }

    //########################################

    /**
     * @return int|string
     */
    public function getCategoryId()
    {
        $src = $this->getStoreCategoryTemplate()->getCategorySource();

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
