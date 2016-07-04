<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner;

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

        $requestPendingSingleCollection = $this->activeRecordFactory->getObject('Request\Pending\Single')
            ->getCollection();
        $requestPendingSingleCollection->addFieldToFilter('component', $params['component']);
        $requestPendingSingleCollection->addFieldToFilter('server_hash', $params['server_hash']);

        /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
        $requestPendingSingle = $requestPendingSingleCollection->getFirstItem();

        if (!$requestPendingSingle->getId()) {
            $requestPendingSingle->setData(array(
                'component'       => $params['component'],
                'server_hash'     => $params['server_hash'],
                'expiration_date' => $this->getHelper('Data')->getDate(
                    $this->getHelper('Data')->getCurrentGmtDate(true)+static::PENDING_REQUEST_MAX_LIFE_TIME
                )
            ));

            $requestPendingSingle->save();
        }

        $requesterSingle = $this->activeRecordFactory->getObject('Connector\Command\Pending\Requester\Single');
        $requesterSingle->setData(array(
            'processing_id'             => $this->getProcessingObject()->getId(),
            'request_pending_single_id' => $requestPendingSingle->getId(),
        ));

        $requesterSingle->save();
    }

    // ##################################
}