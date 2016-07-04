<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Category\Get;

class Suggested extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    // ########################################

    protected function getCommand()
    {
        return array('category', 'get', 'suggested');
    }

    protected function getRequestData()
    {
        return array(
            'query' => $this->params['query']
        );
    }

    protected function validateResponse()
    {
        return true;
    }

    protected function prepareResponseData()
    {
        if ($this->getResponse()->isResultError()) {
            return;
        }

        $this->responseData = $this->getResponse()->getResponseData();
    }

    // ########################################

    protected function buildConnectionInstance()
    {
        $connection = parent::buildConnectionInstance();
        $connection->setTimeout(30);

        return $connection;
    }

    // ########################################
}