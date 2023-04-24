<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\TemplateShipping;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Dictionary\TemplateShipping::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\TemplateShipping::class
        );
    }

    /**
     * @param int $accountId
     *
     * @return $this
     */
    public function appendFilterAccountId(int $accountId): self
    {
        $this->getSelect()->where('main_table.account_id = ?', $accountId);

        return $this;
    }
}
