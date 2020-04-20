<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace\SynchGetExecutingInfo
 */
class SynchGetExecutingInfo extends Marketplace
{
    //########################################

    public function execute()
    {
        $response = [];

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItemManager */
        $lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK
        ]);

        if (!$lockItemManager->isExist()) {
            $response['mode'] = 'inactive';
        } else {
            $response['mode'] = 'executing';

            $contentData = $lockItemManager->getContentData();
            $progressData = $contentData[\Ess\M2ePro\Model\Lock\Item\Progress::CONTENT_DATA_KEY];

            if (!empty($progressData)) {
                $response['title'] = 'eBay Sites Synchronization';
                $response['percents'] = $progressData[key($progressData)]['percentage'];
                $response['status'] = key($progressData);
            }
        }

        $this->setJsonContent($response);
        return $this->getResult();
    }

    //########################################
}
