<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\ShippingAddress
 */
class ShippingAddress extends \Ess\M2ePro\Model\Order\ShippingAddress
{
    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRawData()
    {
        $buyerName = $this->order->getChildObject()->getBuyerName();
        $recipientName = $this->getData('recipient_name');
        return [
            'buyer_name'     => $buyerName,
            'recipient_name' => $recipientName ? $recipientName : $buyerName,
            'email'          => $this->getBuyerEmail(),
            'country_id'     => $this->getData('country_code'),
            'region'         => $this->getData('state'),
            'city'           => $this->getData('city') ? $this->getData('city') : $this->getCountryName(),
            'postcode'       => $this->getPostalCode(),
            'telephone'      => $this->getPhone(),
            'company'        => $this->getData('company'),
            'street'         => $this->getStreet()
        ];
    }

    protected function getBuyerEmail()
    {
        $email = $this->order->getChildObject()->getData('buyer_email');

        if (stripos($email, 'Invalid Request') !== false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = str_replace(' ', '-', strtolower($this->order->getChildObject()->getBuyerUserId()));
            $email .= \Ess\M2ePro\Model\Magento\Customer::FAKE_EMAIL_POSTFIX;
        }

        return $email;
    }

    protected function getPostalCode()
    {
        $postalCode = $this->getData('postal_code');

        if (stripos($postalCode, 'Invalid Request') !== false || $postalCode == '') {
            $postalCode = '0000';
        }

        return $postalCode;
    }

    protected function getPhone()
    {
        $phone = $this->getData('phone');

        if (stripos($phone, 'Invalid Request') !== false || $phone == '') {
            $phone = '0000000000';
        }

        return $phone;
    }

    protected function getStreet()
    {
        $street = $this->getData('street');

        if ($this->order->getChildObject()->getEbayAccount()->isSkipEvtinModeOn()) {
            $street = array_map(
                function ($streetLine) {
                    $ebayPos = strpos($streetLine, 'ebay');

                    return $ebayPos === false ? $streetLine : trim(substr($streetLine, 0, $ebayPos));
                },
                $street
            );
        }

        return array_filter($street);
    }

    //########################################
}
