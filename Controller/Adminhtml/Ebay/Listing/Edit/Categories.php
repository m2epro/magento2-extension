<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class Categories extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit
{
    public function execute()
    {
        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab\Categories::class)
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Categories'));

        return $this->getResult();
    }
}
