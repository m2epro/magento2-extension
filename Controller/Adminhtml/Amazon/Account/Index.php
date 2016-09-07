<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

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