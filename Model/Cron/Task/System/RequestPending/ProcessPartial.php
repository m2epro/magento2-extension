<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System\RequestPending;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessPartial
 */
class ProcessPartial extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/request_pending/process_partial';

    const STATUS_NOT_FOUND  = 'not_found';
    const STATUS_COMPLETE   = 'completed';
    const STATUS_PROCESSING = 'processing';

    const MAX_PARTS_PER_ONE_ITERATION = 5;

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //####################################

    protected function performActions()
    {
        $this->removeOutdated();
        $this->completeExpired();
        $this->executeInProgress();
    }

    //####################################

    protected function removeOutdated()
    {
        $requestPendingPartialCollection = $this->activeRecordFactory->getObject('Request_Pending_Partial')
            ->getCollection();
        $requestPendingPartialCollection->setOnlyOutdatedItemsFilter();
        $requestPendingPartialCollection->addFieldToFilter('is_completed', 1);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial[] $requestsPendingPartialObjects */
        $requestsPendingPartialObjects = $requestPendingPartialCollection->getItems();

        foreach ($requestsPendingPartialObjects as $requestsPendingPartialObject) {
            $requestsPendingPartialObject->delete();
        }
    }

    protected function completeExpired()
    {
        $requestPendingPartialCollection = $this->activeRecordFactory->getObject('Request_Pending_Partial')
            ->getCollection();
        $requestPendingPartialCollection->setOnlyExpiredItemsFilter();
        $requestPendingPartialCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial[] $expiredRequestPendingPartialObjects */
        $expiredRequestPendingPartialObjects = $requestPendingPartialCollection->getItems();

        foreach ($expiredRequestPendingPartialObjects as $requestPendingPartialObject) {
            $this->completeRequest($requestPendingPartialObject, [$this->getFailedMessage()->asArray()]);
        }
    }

    protected function executeInProgress()
    {
        $requestPendingPartialCollection = $this->activeRecordFactory->getObject('Request_Pending_Partial')
            ->getCollection();
        $requestPendingPartialCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial[] $requestPendingPartialObjects */
        $requestPendingPartialObjects = $requestPendingPartialCollection->getItems();

        foreach ($requestPendingPartialObjects as $requestPendingPartial) {
            $this->processRequest($requestPendingPartial);
        }
    }

    //####################################

    protected function processRequest(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial)
    {
        for ($requestCount = 1; $requestCount <= self::MAX_PARTS_PER_ONE_ITERATION; $requestCount++) {
            $serverData = $this->getServerData($requestPendingPartial);

            if ($serverData['status'] == self::STATUS_NOT_FOUND) {
                $this->completeRequest($requestPendingPartial, [$this->getFailedMessage()->asArray()]);
                break;
            }

            if ($serverData['status'] != self::STATUS_COMPLETE) {
                break;
            }

            $requestPendingPartial->addResultData($serverData['data']);

            if (!empty($serverData['next_part'])) {
                continue;
            }

            $this->completeRequest($requestPendingPartial, $serverData['messages']);
            break;
        }
    }

    protected function getServerData(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial)
    {
        $dispatcher = $this->modelFactory->getObject(
            ucfirst($requestPendingPartial->getComponent()) . '\Connector\Dispatcher'
        );
        $connector = $dispatcher->getVirtualConnector(
            'processing',
            'get',
            'results',
            [
                'processing_id' => $requestPendingPartial->getServerHash(),
                'necessary_parts' => [
                    $requestPendingPartial->getServerHash() => $requestPendingPartial->getNextPart(),
                ],
            ],
            'results',
            null,
            null
        );

        $dispatcher->process($connector);
        $result = $connector->getResponseData();

        return $result[$requestPendingPartial->getServerHash()];
    }

    //####################################

    protected function getFailedMessage()
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.',
            \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
        );

        return $message;
    }

    protected function completeRequest(
        \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial,
        array $messages = []
    ) {
        $requestPendingPartial->setSettings('result_messages', $messages);
        $requestPendingPartial->setData('next_part', null);
        $requestPendingPartial->setData('is_completed', 1);

        $requestPendingPartial->save();
    }

    //####################################
}
