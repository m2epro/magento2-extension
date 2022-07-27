<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class ShippingAddress extends AbstractBlock
{
    protected $shippingAddress = null;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    protected $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return \Ess\M2ePro\Model\Order
     */
    public function getOrder()
    {
        return $this->globalDataHelper->getValue('order');
    }

    public function getShippingAddress()
    {
        if ($this->shippingAddress === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Order\ShippingAddress $shippingAddress */
            $shippingAddress = $this->getOrder()->getShippingAddress();

            $this->shippingAddress = $shippingAddress->getData();
            $this->shippingAddress['country_name'] = $shippingAddress->getCountryName();
        }

        return $this->shippingAddress;
    }
}
