<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\IssuesResolver;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\IssuesResolver\RemoveMissedProcessingLocks
 */
class RemoveMissedProcessingLocks extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/issues_resolver/remove_missed_processing_locks';

    //########################################

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Processing\Lock\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Processing\Lock')->getCollection();
        $collection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $collection->addFieldToFilter('p.id', ['null' => true]);

        $logData = [];
        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Processing\Lock $item */

            if (!isset($logData[$item->getModelName()][$item->getObjectId()]) ||
                !in_array($item->getTag(), $logData[$item->getModelName()][$item->getObjectId()])) {
                $logData[$item->getModelName()][$item->getObjectId()][] = $item->getTag();
            }

            $item->delete();
        }

        if (!empty($logData)) {
            $this->getHelper('Module\Logger')->process(
                $logData,
                'Processing Locks Records were broken and removed',
                false
            );
        }
    }

    //########################################
}
