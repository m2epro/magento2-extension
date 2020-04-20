<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace\SynchGetExecutingInfo
 */
class SynchGetExecutingInfo extends Marketplace
{
    //########################################

    public function execute()
    {
        $response = [];

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK
        ]);

        if (!$lockItemManager->isExist()) {
            $response['mode'] = 'inactive';
        } else {
            $response['mode'] = 'executing';

            $contentData = $lockItemManager->getContentData();
            $progressData = $contentData[\Ess\M2ePro\Model\Lock\Item\Progress::CONTENT_DATA_KEY];

            if (!empty($progressData)) {
                $response['title'] = 'Marketplace Synchronization';
                $response['percents'] = $progressData[key($progressData)]['percentage'];
                $response['status'] = key($progressData);
            }
        }

        return $this->getResponse()->setBody($this->getHelper('Data')->jsonEncode($response));
    }

    //########################################
}
