<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending;

abstract class Requester extends \Ess\M2ePro\Model\Connector\Command\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single $processingRunner */
    protected $processingRunner = NULL;

    protected $processingServerHash = NULL;

    /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Responser $responser */
    protected $responser = NULL;

    protected $preparedResponseData = NULL;

    // ########################################

    protected function getProcessingRunner()
    {
        if (!is_null($this->processingRunner)) {
            return $this->processingRunner;
        }

        $this->processingRunner = $this->modelFactory->getObject($this->getProcessingRunnerModelName());

        $this->processingRunner->setParams($this->getProcessingParams());

        $this->processingRunner->setResponserModelName($this->getResponserModelName());
        $this->processingRunner->setResponserParams($this->getResponserParams());

        return $this->processingRunner;
    }

    protected function getResponser()
    {
        if (!is_null($this->responser)) {
            return $this->responser;
        }

        return $this->responser = $this->modelFactory->getObject($this->getResponserModelName(), array(
            'params' => $this->getResponserParams(),
            'response' => $this->getResponse()
        ));
    }

    // ########################################

    public function process()
    {
        $this->getConnection()->process();

        $this->eventBeforeExecuting();

        $responseData = $this->getResponse()->getResponseData();
        if (isset($responseData['processing_id'])) {
            $this->processingServerHash = $responseData['processing_id'];
            $this->getProcessingRunner()->start();

            return;
        }

        $this->processResponser();

        $this->preparedResponseData = $this->getResponser()->getPreparedResponseData();
    }

    // -----------------------------------------

    protected function processResponser()
    {
        try {
            $this->getResponser()->process();
            $this->getResponser()->eventAfterExecuting();
        } catch (\Exception $exception) {
            $this->getResponser()->failDetected($exception->getMessage());
        }
    }

    // ########################################

    public function getPreparedResponseData()
    {
        return $this->preparedResponseData;
    }

    // ########################################

    public function eventBeforeExecuting() {}

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Connector\Command\Pending\Processing\Runner\Single';
    }

    protected function getProcessingParams()
    {
        return array(
            'component'   => $this->getProtocol()->getComponent(),
            'server_hash' => $this->processingServerHash,
        );
    }

    // -----------------------------------------

    protected function getResponserModelName()
    {
        $responserModelName = preg_replace('/Requester$/', '', get_class($this)).'Responser';
        $responserModelName = str_replace('Ess\M2ePro\Model\\', '', $responserModelName);

        return $responserModelName;
    }

    protected function getResponserParams()
    {
        return $this->params;
    }

    // ########################################
}