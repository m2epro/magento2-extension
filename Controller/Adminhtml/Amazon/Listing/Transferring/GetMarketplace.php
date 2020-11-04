<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Transferring;

class GetMarketplace extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    //########################################

    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account_id');
        if (empty($accountId)) {
            return $this->getResponse()->setBody($this->getHelper('Data')->jsonEncode([
                'id'    => null,
                'title' => null
            ]));
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId);
        return $this->getResponse()->setBody($this->getHelper('Data')->jsonEncode([
            'id' => $account->getChildObject()->getMarketplace()->getId(),
            'title' => $account->getChildObject()->getMarketplace()->getTitle()
        ]));
    }

    //########################################
}
