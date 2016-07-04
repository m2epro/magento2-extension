<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class Index extends Template
{
    //########################################

    public function execute()
    {
        $content = $this->getLayout()->createBlock(
            'Ess\\M2ePro\\Block\\Adminhtml\\Ebay\\Template'
        );

        $this->getResult()->getConfig()->getTitle()->prepend('Policies');
        $this->addContent($content);
        $this->setComponentPageHelpLink('Policies');

        return $this->getResult();
    }

    //########################################
}