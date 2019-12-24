<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection\Multiple;

/**
 * Class \Ess\M2ePro\Model\Connector\Connection\Multiple\RequestContainer
 */
class RequestContainer extends \Ess\M2ePro\Model\AbstractModel
{
    private $request = null;
    private $timeout = null;

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
