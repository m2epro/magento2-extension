<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Settings;

class Index extends Settings
{
    //########################################

    public function execute()
    {
        $block = $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\Ebay\Synchronization\Tabs');
        $block->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Ebay\Synchronization\Tabs::TAB_ID_GENERAL);

        $this->addContent($block);

        $this->resultPage->getConfig()->getTitle()->prepend($this->__('Synchronization'));
        $this->resultPage->getConfig()->getTitle()->prepend($this->__('General'));

        return $this->resultPage;
    }

    //########################################
}