<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

abstract class ShippingMap extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_shipping_mapping');
    }
}
