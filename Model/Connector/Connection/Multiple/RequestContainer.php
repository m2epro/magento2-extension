<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection\Multiple;

class RequestContainer extends \Ess\M2ePro\Model\AbstractModel
{
    private $request = NULL;
    private $timeout = NULL;

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Request|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Request $request
     * @return $this
     */
    public function setRequest(\Ess\M2ePro\Model\Connector\Connection\Request $request)
    {
        $this->request = $request;
        return $this;
    }

    // ########################################

    /**
     * @return null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    // ########################################
}