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
        /** @var \Ess\M2ePro\Model\Ebay\Marketplace\Synchronization $synchronization */
        $synchronization = $this->modelFactory->getObject('Ebay_Marketplace_Synchronization');
        if (!$synchronization->isLocked()) {
            $this->setJsonContent(['mode' => 'inactive']);
            return $this->getResult();
        }

        $contentData = $synchronization->getLockItemManager()->getContentData();
        $progressData = $contentData[\Ess\M2ePro\Model\Lock\Item\Progress::CONTENT_DATA_KEY];

        $response = ['mode' => 'executing'];

        if (!empty($progressData)) {
            $response['title'] = 'Marketplaces Synchronization';
            $response['percents'] = $progressData[key($progressData)]['percentage'];
            $response['status'] = key($progressData);
        }

        $this->setJsonContent($response);
        return $this->getResult();
    }

    //########################################
}
