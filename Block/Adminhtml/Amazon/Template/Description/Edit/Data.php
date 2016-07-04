<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Data extends AbstractBlock
{
    protected $_template = 'template/2_column.phtml';

    protected function _prepareLayout()
    {
        $this->setChild('tabs', $this->createBlock('Amazon\Template\Description\Edit\Tabs'));
        return parent::_prepareLayout();
    }
}