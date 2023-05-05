<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order\Log;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ----------------------------------------

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Order\Log::class,
            \Ess\M2ePro\Model\ResourceModel\Order\Log::class
        );
    }

    // ----------------------------------------

    /**
     * GroupBy fix
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $originSelect = clone $this->getSelect();
        $originSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $originSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $originSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $originSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $originSelect->columns(['*']);

        $countSelect = clone $originSelect;
        $countSelect->reset();
        $countSelect->from($originSelect, null);
        $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));

        return $countSelect;
    }

    public function onlyVatChanged(): Collection
    {
        $condition = sprintf(
            "JSON_EXTRACT(main_table.additional_data, '$.%s') IS NOT NULL",
            \Ess\M2ePro\Model\Order::ADDITIONAL_DATA_KEY_VAT_REVERSE_CHARGE
        );
        $this->getSelect()->where($condition);

        return $this;
    }

    public function createdDateGreaterThenOrEqual(\DateTime $date): Collection
    {
        $this->addFieldToFilter('main_table.create_date', [
            'gteq' => $date->format('Y-m-d H:i:s')
        ]);

        return $this;
    }

    //########################################
}
