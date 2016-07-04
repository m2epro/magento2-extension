<?php
namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class NewTemplateHtml extends Template
{
    //########################################

    public function execute()
    {
        $nick = $this->getRequest()->getParam('nick');

        $this->setAjaxContent($this->createBlock('Ebay\Listing\Template\NewTemplate\Form')->setData('nick', $nick));
        
        return $this->getResult();
    }

    //########################################
}