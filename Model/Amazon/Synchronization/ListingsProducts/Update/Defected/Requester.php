<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update\Defected;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update\Defected\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected\ItemsRequester
{
    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Synchronization_ListingsProducts_Update_Defected_ProcessingRunner';
    }

    //########################################
}
