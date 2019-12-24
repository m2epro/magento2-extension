<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingPartial
 */
class ConnectorRequesterPendingPartial extends AbstractModel
{
    const NICK = 'connector_requester_pending_partial';
    const MAX_MEMORY_LIMIT = 512;

    //####################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //####################################

    protected function performActions()
    {
        $this->removeMissedProcessingItems();
        $this->completeExpiredItems();
        $this->processCompletedItems();
    }

    //####################################

    private function removeMissedProcessingItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Partial')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            ['p' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_processing')],
            'p.id = main_table.processing_id',
            []
        );
        $collection->addFieldToFilter('p.id', ['null' => true]);

        $failedItems = $collection->getItems();
        if (empty($failedItems)) {
            return;
        }

        foreach ($failedItems as $failedItem) {
            $requestPendingPartial = $failedItem->getRequestPendingPartial();
            if ($requestPendingPartial !== null) {
                $requestPendingPartial->delete();
            }

            $failedItem->delete();
        }
    }

    private function completeExpiredItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Partial')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            [
                'rpp' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_request_pending_partial')
            ],
            'rpp.id = main_table.request_pending_partial_id',
            []
        );
        $collection->addFieldToFilter('rpp.id', ['null' => true]);

        $expiredItems = $collection->getItems();
        if (empty($expiredItems)) {
            return;
        }

        $expiredMessage = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $expiredMessage->initFromPreparedData(
            'Request wait timeout exceeded.',
            Message::TYPE_ERROR
        );

        foreach ($expiredItems as $expiredItem) {
            $processing = $expiredItem->getProcessing();

            $processing->setSettings('result_messages', [$expiredMessage->asArray()]);
            $processing->setData('is_completed', 1);
            $processing->save();

            $expiredItem->delete();
        }
    }

    private function processCompletedItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Partial')
            ->getCollection();
        $collection->setCompletedRequestPendingPartialFilter();
        $collection->setNotCompletedProcessingFilter();

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
