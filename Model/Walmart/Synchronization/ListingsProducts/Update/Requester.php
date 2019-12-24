<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update\Requester
 */
class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Inventory\Get\ItemsRequester
{
    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart_Synchronization_ListingsProducts_Update_ProcessingRunner';
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
