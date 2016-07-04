<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization\Log;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Settings
{
    //########################################

    public function execute()
    {
        return $this->_redirect('*/developers/index',
            ['active_tab' => \Ess\M2ePro\Block\Adminhtml\Developers\Tabs::TAB_ID_SYNCHRONIZATION_LOG]
        );
    }

    //########################################
}