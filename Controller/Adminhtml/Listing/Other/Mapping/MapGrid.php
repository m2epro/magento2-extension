<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;

class MapGrid extends Listing
{
    public function execute()
    {
        $block = $this->getLayout()->createBlock(
            'Ess\M2ePro\Block\Adminhtml\Listing\Other\Mapping\Grid'
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}