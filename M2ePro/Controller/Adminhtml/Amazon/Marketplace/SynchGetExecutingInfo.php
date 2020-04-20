<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\SynchGetExecutingInfo
 */
class SynchGetExecutingInfo extends Marketplace
{
    //########################################

    public function execute()
    {
        $response = [];

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK
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
