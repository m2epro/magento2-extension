<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending;

/**
 * Class \Ess\M2ePro\Model\Connector\Command\Pending\Responser
 */
abstract class Responser extends \Ess\M2ePro\Model\AbstractModel
{
    protected $params = [];

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response $response */
    protected $response = null;

    protected $preparedResponseData = [];

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->params   = $params;
        $this->response = $response;
        parent::__construct($helperFactory, $modelFactory);
    }

    // ########################################

    protected function getResponse()
    {
        return $this->response;
    }

    // ########################################

    public function process()
    {
        $this->processResponseMessages();

        if (!$this->isNeedProcessResponse()) {
            return null;
        }

        if (!$this->validateResponse()) {
            throw new \Ess\M2ePro\Model\Exception('Validation Failed. The Server response data is not valid.');
        }

        $this->prepareResponseData();
        $this->processResponseData();

        return $this->getPreparedResponseData();
    }

    // ########################################

    public function getPreparedResponseData()
    {
        return $this->preparedResponseData;
    }

    // ########################################

    public function failDetected($messageText)
    {
        return null;
    }

    public function eventAfterExecuting()
    {
        return null;
    }

    //-----------------------------------------

    protected function isNeedProcessResponse()
    {
        return true;
    }

    abstract protected function validateResponse();

    protected function prepareResponseData()
    {
        $this->preparedResponseData = $this->getResponse()->getResponseData();
    }

    abstract protected function processResponseData();

    // ########################################

    protected function processResponseMessages()
    {
        return null;
    }

    // ########################################
}
