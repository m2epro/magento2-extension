<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Delete;

class EntityResponser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
{
    // ########################################

    protected function validateResponse()
    {
        return true;
    }

    protected function processResponseData() {}

    // ########################################
}