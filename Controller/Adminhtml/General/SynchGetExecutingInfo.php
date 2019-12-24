<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\SynchGetExecutingInfo
 */
class SynchGetExecutingInfo extends General
{
    //########################################

    public function execute()
    {
        $response = [];
        $lockItem = $this->modelFactory->getObject('Synchronization_Lock_Item_Manager');

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
