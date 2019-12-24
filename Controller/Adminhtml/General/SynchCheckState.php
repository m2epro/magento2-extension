<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\SynchCheckState
 */
class SynchCheckState extends General
{
    //########################################

    public function execute()
    {
        $lockItem = $this->modelFactory->getObject('Synchronization_Lock_Item_Manager');

        if ($lockItem->isExist()) {
            $this->setAjaxContent('executing', false);
        } else {
            $this->setAjaxContent('inactive', false);
        }

        return $this->getResult();
    }

    //########################################
}
