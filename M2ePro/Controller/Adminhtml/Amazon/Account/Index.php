<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Index
 */
class Index extends Account
{
    public function execute()
    {
        $this->addContent($this->createBlock('Amazon\Account'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Accounts'));
        $this->setPageHelpLink('x/9gEtAQ');

        return $this->getResultPage();
    }
}
