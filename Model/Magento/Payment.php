<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'm2epropayment';

    protected $_canUseCheckout = false;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

    protected $_infoBlockType = 'Ess\M2ePro\Block\Adminhtml\Magento\Payment\Info';

    //########################################

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $data = $data->getData()['additional_data'];

        $details = array(
            'component_mode'        => $data['component_mode'],
            'payment_method'        => $data['payment_method'],
            'channel_order_id'      => $data['channel_order_id'],
            'channel_final_fee'     => $data['channel_final_fee'],
            'cash_on_delivery_cost' => $data['cash_on_delivery_cost'],
            'transactions'          => $data['transactions'],
            'tax_id'                => isset($data['tax_id']) ? $data['tax_id'] : null,
        );

        $this->getInfoInstance()->setAdditionalInformation($details);

        return $this;
    }

    //########################################
}