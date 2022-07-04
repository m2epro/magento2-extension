<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class Index extends Account
{
    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Accounts'));
        $this->setPageHelpLink('x/hv1IB');

        return $this->getResultPage();
    }
}
