<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Item;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Item\Edit
 */
class Edit extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
    }

    protected function _prepareLayout()
    {
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Order'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Log_Order'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Log_Order'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Log_Order'));

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
