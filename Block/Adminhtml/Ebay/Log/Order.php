<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log;

class Order extends \Ess\M2ePro\Block\Adminhtml\Log\Order\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->getParam('magento_order_failed')) {
            $message = <<<TEXT
This Log contains information about your recent eBay orders for which Magento orders were not created.<br/><br/>
Find detailed info in <a href="%url%" target="_blank">the article</a>.
TEXT;
        } else {
            $message = <<<TEXT
This Log contains information about Order processing.<br/><br/>
Find detailed info in <a href="%url%" target="_blank">the article</a>.
TEXT;
        }
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $this->__(
                $message,
                $this->supportHelper->getDocumentationArticleUrl('x/85BCB#Logs&Events-Orderlogs')
            ),
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
