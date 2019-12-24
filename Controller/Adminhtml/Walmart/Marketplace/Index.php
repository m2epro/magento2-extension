<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace\Index
 */
class Index extends Marketplace
{
    //########################################

    public function execute()
    {
        $this->addContent($this->createBlock('Walmart\Marketplace'));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Marketplaces'));
        $this->setPageHelpLink('x/JQBhAQ');

        return $this->getResult();
    }

    //########################################
}
