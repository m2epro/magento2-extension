<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\ItemsRequester
{
    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Synchronization_ListingsProducts_Update_ProcessingRunner';
    }

    protected function getResponserParams()
    {
        return array_merge(
            parent::getResponserParams(),
            [
                'request_date' => $this->getHelper('Data')->getCurrentGmtDate(),
            ]
        );
    }

    // ########################################
}
