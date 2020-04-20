<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode getResource()
 */
class ProductTaxCode extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const PRODUCT_TAX_CODE_MODE_VALUE     = 1;
    const PRODUCT_TAX_CODE_MODE_ATTRIBUTE = 2;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\Source[]
     */
    private $productTaxCodeSourceModels = [];

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;

        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Amazon_Listing_Product')
            ->getCollection()
            ->addFieldToFilter('template_product_tax_code_id', $this->getId())
            ->getSize();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $id = $magentoProduct->getProductId();

        if (!empty($this->productTaxCodeSourceModels[$id])) {
            return $this->productTaxCodeSourceModels[$id];
        }

        $this->productTaxCodeSourceModels[$id] = $this->modelFactory->getObject(
            'Amazon_Template_ProductTaxCode_Source'
        );

        $this->productTaxCodeSourceModels[$id]->setMagentoProduct($magentoProduct);
        $this->productTaxCodeSourceModels[$id]->setProductTaxCodeTemplate($this);

        return $this->productTaxCodeSourceModels[$id];
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    public function getProductTaxCodeMode()
    {
        return (int)$this->getData('product_tax_code_mode');
    }

    public function isProductTaxCodeModeValue()
    {
        return $this->getProductTaxCodeMode() == self::PRODUCT_TAX_CODE_MODE_VALUE;
    }

    public function isProductTaxCodeModeAttribute()
    {
        return $this->getProductTaxCodeMode() == self::PRODUCT_TAX_CODE_MODE_ATTRIBUTE;
    }

    // ---------------------------------------

    public function getProductTaxCodeValue()
    {
        return $this->getData('product_tax_code_value');
    }

    public function getProductTaxCodeAttribute()
    {
        return $this->getData('product_tax_code_attribute');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    public function getProductTaxCodeAttributes()
    {
        $attributes = [];

        if ($this->isProductTaxCodeModeAttribute()) {
            $attributes[] = $this->getProductTaxCodeAttribute();
        }

        return $attributes;
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################
}
