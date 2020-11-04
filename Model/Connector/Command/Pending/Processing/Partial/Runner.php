<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial;

/**
 * Class \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial\Runner
 */
class Runner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner
{
    const MAX_PARTS_PER_RUN = 5;

    /** @var \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial */
    protected $requestPendingPartial;

    //##################################

    public function getType()
    {
        return \Ess\M2ePro\Model\Processing::TYPE_PARTIAL;
    }

    //##################################

    protected function getResponse()
    {
        if ($this->response !== null) {
            return $this->response;
        }

        $this->response = $this->modelFactory->getObject('Connector_Connection_Response');

        $params = $this->getParams();
        if (!empty($params['request_time'])) {
            $this->response->setRequestTime($params['request_time']);
        }

        return $this->response;
    }

    //##################################

    public function processSuccess()
    {
        try {
            for ($i = 0; $i < self::MAX_PARTS_PER_RUN; $i++) {
                $data = $this->getNextData();

                if (empty($data)) {
                    if ($this->getMessages()) {
                        $this->getResponse()->initFromPreparedResponse([], $this->getMessages());
                        $this->getResponser(true)->process();
                    } else {
                        $this->afterLastDataPartProcessed();
                    }

                    return true;
                }

                $this->getResponse()->initFromPreparedResponse($data);
                $this->getResponser(true)->process();

                $this->incrementNextDataPartNumber();
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

    //##################################

    protected function eventBefore()
    {
        parent::eventBefore();

        $params = $this->getParams();

        $requestPendingPartialCollection = $this->activeRecordFactory->getObject('Request_Pending_Partial')
            ->getCollection();
        $requestPendingPartialCollection->addFieldToFilter('component', $params['component']);
        $requestPendingPartialCollection->addFieldToFilter('server_hash', $params['server_hash']);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial */
        $requestPendingPartial = $requestPendingPartialCollection->getFirstItem();

        if (!$requestPendingPartial->getId()) {
            $requestPendingPartial->setData(
                [
                    'component'       => $params['component'],
                    'server_hash'     => $params['server_hash'],
                    'next_part'       => 1,
                    'expiration_date' => $this->getHelper('Data')->getDate(
                        $this->getHelper('Data')->getCurrentGmtDate(true)+self::PENDING_REQUEST_MAX_LIFE_TIME
                    )
                ]
            );

            $requestPendingPartial->save();
        }

        $requesterPartial = $this->activeRecordFactory->getObject('Connector_Command_Pending_Processing_Partial');
        $requesterPartial->setData(
            [
                'processing_id'              => $this->getProcessingObject()->getId(),
                'request_pending_partial_id' => $requestPendingPartial->getId(),
                'next_data_part_number'      => 1,
            ]
        );

        $requesterPartial->save();
    }

    protected function afterLastDataPartProcessed()
    {
        return null;
    }

    //##################################

    protected function getNextData()
    {
        if ($this->getRequestPendingPartialObject() === null) {
            return [];
        }

        return $this->getRequestPendingPartialObject()->getResultData($this->getNextDataPartNumber());
    }

    protected function getMessages()
    {
        if ($this->getRequestPendingPartialObject() === null) {
            return [];
        }

        return $this->getRequestPendingPartialObject()->getResultMessages();
    }

    //##################################

    protected function getRequestPendingPartialObject()
    {
        if ($this->requestPendingPartial !== null) {
            return $this->requestPendingPartial;
        }

        $resultData = $this->getProcessingObject()->getResultData();
        if (empty($resultData['request_pending_partial_id'])) {
            return null;
        }

        $requestPendingPartialId = (int)$resultData['request_pending_partial_id'];

        $requestPendingPartial = $this->activeRecordFactory->getObjectLoaded(
            'Request_Pending_Partial',
            $requestPendingPartialId,
            null,
            false
        );

        if ($requestPendingPartial === null || !$requestPendingPartial->getId()) {
            return null;
        }

        return $this->requestPendingPartial = $requestPendingPartial;
    }

    //##################################

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

    //##################################
}
