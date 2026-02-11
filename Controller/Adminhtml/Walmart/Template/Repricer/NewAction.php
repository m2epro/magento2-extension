<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class NewAction extends Template
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
