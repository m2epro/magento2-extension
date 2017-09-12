<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductTaxCode;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class NewAction extends Template
{
    public function execute()
    {
        $this->_forward('edit');
    }
}