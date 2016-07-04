<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class NewAction extends Template
{
    public function execute()
    {
        $this->_forward('edit');
    }
}