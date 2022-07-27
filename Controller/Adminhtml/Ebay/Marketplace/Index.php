<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

class Index extends Marketplace
{
    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Marketplace::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Marketplaces'));
        $this->setPageHelpLink('x/CP8UB');

        return $this->getResult();
    }
}
