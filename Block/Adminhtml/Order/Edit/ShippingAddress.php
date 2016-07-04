<?php

namespace Ess\M2ePro\Block\Adminhtml\Order\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class ShippingAddress extends AbstractBlock
{
    protected $shippingAddress = null;

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    public function getOrder()
    {
        return $this->getHelper('Data\GlobalData')->getValue('order');
    }

    public function getShippingAddress()
    {
        if (is_null($this->shippingAddress)) {
            /** @var $shippingAddress \Ess\M2ePro\Model\Amazon\Order\ShippingAddress */
            $shippingAddress = $this->getOrder()->getShippingAddress();

            $this->shippingAddress = $shippingAddress->getData();
            $this->shippingAddress['country_name'] = $shippingAddress->getCountryName();
        }

        return $this->shippingAddress;
    }
}