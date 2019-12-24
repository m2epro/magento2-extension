<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Category\Get;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Category\Get\Suggested
 */
class Suggested extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    // ########################################

    protected function getCommand()
    {
        return ['category', 'get', 'suggested'];
    }

    protected function getRequestData()
    {
        return [
            'query' => $this->params['query']
        ];
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
