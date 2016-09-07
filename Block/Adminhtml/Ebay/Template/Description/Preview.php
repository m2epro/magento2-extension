<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Preview extends AbstractBlock
{
    protected $_template = 'ebay/template/description/preview.phtml';

    protected function _construct()
    {
        parent::_construct();

        $this->css->addFile('ebay/template.css');
    }
}