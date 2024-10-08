<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel;

class Product extends AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_WIZARD_ID = 'wizard_id';
    public const COLUMN_UNMANAGED_PRODUCT_ID = 'unmanaged_product_id';
    public const COLUMN_MAGENTO_PRODUCT_ID = 'magento_product_id';
    public const COLUMN_EBAY_ITEM_ID = 'ebay_item_id';
    public const COLUMN_VALIDATION_STATUS = 'validation_status';
    public const COLUMN_VALIDATION_ERRORS = 'validation_errors';
    public const COLUMN_TEMPLATE_CATEGORY_ID = 'template_category_id';
    public const COLUMN_TEMPLATE_CATEGORY_SECONDARY_ID = 'template_category_secondary_id';
    public const COLUMN_STORE_CATEGORY_ID = 'store_category_id';
    public const COLUMN_STORE_CATEGORY_SECONDARY_ID = 'store_category_secondary_id';
    public const COLUMN_IS_PROCESSED = 'is_processed';

    protected function _construct(): void
    {
        $this->_init(Tables::TABLE_NAME_EBAY_LISTING_WIZARD_PRODUCT, self::COLUMN_ID);
    }
}
