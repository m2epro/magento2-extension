<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Log\Order
 */
class Order extends \Ess\M2ePro\Block\Adminhtml\Log\Order\AbstractContainer
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->createBlock('Walmart_Account_Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->createBlock('Walmart_Marketplace_Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    //########################################

    protected function _toHtml()
    {
        $supportHelper = $this->helperFactory->getObject('Module_Support');
        if ($this->getRequest()->getParam('magento_order_failed')) {
            $message = <<<TEXT
This Log contains information about your recent Walmart orders for which Magento orders were not created.<br/><br/>
Find detailed info in <a href="%url%" target="_blank">the article</a>.
TEXT;
        } else {
            $message = <<<TEXT
This Log contains information about Order processing.<br/><br/>
Find detailed info in <a href="%url%" target="_blank">the article</a>.
TEXT;
        }
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                $message,
                $supportHelper->getDocumentationArticleUrl('x/WgBhAQ#Logs&Events-Orderlogs')
            )
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
