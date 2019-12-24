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
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Revise\Responser
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
