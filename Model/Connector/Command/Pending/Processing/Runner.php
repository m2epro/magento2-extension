<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Processing;

abstract class Runner extends \Ess\M2ePro\Model\Processing\Runner
{
    const PENDING_REQUEST_MAX_LIFE_TIME = 43200;

    private $responserModelName = NULL;

    private $responserParams = array();

    /** @var \Ess\M2ePro\Model\Connector\Command\Pending\Responser $responser */
    protected $responser = NULL;

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response $response */
    protected $response = NULL;

    // ##################################

    public function setProcessingObject(\Ess\M2ePro\Model\Processing $processingObjectObject)
    {
        $result = parent::setProcessingObject($processingObjectObject);

        $this->setResponserModelName($this->params['responser_model_name']);
        $this->setResponserParams($this->params['responser_params']);

        return $result;
    }

    // ----------------------------------

    public function getParams()
    {
        $params = parent::getParams();

        $params['responser_model_name'] = $this->getResponserModelName();
        $params['responser_params']     = $this->getResponserParams();

        return $params;
    }

    // ----------------------------------

    public function setResponserModelName($modelName)
    {
        $this->responserModelName = $modelName;
        return $this;
    }

    public function getResponserModelName()
    {
        return $this->responserModelName;
    }

    // ----------------------------------

    public function setResponserParams(array $params)
    {
        $this->responserParams = $params;
        return $this;
    }

    public function getResponserParams()
    {
        return $this->responserParams;
    }

    // ##################################

    protected function eventAfter()
    {
        parent::eventAfter();

        try {
            $this->getResponser()->eventAfterExecuting();
        } catch (\Exception $exception) {
            $this->getResponser()->failDetected($exception->getMessage());
        }
    }

    // ##################################

    protected function getResponser($returnNewObject = false)
    {
        if (!is_null($this->responser) && !$returnNewObject) {
            return $this->responser;
        }

        return $this->responser = $this->modelFactory->getObject($this->getResponserModelName(), array(
            'params' => $this->getResponserParams(),
            'response' => $this->getResponse()
        ));
    }

    protected function getResponse()
    {
        if (!is_null($this->response)) {
            return $this->response;
        }

        $this->response = $this->modelFactory->getObject('Connector\Connection\Response');
        $this->response->initFromPreparedResponse(
            $this->getProcessingObject()->getResultData(), $this->getProcessingObject()->getResultMessages()
        );

        $params = $this->getParams();
        if (!empty($params['request_time'])) {
            $this->response->setRequestTime($params['request_time']);
        }

        return $this->response;
    }

    // ##################################
}