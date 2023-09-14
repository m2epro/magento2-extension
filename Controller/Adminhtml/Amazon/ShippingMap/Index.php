<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\ShippingMap;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\ShippingMap
{
    public function execute()
    {
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\ShippingMap\Main::class));
        $this->getResult()->getConfig()->getTitle()->prepend($this->__('Shipping Mapping'));

        return $this->getResult();
    }
}
