<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationFromMagento1\RunSynchNow
 */
class RunSynchNow extends MigrationFromMagento1
{
    //########################################

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        $component = $this->getRequest()->getParam('component');
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => $this->getLockItemNick()
        ]);

        if ($lockItemManager->isExist()) {
            return;
        }

        $lockItemManager->create();

        /** @var \Ess\M2ePro\Model\Lock\Item\Progress $progressManager */
        $progressManager = $this->modelFactory->getObject('Lock_Item_Progress', [
            'lockItemManager' => $lockItemManager,
            'progressNick'    => $marketplace->getTitle() . ' Marketplace'
        ]);

        $component= ucfirst(strtolower($component));
        $synchronization = $this->modelFactory->getObject($component . '_Marketplace_Synchronization');
        $synchronization->setMarketplace($marketplace);
        $synchronization->setProgressManager($progressManager);

        $synchronization->process();

        $lockItemManager->remove();
    }

    //########################################
}
