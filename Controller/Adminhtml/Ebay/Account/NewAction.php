<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class NewAction extends Account
{
    public function execute()
    {
        $this->_forward('edit');
    }
}