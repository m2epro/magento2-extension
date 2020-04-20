<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\VersionsHistory
 */
class VersionsHistory extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_versions_history', 'id');
    }

    //########################################

    public function getLastItem()
    {
        $collection = $this->activeRecordFactory->getObject('VersionsHistory')->getCollection();
        $collection->setOrder('create_date', $collection::SORT_ORDER_DESC);
        $collection->getSelect()->limit(1);

        return $collection->getFirstItem();
    }

    //########################################
}
