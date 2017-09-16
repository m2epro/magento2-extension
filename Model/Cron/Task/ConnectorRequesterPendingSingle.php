<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Connector\Command\Pending\Requester\Single;
use Ess\M2ePro\Model\Connector\Connection\Response\Message;

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
        $collection = $collection = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Single')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            array('p' => $this->resource->getTableName('m2epro_processing')),
            'p.id = main_table.processing_id',
            array()
        );
        $collection->addFieldToFilter('p.id', array('null' => true));

        $failedItems = $collection->getItems();
        if (empty($failedItems)) {
            return;
        }

        foreach ($failedItems as $failedItem) {
            $requestPendingSingle = $failedItem->getRequestPendingSingle();
            if (!is_null($requestPendingSingle)) {
                $requestPendingSingle->delete();
            }

            $failedItem->delete();
        }
    }

    private function completeExpiredItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Single')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            array('rps' => $this->resource->getTableName('m2epro_request_pending_single')),
            'rps.id = main_table.request_pending_single_id',
            array()
        );
        $collection->addFieldToFilter('rps.id', array('null' => true));

        $expiredItems = $collection->getItems();
        if (empty($expiredItems)) {
            return;
        }

        $expiredMessage = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $expiredMessage->initFromPreparedData(
            'Request wait timeout exceeded.',
            Message::TYPE_ERROR
        );

        foreach ($expiredItems as $expiredItem) {
            $this->completeRequesterPendingSingle($expiredItem, array(), array($expiredMessage->asArray()));
        }
    }

    private function processCompletedItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Single')
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
        array $data = array(), array $messages = array()
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