<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

abstract class Account extends Main
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_configuration_accounts');
    }
}
