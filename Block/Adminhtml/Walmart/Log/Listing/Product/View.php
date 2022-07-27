<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Log\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractView;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Log\Listing\Product\View
 */
class View extends AbstractView
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
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($sessionDataHelper, $context, $data);
    }

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Account\Switcher::class)
                                 ->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Marketplace\Switcher::class)
                                 ->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function _toHtml()
    {
        $message = <<<TEXT
This Log contains information about the actions applied to M2E Pro Listings and related Items.<br/><br/>
Find detailed info in <a href="%url%" target="_blank">the article</a>.
TEXT;
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData([
            'content' => $this->__(
                $message,
                $this->supportHelper->getDocumentationArticleUrl('x/gv1IB#Logs&Events-M2EProListinglogs')
            ),
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
