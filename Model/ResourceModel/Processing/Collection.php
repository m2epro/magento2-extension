<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Processing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Processing\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
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
            'expiration_date',
            ['lt' => $this->helperFactory->getObject('Data')->getCurrentGmtDate()]
        );
        return $this;
    }

    // ########################################
}
