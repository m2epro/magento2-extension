<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Update;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Account\Update\EntityRequester
 */
class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    //########################################

    /**
     * @return array
     */
    protected function getRequestData()
    {
        return $this->params;
    }

    /**
     * @return array
     */
    protected function getCommand()
    {
        return ['account','update','entity'];
    }

    //########################################

    /**
     * @return bool
     */
    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['info']) && !$this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function processResponseData()
    {
        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            throw new \Exception($message->getText());
        }

        $this->responseData = $this->getResponse()->getResponseData();
    }

    //########################################
}
