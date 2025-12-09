<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template;

class SellingFormat extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_REPRICER_MIN_PRICE_MODE = 'repricer_min_price_mode';
    public const COLUMN_REPRICER_MIN_PRICE_ATTRIBUTE = 'repricer_min_price_attribute';
    public const COLUMN_REPRICER_MAX_PRICE_MODE = 'repricer_max_price_mode';
    public const COLUMN_REPRICER_MAX_PRICE_ATTRIBUTE = 'repricer_max_price_attribute';
    public const COLUMN_REPRICER_ACCOUNT_STRATEGIES = 'repricer_account_strategies';

    /** @var bool */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_TEMPLATE_SELLING_FORMAT,
            'template_selling_format_id'
        );
        $this->_isPkAutoIncrement = false;
    }
}
