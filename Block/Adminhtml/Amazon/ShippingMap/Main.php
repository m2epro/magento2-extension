<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\ShippingMap;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Main extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonShippingMapMain');
        $this->_controller = 'adminhtml_amazon_shippingMap';

        // ---------------------------------------

        $this->removeButton('back');
        $this->removeButton('reset');
    }
}
