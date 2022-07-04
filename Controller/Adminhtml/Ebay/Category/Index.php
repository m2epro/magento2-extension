<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    //########################################

    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Category::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Categories'));
        $this->setPageHelpLink('x/c4FkB');

        return $this->getResult();
    }

    //########################################
}
