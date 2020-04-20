<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Index
 */
class Index extends Marketplace
{
    //########################################

    public function execute()
    {
        $this->addContent($this->createBlock('Amazon\Marketplace'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Marketplaces'));
        $this->setPageHelpLink('x/9AEtAQ');

        return $this->getResult();
    }

    //########################################
}
