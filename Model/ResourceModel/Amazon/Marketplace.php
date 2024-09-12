<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

class Marketplace extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_DEFAULT_CURRENCY = 'default_currency';
    public const COLUMN_IS_MERCHANT_FULFILLMENT_AVAILABLE = 'is_merchant_fulfillment_available';
    public const COLUMN_IS_BUSINESS_AVAILABLE = 'is_business_available';
    public const COLUMN_IS_VAT_CALCULATION_SERVICE_AVAILABLE = 'is_vat_calculation_service_available';
    public const COLUMN_IS_PRODUCT_TAX_CODE_POLICY_AVAILABLE = 'is_product_tax_code_policy_available';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_MARKETPLACE,
            self::COLUMN_MARKETPLACE_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
