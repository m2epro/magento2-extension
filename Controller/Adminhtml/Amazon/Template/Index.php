<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Index extends Template
{
    //########################################

    public function execute()
    {
        $content = $this->getLayout()->createBlock(
            'Ess\\M2ePro\\Block\\Adminhtml\\Amazon\\Template'
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend('Policies');
        $this->addContent($content);
        $this->setComponentPageHelpLink('Policies');

        return $this->getResultPage();
    }

    //########################################
}