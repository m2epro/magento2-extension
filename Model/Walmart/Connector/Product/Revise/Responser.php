<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Revise;

/**
 * Class Responser
 * @package Ess\M2ePro\Model\Walmart\Connector\Product\Revise
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    // ########################################

    protected function getSuccessfulMessage()
    {
        return $this->getResponseObject()->getSuccessfulMessage();
    }

    // ########################################
}
