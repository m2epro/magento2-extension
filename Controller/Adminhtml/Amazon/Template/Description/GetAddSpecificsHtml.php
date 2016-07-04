<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetAddSpecificsHtml extends Description
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Specific\Add $addBlock */
        $addBlock = $this->createBlock('Amazon\Template\Description\Category\Specific\Add');

        $gridBlock = $this->prepareGridBlock();
        $addBlock->setChild('specifics_grid', $gridBlock);

        $this->setAjaxContent($addBlock->toHtml());
        return $this->getResult();
    }

    //########################################
}