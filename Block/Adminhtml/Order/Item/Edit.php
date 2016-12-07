<?php

namespace Ess\M2ePro\Block\Adminhtml\Order\Item;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

class Edit extends AbstractContainer
{
    protected function _prepareLayout()
    {
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Order'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Log\Order'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Log\Order'));

        $this->jsTranslator->addTranslations([
            'Please enter correct Product ID or SKU.' => $this->__('Please enter correct Product ID or SKU.'),
            'Please enter correct Product ID.' => $this->__('Please enter correct Product ID.'),
            'Edit Shipping Address' => $this->__('Edit Shipping Address'),
        ]);

        $this->js->add(<<<JS
    require([
        'M2ePro/Order/Edit/Item',
    ], function(){
        window.OrderEditItemObj = new OrderEditItem();
    });
JS
        );

        return parent::_prepareLayout();
    }
}