<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

class RunSynchNow extends Marketplace
{
    //########################################

    public function execute()
    {
        session_write_close();

        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        $marketplaceObj = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        /** @var $dispatcher \Ess\M2ePro\Model\Synchronization\Dispatcher */
        $dispatcher = $this->modelFactory->getObject('Synchronization\Dispatcher');

        $dispatcher->setAllowedComponents(array($marketplaceObj->getComponentMode()));
        $dispatcher->setAllowedTasksTypes(array(
            \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES
        ));

        $dispatcher->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $dispatcher->setParams(array('marketplace_id' => $marketplaceId));

        $dispatcher->process();
    }

    //########################################
}