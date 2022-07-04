<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

class Index extends Marketplace
{
    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Marketplace::class));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Marketplaces'));
        $this->setPageHelpLink('x/Tf1IB');

        return $this->getResult();
    }
}
