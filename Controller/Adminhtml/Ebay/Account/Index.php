<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Index
 */
class Index extends Account
{
    public function execute()
    {
        $this->addContent($this->createBlock('Ebay\Account'));
        $this->setPageHelpLink('x/4gEtAQ');
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Accounts'));

        return $this->getResultPage();
    }
}
