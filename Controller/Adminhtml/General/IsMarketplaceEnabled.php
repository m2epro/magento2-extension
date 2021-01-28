<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\General;

use Ess\M2ePro\Controller\Adminhtml\Base;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\General\IsMarketplaceEnabled
 */
class IsMarketplaceEnabled extends Base
{
    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        if ($marketplaceId === null) {
            $this->setAjaxContent('Marketplace ID is not specified.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Marketplace $marketplaceObj */
        $marketplaceObj = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            $marketplaceId
        );

        $this->setJsonContent(
            [
                'status' => $marketplaceObj->isStatusEnabled() &&
                    $marketplaceObj->getResource()->isDictionaryExist($marketplaceObj)
            ]
        );

        return $this->getResult();
    }

    //########################################
}
