<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Store\Category;

class GetTree extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{

    //########################################

    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account_id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->ebayFactory->getObjectLoaded('Account', $accountId);

        $categoriesTreeArray = $account->getChildObject()->buildEbayStoreCategoriesTree();

        $this->setJsonContent($categoriesTreeArray);

        return $this->getResult();
    }

    //########################################
}
