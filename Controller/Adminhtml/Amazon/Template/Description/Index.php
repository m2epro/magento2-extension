<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\Index
 */
class Index extends Description
{
    //########################################

    public function execute()
    {
        return $this->_redirect('*/amazon_template/index');
    }

    //########################################
}
