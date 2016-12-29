<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders\Update;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Orders\Update\ItemsRequester
{
    // ########################################

    protected function getResponserParams()
    {
        $params = array();

        foreach ($this->params['items'] as $orderUpdate) {
            if (!is_array($orderUpdate)) {
                continue;
            }

            $params[$orderUpdate['change_id']] = $orderUpdate;
        }

        return $params;
    }

    // ########################################

    public function eventBeforeExecuting()
    {
        parent::eventBeforeExecuting();

        $changeIds = array();

        foreach ($this->params['items'] as $orderUpdate) {
            if (!is_array($orderUpdate)) {
                continue;
            }

            $changeIds[] = $orderUpdate['change_id'];
        }

        $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByIds($changeIds);
    }

    // ########################################
}