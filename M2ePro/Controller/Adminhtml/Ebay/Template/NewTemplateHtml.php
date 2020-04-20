<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\NewTemplateHtml
 */
class NewTemplateHtml extends Template
{
    //########################################

    public function execute()
    {
        $nick = $this->getRequest()->getParam('nick');

        $this->setAjaxContent($this->createBlock('Ebay_Listing_Template_NewTemplate_Form')->setData('nick', $nick));

        return $this->getResult();
    }

    //########################################
}
