<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace\Index
 */
class Index extends Marketplace
{
    //########################################

    public function execute()
    {
        $this->addContent($this->createBlock('Ebay\Marketplace'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Marketplaces'));
        $this->setPageHelpLink('x/4AEtAQ');

        return $this->getResult();
    }

    //########################################
}
