<?php

namespace Ess\M2ePro\Block\Adminhtml;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PageHelpLink extends AbstractBlock
{
    protected $_template = 'page_help_link.phtml';

    protected function _toHtml()
    {
        if (is_null($this->getPageHelpLink())) {
            return '';
        }

        return parent::_toHtml();
    }
}