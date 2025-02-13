<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Account;

/**
 * @method \Ess\M2ePro\Model\Amazon\Account getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Account::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account::class
        );
    }
}
