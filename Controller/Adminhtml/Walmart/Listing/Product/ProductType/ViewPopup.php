<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\ProductType;

class ViewPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\AbstractAdd
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\ProductType::class)
        );

        return $this->getResult();
    }
}
