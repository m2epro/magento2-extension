<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetSkuPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Amazon\Listing\Product\Variation\Manage\Tabs\Settings\SkuPopup\Form')
        );

        return $this->getResult();
    }
}