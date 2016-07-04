<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Processing;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
{
    // ########################################

    public function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\Processing',
            'Ess\M2ePro\Model\ResourceModel\Processing'
        );
    }

    // ########################################

    public function setOnlyExpiredItemsFilter()
    {
        $this->addFieldToFilter(
            'expiration_date', array('lt' => $this->helperFactory->getObject('Data')->getCurrentGmtDate())
        );
        return $this;
    }

    // ########################################
}