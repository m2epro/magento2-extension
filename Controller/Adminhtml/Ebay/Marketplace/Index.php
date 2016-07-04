<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

class Index extends Marketplace
{
    //########################################

    public function execute()
    {
        $this->addContent($this->createBlock('Ebay\Marketplace'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Marketplaces'));
        $this->setComponentPageHelpLink('Marketplaces');

        return $this->getResult();
    }

    //########################################
}