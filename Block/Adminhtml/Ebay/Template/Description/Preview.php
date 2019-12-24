<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Preview
 */
class Preview extends AbstractBlock
{
    protected $_template = 'ebay/template/description/preview.phtml';

    protected function _construct()
    {
        parent::_construct();

        $this->css->addFile('ebay/template.css');
    }
}
