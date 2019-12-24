<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Edit\ShippingAddress
 */
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
        if ($this->shippingAddress === null) {
            /** @var $shippingAddress \Ess\M2ePro\Model\Amazon\Order\ShippingAddress */
            $shippingAddress = $this->getOrder()->getShippingAddress();

            $this->shippingAddress = $shippingAddress->getData();
            $this->shippingAddress['country_name'] = $shippingAddress->getCountryName();
        }

        return $this->shippingAddress;
    }
}
