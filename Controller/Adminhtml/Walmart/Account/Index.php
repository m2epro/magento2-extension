<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Index
 */
class Index extends Account
{
    public function execute()
    {
        $this->addContent($this->createBlock('Walmart\Account'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Accounts'));
        $this->setPageHelpLink('x/XgBhAQ');

        return $this->getResultPage();
    }
}
