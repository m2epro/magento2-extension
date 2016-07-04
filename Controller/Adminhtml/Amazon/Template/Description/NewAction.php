<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class NewAction extends Description
{
    //########################################

    public function execute()
    {
        $this->_forward('edit');
    }

    //########################################
}