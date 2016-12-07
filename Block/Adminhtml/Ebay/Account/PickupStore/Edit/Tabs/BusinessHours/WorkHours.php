<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\BusinessHours;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

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