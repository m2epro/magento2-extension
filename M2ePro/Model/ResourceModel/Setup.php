<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Setup
 */
class Setup extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_setup', 'id');
    }

    //########################################

    public function getMaxCompletedItem()
    {
        $collection = $this->activeRecordFactory->getObject('Setup')->getCollection();
        $collection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Setup[] $completedItems */
        $completedItems = $collection->getItems();

        /** @var \Ess\M2ePro\Model\Setup $maxCompletedItem */
        $maxCompletedItem = null;

        foreach ($completedItems as $completedItem) {
            if ($maxCompletedItem === null) {
                $maxCompletedItem = $completedItem;
                continue;
            }

            if (version_compare($maxCompletedItem->getVersionTo(), $completedItem->getVersionTo(), '>')) {
                continue;
            }

            $maxCompletedItem = $completedItem;
        }

        return $maxCompletedItem;
    }

    //########################################
}
