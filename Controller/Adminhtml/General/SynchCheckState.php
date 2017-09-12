<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\General;

class SynchCheckState extends General
{
    //########################################

    public function execute()
    {
        $lockItem = $this->modelFactory->getObject('Synchronization\Lock\Item\Manager');

        if ($lockItem->isExist()) {
            $this->setAjaxContent('executing', false);
        } else {
            $this->setAjaxContent('inactive', false);
        }

        return $this->getResult();
    }

    //########################################
}