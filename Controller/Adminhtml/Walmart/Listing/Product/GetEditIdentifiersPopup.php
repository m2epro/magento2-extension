<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

class GetEditIdentifiersPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart\Listing\Product\Identifiers\Form')
        );

        return $this->getResult();
    }
}