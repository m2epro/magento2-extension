<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Inventory\Get;

abstract class ItemsRequester extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Requester
{
    // ########################################

    protected function getRequestData()
    {
        return array();
    }

    public function getCommand()
    {
        return array('inventory','get','items');
    }

    // ########################################

    protected function getResponserParams()
    {
        return array(
            'account_id'     => $this->account->getId(),
            'marketplace_id' => $this->marketplace->getId()
        );
    }

    // ########################################
}