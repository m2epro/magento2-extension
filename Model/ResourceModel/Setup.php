<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

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
        $maxCompletedItem = NULL;

        foreach ($completedItems as $completedItem) {
            if (is_null($maxCompletedItem)) {
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