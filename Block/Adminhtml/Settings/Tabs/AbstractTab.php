<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

abstract class AbstractTab extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    protected function getGlobalNotice()
    {
        $activeComponents = $this->getHelper('Component')->getEnabledComponents();

        if (count($activeComponents) == 1) {
            return '';
        }

        $content = $this->__("These settings are global for all the Integrations you are using.");
        return '<div id="global_messages" style="overflow: hidden; padding-right: 20px;">
                   <div class="messages"><div class="message">'
                . $content
                . '</div></div></div>';
    }

    //########################################

    protected function _toHtml()
    {
        return $this->getGlobalNotice() . parent::_toHtml();
    }

    //########################################
}