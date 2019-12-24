<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command;

/**
 * Class \Ess\M2ePro\Model\Connector\Command\RealTime
 */
abstract class RealTime extends \Ess\M2ePro\Model\Connector\Command\AbstractModel
{
    // ########################################

    protected $responseData = null;

    // ########################################

    public function process()
    {
        $this->getConnection()->process();

        if (!$this->validateResponse()) {
            throw new \Ess\M2ePro\Model\Exception('Validation Failed. The Server response data is not valid.');
        }

        $this->prepareResponseData();
    }

    // ########################################

    protected function validateResponse()
    {
        return true;
    }

    protected function prepareResponseData()
    {
        $this->responseData = $this->getResponse()->getResponseData();
    }

    // ########################################

    public function getResponseData()
    {
        return $this->responseData;
    }

    public function getResponseMessages()
    {
        return $this->getResponse()->getMessages()->getEntitiesAsArrays();
    }

    // ########################################
}
