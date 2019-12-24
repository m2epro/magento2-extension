<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\BusinessHours;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\BusinessHours\WorkHours
 */
class WorkHours extends AbstractBlock
{
    protected $_template = 'ebay/account/pickup_store/work_hours.phtml';

    //########################################

    public function isDayExistInWeekSettingsArray($day, $weekDays)
    {
        return in_array(strtolower($day), $weekDays);
    }

    //########################################
}
