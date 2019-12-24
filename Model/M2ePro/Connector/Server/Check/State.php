<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\M2ePro\Connector\Server\Check;

/**
 * Class \Ess\M2ePro\Model\M2ePro\Connector\Server\Check\State
 */
class State extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    // ########################################

    protected function getCommand()
    {
        return ['server', 'check', 'state'];
    }

    protected function getRequestData()
    {
        return [];
    }

    protected function validateResponse()
    {
        return true;
    }

    // ########################################

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout(30)
            ->setServerBaseUrl($this->params['base_url'])
            ->setServerHostName($this->params['hostname'])
            ->setTryToSwitchEndpointOnError(false)
            ->setTryToResendOnError(false);

        return $connection;
    }

    // ########################################
}
