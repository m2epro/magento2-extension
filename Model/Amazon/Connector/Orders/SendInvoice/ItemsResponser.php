<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\SendInvoice;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\SendInvoice\ItemsResponser
 */
abstract class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    //########################################

    protected function validateResponse()
    {
        return true;
    }

    //########################################
}
