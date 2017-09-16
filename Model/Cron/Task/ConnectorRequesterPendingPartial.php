<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;

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
        $collection = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Partial')
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
            $requestPendingPartial = $failedItem->getRequestPendingPartial();
            if (!is_null($requestPendingPartial)) {
                $requestPendingPartial->delete();
            }

            $failedItem->delete();
        }
    }

    private function completeExpiredItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Partial')
            ->getCollection();
        $collection->getSelect()->joinLeft(
            array('rpp' => $this->resource->getTableName('m2epro_request_pending_partial')),
            'rpp.id = main_table.request_pending_partial_id',
            array()
        );
        $collection->addFieldToFilter('rpp.id', array('null' => true));

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
            $processing = $expiredItem->getProcessing();

            $processing->setSettings('result_messages', array($expiredMessage->asArray()));
            $processing->setData('is_completed', 1);
            $processing->save();

            $expiredItem->delete();
        }
    }

    private function processCompletedItems()
    {
        $collection = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Partial')
            ->getCollection();
        $collection->setCompletedRequestPendingPartialFilter();
        $collection->setNotCompletedProcessingFilter();

        $requesterPartialObjects = $collection->getItems();

        foreach ($requesterPartialObjects as $requesterPartialObject) {

            $processing = $requesterPartialObject->getProcessing();
            $processing->setSettings(
                'result_data',
                array('request_pending_partial_id' => $requesterPartialObject->getRequestPendingPartialId())
            );
            $processing->setData('is_completed', 1);
            $processing->save();

            $requesterPartialObject->delete();
        }
    }

    //####################################
}