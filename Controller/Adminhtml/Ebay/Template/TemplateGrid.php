<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class TemplateGrid extends Template
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Grid $switcherBlock */
        $grid = $this->getLayout()->createBlock(
            'Ess\\M2ePro\\Block\\Adminhtml\\Ebay\\Template\\Grid'
        );

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}