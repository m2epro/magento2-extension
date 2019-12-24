<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\PageHelpLink
 */
class PageHelpLink extends AbstractBlock
{
    protected $_template = 'page_help_link.phtml';

    protected function _toHtml()
    {
        if ($this->getPageHelpLink() === null) {
            return '';
        }

        return parent::_toHtml();
    }
}
