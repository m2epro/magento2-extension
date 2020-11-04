<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\Processing;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\Processing\ProcessResult
 */
class ProcessResult extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const SINGLE_PROCESSINGS_PER_CRON_COUNT = 5000;
    const PARTIAL_PROCESSINGS_PER_CRON_COUNT = 5;

    const NICK = 'system/processing/process_result';

    //########################################

    protected function performActions()
    {
        $this->removeMissedProcessingLocks();
        $this->processExpired();

        $this->processCompleted(\Ess\M2ePro\Model\Processing::TYPE_SINGLE, self::SINGLE_PROCESSINGS_PER_CRON_COUNT);
        $this->processCompleted(\Ess\M2ePro\Model\Processing::TYPE_PARTIAL, self::PARTIAL_PROCESSINGS_PER_CRON_COUNT);
    }

    //########################################

    protected function removeMissedProcessingLocks()
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

    protected function processExpired()
    {
        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->setOnlyExpiredItemsFilter();
        $processingCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Processing[] $processingObjects */
        $processingObjects = $processingCollection->getItems();

        foreach ($processingObjects as $processingObject) {
            try {
                if (!$this->modelFactory->canCreateObject($processingObject->getModel())) {
                    throw new \Ess\M2ePro\Model\Exception(
                        sprintf('Responser runner model class "%s" does not exists', $processingObject->getModel())
                    );
                }

                /** @var \Ess\M2ePro\Model\Processing\Runner $processingRunner */
                $processingRunner = $this->modelFactory->getObject($processingObject->getModel());
                $processingRunner->setProcessingObject($processingObject);

                $processingRunner->processExpired();
                $processingRunner->complete();
            } catch (\Exception $exception) {
                $processingObject->forceRemove();
                $this->getHelper('Module\Exception')->process($exception);
            }
        }
    }

    //----------------------------------------

    protected function processCompleted($type, $limit)
    {
        $processingCollection = $this->activeRecordFactory->getObject('Processing')->getCollection();
        $processingCollection->addFieldToFilter('is_completed', 1);
        $processingCollection->addFieldToFilter('type', $type);
        $processingCollection->getSelect()->order('main_table.id ASC');
        $processingCollection->getSelect()->limit($limit);

        /** @var \Ess\M2ePro\Model\Processing[] $processingObjects */
        $processingObjects = $processingCollection->getItems();
        if (empty($processingObjects)) {
            return;
        }

        $iteration = 0;
        $percentsForOneAction = 50 / count($processingObjects);

        foreach ($processingObjects as $processingObject) {
            if ($iteration % 10 == 0) {
                $this->eventManager->dispatch(
                    \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME,
                    [
                        'progress_nick' => self::NICK,
                        'percentage'    => ceil($percentsForOneAction * $iteration),
                        'total'         => count($processingObjects)
                    ]
                );
            }

            try {
                if (!$this->modelFactory->canCreateObject($processingObject->getModel())) {
                    throw new \Ess\M2ePro\Model\Exception(
                        sprintf('Responser runner model class "%s" does not exists', $processingObject->getModel())
                    );
                }

                /** @var \Ess\M2ePro\Model\Processing\Runner $processingRunner */
                $processingRunner = $this->modelFactory->getObject($processingObject->getModel());
                $processingRunner->setProcessingObject($processingObject);

                $processingRunner->processSuccess() && $processingRunner->complete();
            } catch (\Exception $exception) {
                $processingObject->forceRemove();
                $this->getHelper('Module\Exception')->process($exception);
            }

            $iteration++;
        }
    }

    //####################################
}
