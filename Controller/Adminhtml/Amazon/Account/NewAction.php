<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class NewAction extends Account
{
    public function execute()
    {
        $this->_forward('edit');
    }
}