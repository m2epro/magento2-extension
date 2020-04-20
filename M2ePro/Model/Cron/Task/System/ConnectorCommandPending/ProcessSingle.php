<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessSingle
 */
class ProcessSingle extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/connector_command_pending/process_single';

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
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Single')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            ['p' => $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable()],
            'p.id = main_table.processing_id',
            []
        );
        $collection->addFieldToFilter('p.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single[] $failedItems */
        $failedItems = $collection->getItems();
        if (empty($failedItems)) {
            return;
        }

        foreach ($failedItems as $failedItem) {
            $requestPendingSingle = $failedItem->getRequestPendingSingle();
            if ($requestPendingSingle != null && $requestPendingSingle->getId()) {
                $requestPendingSingle->delete();
            }

            $failedItem->delete();
        }
    }

    protected function completeExpiredItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Single')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            [
                'rps' => $this->activeRecordFactory->getObject('Request_Pending_Single')->getResource()
                ->getMainTable()
            ],
            'rps.id = main_table.request_pending_single_id',
            []
        );
        $collection->addFieldToFilter('rps.id', ['null' => true]);

        /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single[] $expiredItems */
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
            $this->completeRequesterPendingSingle($expiredItem, [], [$expiredMessage->asArray()]);
        }
    }

    protected function processCompletedItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Single')
            ->getCollection();
        $collection->setCompletedRequestPendingSingleFilter();
        $collection->setNotCompletedProcessingFilter();

        /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single[] $requesterSingleObjects */
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

    protected function completeRequesterPendingSingle(
        \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Single $requesterPendingSingle,
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
