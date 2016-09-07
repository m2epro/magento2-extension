<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class PreviewItems extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected function getLayoutType()
    {
        return self::LAYOUT_BLANK;
    }

    public function execute()
    {
        $this->addContent(
            $this->createBlock('Ebay\Listing\Preview')
        );

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('Preview Items')
        );

        return $this->getResult();
    }
}