<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

class Index extends Settings
{
    //########################################

    public function execute()
    {
        // todo Remove when Mageto fix Horizontal Tabs bug
        if ($this->getRequest()->isXmlHttpRequest()) {
            $block = $this->createBlock('Amazon\Synchronization')->toHtml();
            $this->setAjaxContent($block);

            return $this->getResult();
        }

        $block = $this->createBlock('Synchronization\Tabs');
        $block->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Amazon\Synchronization\Tabs::TAB_ID_GENERAL);

        $this->addContent($block);

        $this->resultPage->getConfig()->getTitle()->prepend($this->__('Synchronization'));
        $this->resultPage->getConfig()->getTitle()->prepend($this->__('General'));

        return $this->resultPage;
    }

    //########################################
}