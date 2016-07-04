<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner;

class Partial extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner
{
    const MAX_PROCESSING_PACKS_COUNT = 3;

    /** @var \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial */
    private $requestPendingPartial = NULL;

    // ##################################

    protected function getResponse()
    {
        if (!is_null($this->response)) {
            return $this->response;
        }

        $this->response = $this->modelFactory->getObject('Connector\Connection\Response');

        $params = $this->getParams();
        if (!empty($params['request_time'])) {
            $this->response->setRequestTime($params['request_time']);
        }

        return $this->response;
    }

    // ##################################

    public function processSuccess()
    {
        try {
            for ($i = 1; $i <= self::MAX_PROCESSING_PACKS_COUNT; $i++) {
                $data = $this->getNextData();

                if (empty($data)) {
                    if ($this->getMessages()) {
                        $this->getResponse()->initFromPreparedResponse(array(), $this->getMessages());
                        $this->getResponser(true)->process();
                    }

                    return true;
                }

                $this->incrementNextDataPartNumber();

                $this->getResponse()->initFromPreparedResponse($data);
                $this->getResponser(true)->process();
            }
        } catch (\Exception $exception) {
            $this->getResponser()->failDetected($exception->getMessage());
            return true;
        }

        return false;
    }

    public function processExpired()
    {
        $this->getResponser()->failDetected($this->getExpiredErrorMessage());
    }

    public function complete()
    {
        try {
            parent::complete();
        } catch (\Exception $exception) {
            $this->getResponser()->failDetected($exception->getMessage());
            throw $exception;
        }
    }

    // ##################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $requestPendingPartialCollection = $this->activeRecordFactory->getObject('Request\Pending\Partial')
            ->getCollection();
        $requestPendingPartialCollection->addFieldToFilter('component', $params['component']);
        $requestPendingPartialCollection->addFieldToFilter('server_hash', $params['server_hash']);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial */
        $requestPendingPartial = $requestPendingPartialCollection->getFirstItem();

        if (!$requestPendingPartial->getId()) {
            $requestPendingPartial->setData(array(
                'component'       => $params['component'],
                'server_hash'     => $params['server_hash'],
                'next_part'       => 1,
                'expiration_date' => $this->getHelper('Data')->getDate(
                    $this->getHelper('Data')->getCurrentGmtDate(true)+self::PENDING_REQUEST_MAX_LIFE_TIME
                )
            ));

            $requestPendingPartial->save();
        }

        $requesterPartial = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Partial');
        $requesterPartial->setData(array(
            'processing_id'              => $this->getProcessingObject()->getId(),
            'request_pending_partial_id' => $requestPendingPartial->getId(),
            'next_data_part_number'      => 1,
        ));

        $requesterPartial->save();
    }

    // ##################################

    private function getNextData()
    {
        if (is_null($this->getRequestPendingPartialObject())) {
            return array();
        }

        return $this->getRequestPendingPartialObject()->getResultData($this->getNextDataPartNumber());
    }

    private function getMessages()
    {
        if (is_null($this->getRequestPendingPartialObject())) {
            return array();
        }

        return $this->getRequestPendingPartialObject()->getResultMessages();
    }

    // ##################################

    protected function getRequestPendingPartialObject()
    {
        if (!is_null($this->requestPendingPartial)) {
            return $this->requestPendingPartial;
        }

        $resultData = $this->getProcessingObject()->getResultData();
        if (empty($resultData['request_pending_partial_id'])) {
            return NULL;
        }

        $requestPendingPartialId = (int)$resultData['request_pending_partial_id'];

        $requestPendingPartial = $this->activeRecordFactory->getObjectLoaded(
            'Request\Pending\Partial', $requestPendingPartialId, NULL, false
        );

        if (is_null($requestPendingPartial) || !$requestPendingPartial->getId()) {
            return NULL;
        }

        return $this->requestPendingPartial = $requestPendingPartial;
    }

    // ##################################

    protected function getNextDataPartNumber()
    {
        $resultData = $this->getProcessingObject()->getResultData();
        if (empty($resultData['next_data_part_number'])) {
            return 1;
        }

        return (int)$resultData['next_data_part_number'];
    }

    protected function incrementNextDataPartNumber()
    {
        $resultData = $this->getProcessingObject()->getResultData();
        $resultData['next_data_part_number'] = $this->getNextDataPartNumber() + 1;
        $this->getProcessingObject()->setSettings('result_data', $resultData);
        $this->getProcessingObject()->save();
    }

    // ##################################
}