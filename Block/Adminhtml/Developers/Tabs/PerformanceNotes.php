<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PerformanceNotes extends AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->supportHelper = $supportHelper;
    }

    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\HelpBlock::class,
            '',
            ['data' => [
            'no_collapse' => true,
            'no_hide' => true,
            'content' => $this->__(
                <<<HTML
Find useful tips on how to optimize your Module work in <a target="_blank" href="%url%">this article</a>.
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('x/PX39')
            )
            ]]
        );
        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
