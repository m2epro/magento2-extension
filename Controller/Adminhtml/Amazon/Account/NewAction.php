<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class NewAction extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    public function execute()
    {
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Add Account')
        );

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Create::class)
        );
        $this->setPageHelpLink('accounts');

        return $this->getResultPage();
    }
}
