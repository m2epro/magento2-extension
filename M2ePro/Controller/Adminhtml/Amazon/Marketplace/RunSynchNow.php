<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\RunSynchNow
 */
class RunSynchNow extends Marketplace
{
    //########################################

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK
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

        /** @var \Ess\M2ePro\Model\Amazon\Marketplace\Synchronization $synchronization */
        $synchronization = $this->modelFactory->getObject('Amazon_Marketplace_Synchronization');
        $synchronization->setMarketplace($marketplace);
        $synchronization->setProgressManager($progressManager);

        $synchronization->process();

        $lockItemManager->remove();
    }

    //########################################
}
