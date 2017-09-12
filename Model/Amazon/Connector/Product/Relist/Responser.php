<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Relist;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    // ########################################

    protected function getSuccessfulMessage()
    {
        return $this->getResponseObject()->getSuccessfulMessage();
    }

    // ########################################
}