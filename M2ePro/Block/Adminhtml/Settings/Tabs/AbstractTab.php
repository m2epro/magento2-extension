<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Settings\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
 */
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
                   <div class="messages m2epro-messages"><div class="message">'
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
