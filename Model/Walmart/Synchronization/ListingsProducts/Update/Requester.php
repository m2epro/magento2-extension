<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\ListingsProducts\Update;

class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Inventory\Get\ItemsRequester
{
    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart\Synchronization\ListingsProducts\Update\ProcessingRunner';
    }

    protected function getResponserParams()
    {
        return array_merge(
            parent::getResponserParams(),
            array(
                'request_date' => $this->getHelper('Data')->getCurrentGmtDate(),
            )
        );
    }

    // ########################################
}