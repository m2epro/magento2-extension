<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;

class GetFailedProductsGrid extends Listing
{
    public function execute()
    {
        $block = $this->createBlock('Listing\Moving\FailedProducts\Grid');

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}