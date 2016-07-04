<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetAddSpecificsGridHtml extends Description
{
    //########################################

    public function execute()
    {
        $gridBlock = $this->prepareGridBlock();
        $this->setAjaxContent($gridBlock->toHtml());
        return $this->getResult();
    }

    //########################################
}