<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

class GetEditIdentifiersPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Walmart\Listing\View\Walmart\Identifiers\Main')
        );

        return $this->getResult();
    }
}