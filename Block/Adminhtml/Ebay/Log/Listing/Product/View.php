<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Log\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractView;

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
        return \Ess\M2ePro\Helper\View\Ebay::NICK;
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
                $this->supportHelper->getDocumentationArticleUrl('x/85BCB#Logs&Events-M2EProListinglogs')
            ),
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
