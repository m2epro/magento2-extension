<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

class GetFailedProductsGrid extends \Ess\M2ePro\Controller\Adminhtml\Listing\Moving
{
    //########################################

    public function execute()
    {
        $block = $this->createBlock('Listing\Moving\FailedProducts\Grid');

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}