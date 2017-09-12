<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class SynchGetExecutingInfo extends General
{
    //########################################

    public function execute()
    {
        $response = array();
        $lockItem = $this->modelFactory->getObject('Synchronization\Lock\Item\Manager');

        if (!$lockItem->isExist()) {
            $response['mode'] = 'inactive';
        } else {
            $response['mode'] = 'executing';
            $response['title'] = $lockItem->getTitle();
            $response['percents'] = $lockItem->getPercents();
            $response['status'] = $lockItem->getStatus();
        }

        $this->setJsonContent($response);

        return $this->getResult();
    }

    //########################################
}