<?php

namespace Ess\M2ePro\Block\Adminhtml\Renderer;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class Description extends AbstractBlock
{
    protected function _construct()
    {
        parent::_construct();
        $this->setData('area', 'adminhtml');
    }
}