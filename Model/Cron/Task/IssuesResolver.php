<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

class IssuesResolver extends AbstractModel
{
    const NICK = 'issues_resolver';
    const MAX_MEMORY_LIMIT = 512;

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //########################################

    protected function performActions()
    {
        $this->removeMissedProcessingLocks();
    }

    //########################################

    private function removeMissedProcessingLocks()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Processing\Lock\Collection */
        $collection = $this->activeRecordFactory->getObject('Processing\Lock')->getCollection();
        $collection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $collection->addFieldToFilter('p.id', array('null' => true));

        $logData = [];
        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Processing\Lock $item */

            if (!isset($logData[$item->getModelName()][$item->getObjectId()]) ||
                !in_array($item->getTag(), $logData[$item->getModelName()][$item->getObjectId()]))
            {
                $logData[$item->getModelName()][$item->getObjectId()][] = $item->getTag();
            }

            $item->delete();
        }

        if (!empty($logData)) {
            $this->helperFactory->getObject('Module\Logger')->process(
                $logData, 'Processing Locks Records were broken and removed', false
            );
        }
    }

    //########################################
}