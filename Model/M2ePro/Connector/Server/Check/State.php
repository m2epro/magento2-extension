<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\M2ePro\Connector\Server\Check;

class State extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    // ########################################

    protected function getCommand()
    {
        return array('server', 'check', 'state');
    }

    protected function getRequestData()
    {
        return array();
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