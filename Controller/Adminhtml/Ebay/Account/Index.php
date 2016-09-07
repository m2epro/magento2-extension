<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

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