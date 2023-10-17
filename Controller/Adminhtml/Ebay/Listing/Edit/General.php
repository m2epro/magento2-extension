<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class General extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit
{
    public function execute()
    {
        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab\General::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('General'));

        return $this->getResult();
    }
}
