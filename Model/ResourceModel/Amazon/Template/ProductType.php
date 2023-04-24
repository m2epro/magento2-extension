<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template;

class ProductType extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
        $this->productTypeFactory = $productTypeFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('m2epro_amazon_template_product_type', 'id');
    }

    /**
     * @param int $productTypeId
     *
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductType
     */
    public function loadById(int $productTypeId): \Ess\M2ePro\Model\Amazon\Template\ProductType
    {
        $productType = $this->productTypeFactory->create();
        $this->load($productType, $productTypeId);

        return $productType;
    }
}
