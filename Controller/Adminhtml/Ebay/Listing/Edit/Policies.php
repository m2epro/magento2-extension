<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class Policies extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit
{
    public function execute()
    {
        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Tab\Policies::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));

        return $this->getResult();
    }
}
