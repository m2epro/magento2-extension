<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

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