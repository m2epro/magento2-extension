<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Index
 */
class Index extends Template
{
    //########################################

    public function execute()
    {
        $content = $this->createBlock(
            'Amazon\\Template'
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend('Policies');
        $this->addContent($content);
        $this->setPageHelpLink('x/8gEtAQ');

        return $this->getResultPage();
    }

    //########################################
}
