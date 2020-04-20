<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessPartial
 */
class ProcessPartial extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/connector_command_pending/process_partial';

    //####################################

    protected function performActions()
    {
        $this->removeMissedProcessingItems();
        $this->completeExpiredItems();
        $this->processCompletedItems();
    }

    //####################################

    protected function removeMissedProcessingItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Partial')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $collection->addFieldToFilter('p.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial[] $failedItems */
        $failedItems = $collection->getItems();
        if (empty($failedItems)) {
            return;
        }

        foreach ($failedItems as $failedItem) {
            $requestPendingPartial = $failedItem->getRequestPendingPartial();
            if ($requestPendingPartial != null && $requestPendingPartial->getId()) {
                $requestPendingPartial->delete();
            }

            $failedItem->delete();
        }
    }

    protected function completeExpiredItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Partial')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            [
                'rpp' => $this->activeRecordFactory->getObject('Request_Pending_Partial')->getResource()->getMainTable()
            ],
            'rpp.id = main_table.request_pending_partial_id',
            []
        );
        $collection->addFieldToFilter('rpp.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial[] $expiredItems */
        $expiredItems = $collection->getItems();
        if (empty($expiredItems)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $expiredMessage */
        $expiredMessage = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $expiredMessage->initFromPreparedData(
            'Request wait timeout exceeded.',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        foreach ($expiredItems as $expiredItem) {
            $processing = $expiredItem->getProcessing();

            $processing->setSettings('result_messages', [$expiredMessage->asArray()]);
            $processing->setData('is_completed', 1);
            $processing->save();

            $expiredItem->delete();
        }
    }

    protected function processCompletedItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Partial')
            ->getCollection();
        $collection->setCompletedRequestPendingPartialFilter();
        $collection->setNotCompletedProcessingFilter();

        /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial[] $requesterPartialObjects */
        $requesterPartialObjects = $collection->getItems();

        foreach ($requesterPartialObjects as $requesterPartialObject) {
            $processing = $requesterPartialObject->getProcessing();
            $processing->setSettings(
                'result_data',
                ['request_pending_partial_id' => $requesterPartialObject->getRequestPendingPartialId()]
            );
            $processing->setData('is_completed', 1);
            $processing->save();

            $requesterPartialObject->delete();
        }
    }

    //####################################
}
