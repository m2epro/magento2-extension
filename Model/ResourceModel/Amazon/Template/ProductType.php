<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template;

class ProductType extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_DICTIONARY_PRODUCT_TYPE_ID = 'dictionary_product_type_id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_VIEW_MODE = 'view_mode';
    public const COLUMN_SETTINGS = 'settings';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

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

    protected function _construct(): void
    {
        $this->_init(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_TEMPLATE_PRODUCT_TYPE, self::COLUMN_ID);
    }
}
