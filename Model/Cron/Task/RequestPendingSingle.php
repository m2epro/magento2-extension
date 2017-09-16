<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Request\Pending\Single;

class RequestPendingSingle extends AbstractModel
{
    const NICK = 'request_pending_single';
    const MAX_MEMORY_LIMIT = 512;

    const STATUS_NOT_FOUND  = 'not_found';
    const STATUS_COMPLETE   = 'completed';
    const STATUS_PROCESSING = 'processing';

    const MAX_HASHES_PER_REQUEST = 100;

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
        $this->removeOutdated();
        $this->completeExpired();
        $this->executeInProgress();
    }

    //####################################

    private function removeOutdated()
    {
        $requestPendingSingleCollection = $this->activeRecordFactory->getObject('Request\Pending\Single')
            ->getCollection();
        $requestPendingSingleCollection->setOnlyOutdatedItemsFilter();
        $requestPendingSingleCollection->addFieldToFilter('is_completed', 1);

        /** @var Single[] $requestsPendingSingleObjects */
        $requestsPendingSingleObjects = $requestPendingSingleCollection->getItems();

        foreach ($requestsPendingSingleObjects as $requestsPendingSingleObject) {
            $requestsPendingSingleObject->delete();
        }
    }

    private function completeExpired()
    {
        $requestPendingSingleCollection = $this->activeRecordFactory->getObject('Request\Pending\Single')
            ->getCollection();
        $requestPendingSingleCollection->setOnlyExpiredItemsFilter();
        $requestPendingSingleCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Request\Pending\Single[] $expiredRequestPendingSingleObjects */
        $expiredRequestPendingSingleObjects = $requestPendingSingleCollection->getItems();

        foreach ($expiredRequestPendingSingleObjects as $requestPendingSingle) {
            $this->completeRequest($requestPendingSingle, array(), array($this->getFailedMessage()->asArray()));
        }
    }

    private function executeInProgress()
    {
        $componentsInProgress = $this->activeRecordFactory
            ->getObject('Request\Pending\Single')->getResource()->getComponentsInProgress();

        foreach ($componentsInProgress as $component) {
            $requestPendingSingleCollection = $this->activeRecordFactory
                ->getObject('Request\Pending\Single')->getCollection();
            $requestPendingSingleCollection->addFieldToFilter('component', $component);
            $requestPendingSingleCollection->addFieldToFilter('is_completed', 0);

            $serverHashes = $requestPendingSingleCollection->getColumnValues('server_hash');
            $serverHashesPacks = array_chunk($serverHashes, self::MAX_HASHES_PER_REQUEST);

            foreach ($serverHashesPacks as $serverHashesPack) {

                $results = $this->getResultsFromServer($component, $serverHashesPack);

                foreach ($serverHashesPack as $serverHash) {
                    /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
                    $requestPendingSingle = $requestPendingSingleCollection->getItemByColumnValue(
                        'server_hash', $serverHash
                    );

                    if (!isset($results[$serverHash]['status']) ||
                        $results[$serverHash]['status'] == self::STATUS_NOT_FOUND
                    ) {
                        $this->completeRequest(
                            $requestPendingSingle, array(), array($this->getFailedMessage()->asArray())
                        );
                        continue;
                    }

                    if ($results[$serverHash]['status'] != self::STATUS_COMPLETE) {
                        continue;
                    }

                    $data = array();
                    if (isset($results[$serverHash]['data'])) {
                        $data = $results[$serverHash]['data'];
                    }

                    $messages = array();
                    if (isset($results[$serverHash]['messages'])) {
                        $messages = $results[$serverHash]['messages'];
                    }

                    $this->completeRequest($requestPendingSingle, $data, $messages);
                }
            }
        }
    }

    //####################################

    private function getResultsFromServer($component, array $serverHashes)
    {
        $dispatcher = $this->modelFactory->getObject(ucfirst($component).'\Connector\Dispatcher');
        $connector = $dispatcher->getVirtualConnector(
            'processing','get','results',
            array('processing_ids' => $serverHashes),
            'results', NULL, NULL
        );

        $dispatcher->process($connector);

        return $connector->getResponseData();
    }

    private function getFailedMessage()
    {
        $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.', Message::TYPE_ERROR
        );

        return $message;
    }

    private function completeRequest(Single $requestPendingSingle, array $data, array $messages)
    {
        $requestPendingSingle->setSettings('result_data', $data);
        $requestPendingSingle->setSettings('result_messages', $messages);

        $requestPendingSingle->setData('is_completed', 1);

        $requestPendingSingle->save();
    }

    //####################################
}