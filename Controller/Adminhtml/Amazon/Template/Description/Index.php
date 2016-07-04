<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class Index extends Description
{
    //########################################

    public function execute()
    {
        return $this->_redirect('*/amazon_template/index');
    }

    //########################################
}