<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs\PerformanceNotes
 */
class PerformanceNotes extends AbstractBlock
{
    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'no_collapse' => true,
            'no_hide' => true,
            'content' => $this->__(
                <<<HTML
Find useful tips on how to optimize your Module work in <a target="_blank" href="%url">this article</a>.
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/z4QVAQ')
            )
        ]]);
        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
