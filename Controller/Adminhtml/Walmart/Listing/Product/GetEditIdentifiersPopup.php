<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

class GetEditIdentifiersPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Identifiers\Form::class)
        );

        return $this->getResult();
    }
}
