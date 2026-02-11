<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Repricer;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Index extends Template
{
    public function execute()
    {
        return $this->_redirect('*/walmart_template/index');
    }
}
