<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Account\MerchantSetting;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Account\MerchantSetting::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Account\MerchantSetting::class
        );
    }
}
