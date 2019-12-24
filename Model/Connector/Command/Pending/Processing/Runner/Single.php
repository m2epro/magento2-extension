<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner;

/**
 * Class \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
 */
class Single extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner
{
    // ##################################

    public function processSuccess()
    {
        try {
            $this->getResponser()->process();
        } catch (\Exception $exception) {
            $this->getResponser()->failDetected($exception->getMessage());
        }

        return true;
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

        $requestPendingSingleCollection = $this->activeRecordFactory->getObject('Request_Pending_Single')
            ->getCollection();
        $requestPendingSingleCollection->addFieldToFilter('component', $params['component']);
        $requestPendingSingleCollection->addFieldToFilter('server_hash', $params['server_hash']);

        /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
        $requestPendingSingle = $requestPendingSingleCollection->getFirstItem();

        if (!$requestPendingSingle->getId()) {
            $requestPendingSingle->setData([
                'component'       => $params['component'],
                'server_hash'     => $params['server_hash'],
                'expiration_date' => $this->getHelper('Data')->getDate(
                    $this->getHelper('Data')->getCurrentGmtDate(true)+static::PENDING_REQUEST_MAX_LIFE_TIME
                )
            ]);

            $requestPendingSingle->save();
        }

        $requesterSingle = $this->activeRecordFactory->getObject('Connector_Command_Pending_Requester_Single');
        $requesterSingle->setData([
            'processing_id'             => $this->getProcessingObject()->getId(),
            'request_pending_single_id' => $requestPendingSingle->getId(),
        ]);

        $requesterSingle->save();
    }

    // ##################################
}
