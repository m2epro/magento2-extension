<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Connector\Command\Pending\Requester\Single;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\ConnectorRequesterPendingSingle
 */
class ConnectorRequesterPendingSingle extends AbstractModel
{
    const NICK = 'connector_requester_pending_single';
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
        $collection = $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Single')
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
            $requestPendingSingle = $failedItem->getRequestPendingSingle();
            if ($requestPendingSingle !== null) {
                $requestPendingSingle->delete();
            }

            $failedItem->delete();
        }
    }

    private function completeExpiredItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Single')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            [
                'rps' => $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_request_pending_single')
            ],
            'rps.id = main_table.request_pending_single_id',
            []
        );
        $collection->addFieldToFilter('rps.id', ['null' => true]);

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
            $this->completeRequesterPendingSingle($expiredItem, [], [$expiredMessage->asArray()]);
        }
    }

    private function processCompletedItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Single')
            ->getCollection();
        $collection->setCompletedRequestPendingSingleFilter();
        $collection->setNotCompletedProcessingFilter();

        $requesterSingleObjects = $collection->getItems();

        foreach ($requesterSingleObjects as $requesterSingleObject) {
            $this->completeRequesterPendingSingle(
                $requesterSingleObject,
                $requesterSingleObject->getRequestPendingSingle()->getResultData(),
                $requesterSingleObject->getRequestPendingSingle()->getResultMessages()
            );
        }
    }

    //####################################

    private function completeRequesterPendingSingle(
        Single $requesterPendingSingle,
        array $data = [],
        array $messages = []
    ) {
        $processing = $requesterPendingSingle->getProcessing();

        $processing->setSettings('result_data', $data);
        $processing->setSettings('result_messages', $messages);
        $processing->setData('is_completed', 1);
        $processing->save();

        $requesterPendingSingle->delete();
    }

    //####################################
}
