<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Data extends AbstractBlock
{
    protected $_template = 'template/2_column.phtml';

    protected function _prepareLayout()
    {
        $this->setChild('tabs', $this->createBlock('Amazon\Template\Synchronization\Edit\Tabs'));
        return parent::_prepareLayout();
    }
}