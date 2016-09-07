<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending;

abstract class Responser extends \Ess\M2ePro\Model\AbstractModel
{
    protected $params = array();

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response $response */
    protected $response = NULL;

    protected $preparedResponseData = array();

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
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
            return NULL;
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

    public function failDetected($messageText) {}

    public function eventAfterExecuting() {}

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

    protected function processResponseMessages() {}

    // ########################################
}